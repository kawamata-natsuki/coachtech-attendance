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
            ['work_status'  => WorkStatus::OFF]
        );

        return view(
            'user.attendances.record',
            [
                'attendance'    => $attendance,
                'statusLabel'   => $attendance->work_status->label(),
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
        $correctionRequest = $attendance->correctionRequests()->latest()->first();

        $isCorrectionDisabled = $correctionRequest?->isPending() || $correctionRequest?->isApproved();
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

    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $attendance = Attendance::findOrFail($id);
        $breaks = $request->input('requested_breaks', []);

        $clockInParts = $request->input('requested_clock_in');
        $clockOutParts = $request->input('requested_clock_out');

        $clockIn = filled($clockInParts['hour'] ?? null) && filled($clockInParts['minute'] ?? null)
            ? Carbon::createFromTime($clockInParts['hour'], $clockInParts['minute'])
            : null;

        $clockOut = filled($clockOutParts['hour'] ?? null) && filled($clockOutParts['minute'] ?? null)
            ? Carbon::createFromTime($clockOutParts['hour'], $clockOutParts['minute'])
            : null;

        // 未来の退勤時間は申請不可
        return back()->withErrors([
            'requested_clock_out' => [
                'hour' => '未来の時間は申請できません。',
            ],
        ])->withInput();

        // 既存の修正申請があれば更新、なければ作成
        $correctionRequest = $attendance->correctionRequests()->updateOrCreate(
            ['user_id' => $attendance->user_id],
            [
                'work_date' => $attendance->work_date,
                'original_clock_in' => $attendance->clock_in,
                'original_clock_out' => $attendance->clock_out,
                'requested_clock_in' => $clockIn,
                'requested_clock_out' => $clockOut,
                'reason' => $request->input('reason'),
            ]
        );

        // 休憩時間も含めて保存
        foreach ($breaks as $break) {
            // 休憩の開始・終了どちらもnullなら保存しない
            if (empty($break['requested_break_start']) && empty($break['requested_break_end'])) {
                continue;
            }

            $correctionRequest->correctionBreakTimes()->create([
                'requested_break_start' => $break['requested_break_start'],
                'requested_break_end' => $break['requested_break_end'],
            ]);
        }

        return redirect()
            ->route('attendances.show', ['id' => $id])
            ->with('success', '修正を受け付けました');
    }
}
