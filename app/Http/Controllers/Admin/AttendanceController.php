<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\AttendanceLogService;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠一覧画面（管理者）表示
    public function index(Request $request)
    {
        // 表示対象の日を取得
        $dateParam = $request->query('date');
        $currentDate = $dateParam
            ? Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay()
            : now()->startOfDay();

        // 日付ナビゲーション用のURL生成
        $prevUrl = route('admin.attendances.index', ['date' => $currentDate->copy()->subDay()->format('Y-m-d')]);
        $nextUrl = route('admin.attendances.index', ['date' => $currentDate->copy()->addDay()->format('Y-m-d')]);

        // 表示対象の日付の各ユーザーの勤怠データを取得
        $users = User::with(['attendances' => function ($query) use ($currentDate) {
            $query->whereDate('work_date', $currentDate->toDateString());
        }])->get();

        // 休憩時間と合計勤務時間を計算して取得
        foreach ($users as $user) {
            $attendance = $user->attendances->first();
            if ($attendance) {
                $attendance->workTime = AttendanceService::calculateWorkTime($attendance);
                $attendance->breakTime = AttendanceService::calculateBreakTime($attendance);
            }
        }

        return view('admin.attendances.index', [
            'users' => $users,
            'currentDate' => $currentDate,
            'prevUrl' => $prevUrl,
            'nextUrl' => $nextUrl,
        ]);
    }

    // 勤怠詳細画面（管理者）表示
    public function show(Request $request, $id)
    {
        // 勤怠レコードと関連するユーザー・休憩データを取得
        $attendance = Attendance::with([
            'user',
            'breakTimes',
        ])->findOrFail($id);

        // クエリパラメータから申請IDを取得
        $requestId = $request->query('request_id');

        // 申請IDがあればその申請を取得、なければ勤怠に紐づく最新の申請を取得
        if ($requestId) {
            $correctionRequest = CorrectionRequest::with('correctionBreakTimes')
                ->where('id', $requestId)
                ->where('attendance_id', $attendance->id)
                ->firstOrFail();
        } else {
            $correctionRequest = $attendance->correctionRequests()->latest()->first();
        }

        // 出退勤時間：修正申請があればその申請内容を優先、なければ勤怠レコードの値を使う
        $requestedClockIn = optional(
            $correctionRequest?->requested_clock_in ?? $attendance->clock_in
        )?->format('H:i');
        $requestedClockOut = optional(
            $correctionRequest?->requested_clock_out ?? $attendance->clock_out
        )?->format('H:i');

        return view('shared.attendances.show', [
            'attendance'            => $attendance,
            'correctionRequest'     => $correctionRequest,
            'breakTimes'            => $attendance->breakTimes,
            'isCorrectionDisabled'  => false,
            'nextIndex'             => $attendance->breakTimes->count(),
            'requestedClockIn'      => $requestedClockIn,
            'requestedClockOut'     => $requestedClockOut,
        ]);
    }

    // 勤怠詳細画面（管理者）即時修正処理
    public function update(Request $request, $id, AttendanceLogService $logService)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 修正前のデータ保持
        $beforeClockIn  = $attendance->clock_in;
        $beforeClockOut = $attendance->clock_out;
        $beforeReason   = $attendance->reason;
        $beforeBreaks   = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();

        // --- 出勤・退勤の処理 --- 
        // HH:MM形式に変換
        $clockInParts = $request->input('requested_clock_in');
        $clockOutParts = $request->input('requested_clock_out');

        $clockIn = filled($clockInParts['hour']) && filled($clockInParts['minute'])
            ? Carbon::createFromTime($clockInParts['hour'], $clockInParts['minute'])
            : null;

        $clockOut = filled($clockOutParts['hour']) && filled($clockOutParts['minute'])
            ? Carbon::createFromTime($clockOutParts['hour'], $clockOutParts['minute'])
            : null;

        // 出勤・退勤時間・備考の更新
        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'reason' => $request->input('reason'),
        ]);

        // --- 休憩の処理 --- 
        // 休憩時間の更新
        foreach ($request->input('requested_breaks', []) as $break) {
            $breakStartParts = $break['requested_break_start'];
            $breakEndParts = $break['requested_break_end'];
            $breakTimeId = $break['break_time_id'] ?? null;

            $isDeleted = empty($breakStartParts['hour']) && empty($breakStartParts['minute']) &&
                empty($breakEndParts['hour']) && empty($breakEndParts['minute']);

            if ($isDeleted) {
                // 削除対象ならdelete
                if ($breakTimeId) {
                    $attendance->breakTimes()->where('id', $breakTimeId)->delete();
                }
                continue;
            }

            // HH:MM形式に変換
            $breakStart = filled($breakStartParts['hour']) && filled($breakStartParts['minute'])
                ? Carbon::createFromTime($breakStartParts['hour'], $breakStartParts['minute'])
                : null;
            $breakEnd = filled($breakEndParts['hour']) && filled($breakEndParts['minute'])
                ? Carbon::createFromTime($breakEndParts['hour'], $breakEndParts['minute'])
                : null;

            // 更新 or 新規作成
            $attendance->breakTimes()->updateOrCreate(
                ['id' => $breakTimeId],
                [
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]
            );
        }

        // 修正後の休憩時間を整形
        $afterBreaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();

        // --- 修正ログの処理 --- 
        // モデルの状態を最新に更新（再取得）
        $attendance->refresh();

        // 修正ログを記録（管理者による即時修正）
        $logService->logManual(
            $attendance,
            $beforeClockIn,
            $beforeClockOut,
            $clockIn,
            $clockOut,
            $beforeReason,
            $request->input('reason'),
            $beforeBreaks,
            $afterBreaks,
            Auth::guard('admin')->user()
        );

        // 直接修正完了後リダイレクト
        return redirect()
            ->route('attendances.show', ['id' => $id])
            ->with('success', '勤怠を修正しました');
    }

    // スタッフ別勤怠一覧画面（管理者）表示
    public function staff(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // クエリパラメータがなければ今月
        $currentMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))
            : now()->startOfMonth();

        $attendances = AttendanceService::getMonthlyAttendances($user->id, $currentMonth);

        // 月ナビゲーションの前月・翌月リンク用
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $prevUrl = route('admin.attendances.staff', [
            'id'    => $user->id,
            'month' => $prevMonth,
        ]);
        $nextUrl = route('admin.attendances.staff', [
            'id'    => $user->id,
            'month' => $nextMonth,
        ]);

        return view('shared.attendances.index', [
            'user' => $user,
            'attendances'   => $attendances,
            'currentMonth'  => $currentMonth,
            'isAdminView'   => true,
            'prevUrl'       => $prevUrl,
            'nextUrl'       => $nextUrl,
        ]);
    }

    // スタッフ別勤怠一覧画面CSVエクスポート処理
    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))
            : now()->startOfMonth();
        $attendances = AttendanceService::getMonthlyAttendances($user->id, $currentMonth);

        $filename = "勤務表_{$user->name}_{$currentMonth->format('Y-m')}.csv";

        return response()->streamDownload(function () use ($attendances, $user, $currentMonth) {
            $handle = fopen('php://output', 'w');

            // Excel対応の文字コード変換
            stream_filter_append($handle, 'convert.iconv.utf-8/cp932//TRANSLIT');

            // コメント行（ユーザー名・対象月など）
            fwrite($handle, "氏名：{$user->name}\r\n");
            fwrite($handle, "対象月：{$currentMonth->format('Y年n月')}\r\n");
            fputcsv($handle, []);
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            // 内容行
            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->work_date->locale('ja')->isoFormat('MM/DD(dd)'),
                    optional($attendance->clock_in)?->format('H:i') ?? '--',
                    optional($attendance->clock_out)?->format('H:i') ?? '--',
                    AttendanceService::calculateBreakTime($attendance),
                    AttendanceService::calculateWorkTime($attendance),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($filename),
        ]);
    }
}
