<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attendance;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\AttendanceLogService;
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠一覧画面（管理者）表示
    public function index(Request $request)
    {
        // 表示対象の日付（クエリ指定がなければ今日）
        $dateParam = $request->query('date');
        $currentDate = $dateParam
            ? Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay()
            : now()->startOfDay();

        // 表示対象の日付の各ユーザーの勤怠データを取得
        $users = User::with(['attendances' => function ($query) use ($currentDate) {
            $query->whereDate('work_date', $currentDate->toDateString());
        }])->get();

        foreach ($users as $user) {
            $user->attendanceForDay = $user->attendances->first();
        }

        // 日ナビゲーションのためのURL生成（currenrDateの全日と翌日）
        $prevUrl = route('admin.attendances.index', ['date' => $currentDate->copy()->subDay()->format('Y-m-d')]);
        $nextUrl = route('admin.attendances.index', ['date' => $currentDate->copy()->addDay()->format('Y-m-d')]);

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
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        return view('shared.attendances.show', [
            'attendance' => $attendance,
            'correctionRequest' => null,
            'breakTimes' => $attendance->breakTimes,
            'isCorrectionDisabled' => false,
            'nextIndex' => $attendance->breakTimes->count(),
        ]);
    }

    // 勤怠詳細画面（管理者）即時修正処理
    public function update(Request $request, $id, AttendanceLogService $logService)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // ===== 修正前のデータ保持 =====
        $beforeClockIn = $attendance->clock_in;
        $beforeClockOut = $attendance->clock_out;
        $beforeReason = $attendance->reason;
        $beforeBreaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();

        // ===== 出勤・退勤時間の整形（HH:MM形式）=====
        $clockInParts = $request->input('requested_clock_in');
        $clockOutParts = $request->input('requested_clock_out');

        $clockIn = filled($clockInParts['hour']) && filled($clockInParts['minute'])
            ? Carbon::createFromTime($clockInParts['hour'], $clockInParts['minute'])
            : null;

        $clockOut = filled($clockOutParts['hour']) && filled($clockOutParts['minute'])
            ? Carbon::createFromTime($clockOutParts['hour'], $clockOutParts['minute'])
            : null;

        // ===== 出勤・退勤時間・備考の更新 =====
        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'reason' => $request->input('reason'),
        ]);

        // ===== 休憩時間の更新 =====
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

        // ===== モデルの状態を最新に更新（再取得）=====
        $attendance->refresh();

        // ===== 修正後の休憩時間を整形 =====
        $afterBreaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();

        // ===== 修正ログを記録（管理者による即時修正）=====
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

        // ===== 完了後リダイレクト =====
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

        $prevUrl = route('user.attendances.index', ['month' => $prevMonth]);
        $nextUrl = route('user.attendances.index', ['month' => $nextMonth]);

        return view('shared.attendances.index', [
            'user' => $user,
            'attendances' => $attendances,
            'currentMonth' => $currentMonth,
            'isAdminView' => true,
            'prevUrl'       => $prevUrl,
            'nextUrl'       => $nextUrl,
        ]);
    }

    // スタッフ別勤怠一覧画面CSVエクスポート処理
    public function exportCsv($id)
    {
        $user = User::findOrFail($id);
        $currentMonth = now()->startOfMonth();
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
