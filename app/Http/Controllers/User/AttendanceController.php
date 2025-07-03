<?php

namespace App\Http\Controllers\User;

use App\Enums\ApprovalStatus;
use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠登録画面（一般ユーザー）表示処理
    public function record()
    {
        $user   = Auth::user();
        $today  = today();

        // DB に attendanceレコードあれば取得、なければ未保存の新規レコード生成
        $attendance = Attendance::firstOrNew(
            [
                'user_id'       => $user->id,
                'work_date'     => $today,
            ],
            [
                'work_status'   => WorkStatus::OFF,
            ]
        );

        return view(
            'user.attendances.record',
            [
                'attendance'    => $attendance,
                'statusLabel'   => $attendance->work_status?->label() ?? '',
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
            [
                'user_id'       => $user->id,
                'work_date'     => $today,
            ],
            [
                'work_status'   => WorkStatus::OFF,
            ]
        );

        // リクエストのアクションに応じて勤怠登録処理を分岐
        switch ($request->input('action')) {
            // 出勤
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
                // 未終了の休憩（break_end が null）を取得
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

        // 表示対象の月（クエリがなければ今月）を取得
        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        // 対象月の勤怠データ取得
        $attendances = AttendanceService::getMonthlyAttendances(
            $user->id,
            $currentMonth,
        );

        // 月ナビゲーションの前月・翌月リンク作成
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $prevUrl = route('user.attendances.index', [
            'month' => $prevMonth,
        ]);
        $nextUrl = route('user.attendances.index', [
            'month' => $nextMonth,
        ]);

        return view('shared.attendances.index', [
            'attendances'   => $attendances,
            'currentMonth'  => $currentMonth,
            'user'          => $user,
            'prevUrl'       => $prevUrl,
            'nextUrl'       => $nextUrl,
        ]);
    }

    // 勤怠詳細画面（共通）の表示処理
    public function show(Request $request, $id)
    {
        // 勤怠レコードと関連するユーザー・休憩データを取得
        $attendance = Attendance::with([
            'user',
            'breakTimes',
        ])->findOrFail($id);

        // 勤怠レコードの休憩情報を取得し、新規休憩のインデックスを決定
        $breakTimes = $attendance->breakTimes;
        $nextIndex = count($breakTimes);

        // クエリパラメータから申請IDを取得
        $requestId = $request->query('request_id');

        // 指定された correctionRequest を取得
        if ($requestId) {
            $correctionRequest = CorrectionRequest::with('correctionBreakTimes')
                ->where('id', $requestId)
                ->where('attendance_id', $attendance->id)
                ->firstOrFail();
        } else {
            // 通常は最新の申請を表示
            $correctionRequest = $attendance->correctionRequests()->latest()->first();
        }

        // 申請が「承認待ち」の場合はフォームを無効化
        $isCorrectionDisabled = $correctionRequest?->isPending();

        return view('shared.attendances.show', [
            'attendance'            => $attendance,
            'correctionRequest'     => $correctionRequest,
            'breakTimes'            => $breakTimes,
            'nextIndex'             => $nextIndex,
            'isCorrectionDisabled'  => $isCorrectionDisabled,
        ]);
    }

    // 勤怠詳細画面（共通）の修正申請処理
    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        // 対象の勤怠レコードを取得
        $attendance = Attendance::findOrFail($id);
        $workDate = $attendance->work_date;

        // すでに承認待ちの申請があるかチェック
        $pendingExists = $attendance->correctionRequests()
            ->where('approval_status', ApprovalStatus::PENDING)
            ->exists();
        if ($pendingExists) {
            return redirect()
                ->route('attendances.show', ['id' => $id])
                ->with('error', 'すでに承認待ちの修正申請が存在します。承認されるまで新しい申請はできません。');
        }

        // 申請フォームから休憩時間データを取得
        $breaks = $request->input('requested_breaks', []);

        // 入力された出勤・退勤時刻を分解して Carbon に変換
        $requestedClockInParts = $request->input('requested_clock_in');
        $requestedClockOutParts = $request->input('requested_clock_out');

        // 出勤時刻：時間と分が両方入力されていれば Carbon に変換、なければ null
        $requestedClockIn = filled($requestedClockInParts['hour'] ?? null) && filled($requestedClockInParts['minute'] ?? null)
            ? Carbon::createFromDate($workDate->year, $workDate->month, $workDate->day)
            ->setTime($requestedClockInParts['hour'], $requestedClockInParts['minute'])
            : null;

        // 退勤時刻：時間と分が両方入力されていれば Carbon に変換、なければ null
        $requestedClockOut = filled($requestedClockOutParts['hour'] ?? null) && filled($requestedClockOutParts['minute'] ?? null)
            ? Carbon::createFromDate($workDate->year, $workDate->month, $workDate->day)
            ->setTime($requestedClockOutParts['hour'], $requestedClockOutParts['minute'])
            : null;

        // 勤怠修正申請レコードを新規作成（毎回 create して履歴を残す）
        $correctionRequest = $attendance->correctionRequests()->create([
            'user_id'             => $attendance->user_id,
            'work_date'           => $attendance->work_date,
            'original_clock_in'   => $attendance->clock_in,
            'original_clock_out'  => $attendance->clock_out,
            'requested_clock_in'  => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
            'reason'              => $request->input('reason'),
        ]);

        // 勤怠に紐づく元々の休憩情報を取得
        $originalBreaks = $attendance->breakTimes->values();

        // 入力された各休憩の申請内容を保存
        foreach ($breaks as $i => $break) {
            $startParts = $break['requested_break_start'];
            $endParts   = $break['requested_break_end'];

            // 入力されてないただの空欄はスキップ
            $isEmptyInput = empty($startParts['hour']) && empty($startParts['minute']) &&
                empty($endParts['hour']) && empty($endParts['minute']);

            // break_time_idがある場合は既存の休憩データに対応
            $hasBreakTimeId = isset($break['break_time_id']);

            // 空フォームで既存データにも紐づかない場合はスキップ
            if ($isEmptyInput && !$hasBreakTimeId) {
                continue;
            }

            // 元々の休憩データをインデックスで取得
            $originalBreak = $originalBreaks->get($i);

            // 休憩申請を修正申請に紐づけて作成
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
