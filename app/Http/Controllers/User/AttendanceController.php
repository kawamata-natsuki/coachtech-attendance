<?php

namespace App\Http\Controllers\User;

use App\Services\AttendanceService;
use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    // 勤怠登録画面（一般ユーザー）表示処理
    public function record()
    {
        $user   = Auth::user();
        $today  = today();

        // DB に attendanceレコードあれば取得、なければ未保存の新規レコード生成
        $attendance = Attendance::firstOrNew(
            ['user_id'      => $user->id, 'work_date' => $today],
        );
        if (!$attendance->exists) {
            $attendance->work_status = WorkStatus::OFF;
        }

        return view(
            'user.attendances.record',
            [
                'attendance'    => $attendance,
                'statusLabel' => $attendance->work_status?->label() ?? '',
            ]
        );
    }

    // 勤怠登録画面（一般ユーザー）勤怠登録処理
    public function store(Request $request)
    {
        $user   = Auth::user();
        $today  = today();
        $now    = now();

        // 初回打刻時にレコード作成（1日1回出勤）
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['work_status' => WorkStatus::OFF]
        );

        // リクエストのアクションに応じて勤怠登録処理を分岐
        switch ($request->input('action')) {
            // 出勤：初回のみ有効
            case 'clock_in':
                if ($attendance->clock_in === null) {
                    $attendance->update([
                        'clock_in'      => $now,
                        'work_status'   => WorkStatus::WORKING,
                    ]);
                }
                break;

            // 休憩入
            case 'break_start':
                $attendance->breakTimes()->create([
                    'break_start' => $now,
                ]);
                $attendance->update([
                    'work_status' => WorkStatus::BREAK,
                ]);
                break;

            // 休憩戻
            case 'break_end':
                // まだ終了していない最新の休憩（break_end が null）を取得
                $activeBreak = $attendance->breakTimes()
                    ->whereNull('break_end')
                    ->oldest('break_start')
                    ->first();

                if ($activeBreak) {
                    $activeBreak->update([
                        'break_end' => $now,
                    ]);

                    $attendance->update([
                        'work_status' => WorkStatus::WORKING,
                    ]);
                }
                break;

            // 退勤
            case 'clock_out':
                if ($attendance->clock_out === null) {
                    $attendance->update([
                        'clock_out' => $now,
                        'work_status' => WorkStatus::COMPLETED,
                    ]);
                }
                break;
        }
        return redirect()->route('user.attendances.record');
    }

    // 勤怠一覧画面（一般ユーザー）の表示処理
    public function index(Request $request)
    {
        $user = Auth::user();

        // 表示対象の月（クエリがなければ今月）
        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        // 当月分の勤怠データ取得
        $attendances = AttendanceService::getMonthlyAttendances(auth()->id(), $currentMonth);

        // 月ナビゲーションの前月・翌月リンク用
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $prevUrl = route('user.attendances.index', ['month' => $prevMonth]);
        $nextUrl = route('user.attendances.index', ['month' => $nextMonth]);

        return view('shared.attendances.index', [
            'attendances'   => $attendances,
            'currentMonth'  => $currentMonth,
            'user'          => $user->id,
            'prevUrl'       => $prevUrl,
            'nextUrl'       => $nextUrl,
        ]);
    }

    // 勤怠詳細画面（共通）の表示処理
    public function show(Request $request, $id)
    {
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

        // クエリパラメータから申請IDを取得
        $requestId = $request->query('request_id');

        if ($requestId) {
            // 指定された correctionRequest を取得（セキュリティで attendance_id も確認）
            $correctionRequest = \App\Models\CorrectionRequest::with('correctionBreakTimes')
                ->where('id', $requestId)
                ->where('attendance_id', $attendance->id)
                ->firstOrFail();
        } else {
            // 通常は最新の申請を表示
            $correctionRequest = $attendance->correctionRequests()->latest()->first();
        }

        // 承認待ちのときだけ入力を無効化
        $isCorrectionDisabled = $correctionRequest?->isPending();

        $breakTimes = $attendance->breakTimes;
        $nextIndex = count($breakTimes);

        return view('shared.attendances.show', [
            'attendance' => $attendance,
            'correctionRequest' => $correctionRequest,
            'breakTimes' => $breakTimes,
            'nextIndex' => $nextIndex,
            'isCorrectionDisabled' => $isCorrectionDisabled,
        ]);
    }

    // 勤怠詳細画面（共通）の修正申請処理
    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        // 勤怠と日付情報取得
        $attendance = Attendance::findOrFail($id);
        $workDate = $attendance->work_date;
        $breaks = $request->input('requested_breaks', []);

        $requestedClockInParts = $request->input('requested_clock_in');
        $requestedClockOutParts = $request->input('requested_clock_out');

        $requestedClockIn = filled($requestedClockInParts['hour'] ?? null) && filled($requestedClockInParts['minute'] ?? null)
            ? Carbon::createFromDate($workDate->year, $workDate->month, $workDate->day)
            ->setTime($requestedClockInParts['hour'], $requestedClockInParts['minute'])
            : null;

        $requestedClockOut = filled($requestedClockOutParts['hour'] ?? null) && filled($requestedClockOutParts['minute'] ?? null)
            ? Carbon::createFromDate($workDate->year, $workDate->month, $workDate->day)
            ->setTime($requestedClockOutParts['hour'], $requestedClockOutParts['minute'])
            : null;

        // 申請レコードを新規作成（常に create）
        $correctionRequest = $attendance->correctionRequests()->create([
            'user_id'             => $attendance->user_id,
            'work_date'           => $attendance->work_date,
            'original_clock_in'   => $attendance->clock_in,
            'original_clock_out'  => $attendance->clock_out,
            'requested_clock_in'  => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
            'reason'              => $request->input('reason'),
        ]);

        // 休憩を保存
        $originalBreaks = $attendance->breakTimes->values();

        foreach ($breaks as $i => $break) {
            $startParts = $break['requested_break_start'];
            $endParts   = $break['requested_break_end'];

            // 空フォームの入力（完全に未入力）なら無視
            $isEmptyInput = empty($startParts['hour']) && empty($startParts['minute']) &&
                empty($endParts['hour']) && empty($endParts['minute']);

            // break_time_idが存在しない＝空フォームの追加用で保存対象外
            $hasBreakTimeId = isset($break['break_time_id']);

            if ($isEmptyInput && !$hasBreakTimeId) {
                continue; // 完全に何も入力されてないやつはスルー
            }

            // 対応する breakTime（元データ）があれば取得、なければ null
            $originalBreak = $originalBreaks->get($i);

            $correctionRequest->correctionBreakTimes()->create([
                'requested_break_start' => $isEmptyInput ? null : Carbon::createFromTime($startParts['hour'], $startParts['minute']),
                'requested_break_end'   => $isEmptyInput ? null : Carbon::createFromTime($endParts['hour'], $endParts['minute']),
                'original_break_start'  => $originalBreak?->break_start,
                'original_break_end'    => $originalBreak?->break_end,
            ]);
        }


        return redirect()
            ->route('attendances.show', ['id' => $id])
            ->with('success', '修正を受け付けました');
    }
}
