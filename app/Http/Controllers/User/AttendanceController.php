<?php

namespace App\Http\Controllers\User;

use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreakTime;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 勤怠登録画面の表示
    public function record()
    {
        $user = Auth::user();
        $today = today();

        // 打刻前表示用：DBにレコードがなければ未打刻の新しいインスタンスを作成※保存はしない
        $attendance = Attendance::firstOrNew(
            ['user_id' => $user->id, 'work_date' => $today],
            ['work_status' => WorkStatus::OFF]
        );

        // 勤務ステータス表示
        $statusLabel = $attendance->work_status->label();

        return view('user.attendances.record', compact('attendance', 'statusLabel'));
    }

    // 勤怠登録の処理
    public function store(Request $request)
    {
        $user = Auth::user();
        $today = today();

        // 初回打刻時にレコードを作成（1日1回出勤）
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['work_status' => WorkStatus::OFF]
        );

        // リクエストのアクションに応じて勤怠登録処理を分岐
        switch ($request->input('action')) {
            case 'clock_in':
                // 出勤打刻：初回のみ有効（clock_inがnullのとき）
                if ($attendance->clock_in === null) {
                    $attendance->update([
                        'clock_in' => now(),
                        'work_status' => WorkStatus::WORKING,
                        'is_dummy' => false,
                    ]);
                }
                break;

            case 'break_start':
                // 休憩開始：関連するbreaksテーブルにレコード追加＋attendancesテーブルのステータス変更
                $attendance->breakTimes()->create([
                    'break_start' => now(),
                ]);
                $attendance->update([
                    'work_status' => WorkStatus::BREAK,
                ]);
                break;

            case 'break_end':
                // 休憩終了：まだ終了していない最新の休憩（break_end が null）を取得
                $activeBreak = $attendance->breakTimes()
                    ->whereNull('break_end')
                    ->latest()
                    ->first();

                if ($activeBreak) {
                    $activeBreak->update([
                        'break_end' => now(),
                    ]);

                    $attendance->update([
                        'work_status' => WorkStatus::WORKING,
                    ]);
                }
                break;

            case 'clock_out':
                // 退勤打刻：初回のみ有効（clock_outがnullのとき）
                if ($attendance->clock_out === null) {
                    $attendance->update([
                        'clock_out' => now(),
                        'work_status' => WorkStatus::COMPLETED,
                    ]);
                }
                break;
        }
        return redirect()->route('user.attendances.record');
    }

    // 勤怠一覧画面の表示
    public function index(Request $request)
    {
        $targetMonth = $request->input('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
            : now()->startOfMonth();

        $attendances = AttendanceService::generateThisMonthAttendances(auth()->id(), $targetMonth);

        return view('shared.attendances.index', [
            'attendances' => $attendances,
            'currentMonth' => $targetMonth,
        ]);
    }

    // 勤怠詳細画面の表示
    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        $break_times = $attendance->breakTimes()->get();

        return view(
            'shared.attendances.show',
            compact('attendance', 'break_times')
        );
    }

    // 勤怠詳細修正の処理
    public function update(AttendanceCorrectionRequest $request, $id)
    {
        // 該当の勤怠レコードを取得、存在しない場合はエラーメッセージを表示
        $attendance = Attendance::find($id);
        if (!$attendance) {
            return redirect()->route('user.attendances.index')
                ->with('error', '指定された勤怠データは存在しません。');
        }

        // リクエストの内容が元データから変更されているかをチェック
        $clockInChanged = $request->requested_clock_in !== optional($attendance->clock_in)->format('H:i');
        $clockOutChanged = $request->requested_clock_out !== optional($attendance->clock_out)->format('H:i');

        // 変更された休憩を収集するためのコレクションを初期化
        $changedBreaks = collect();

        // 申請レコードを作成するための変数を初期化
        $correctionRequest = null;

        // リクエストに含まれる休憩時間の修正を一つずつチェック
        foreach ($request->input('requested_breaks', []) as $break) {
            // 休憩の修正申請がなければスキップ
            if (!isset($break['break_time_id'])) {
                continue;
            }

            // 該当する休憩レコードをDBから取得、存在しない場合はスキップ
            $breakTime = BreakTime::find($break['break_time_id']);
            if (!$breakTime) {
                continue;
            }

            // リクエストの内容が元データから変更されているかをチェック
            $startChanged = $break['requested_break_start'] !== optional($breakTime->break_start)->format('H:i');
            $endChanged = $break['requested_break_end'] !== optional($breakTime->break_end)->format('H:i');

            // 変更がある場合は$changedBreaksに休憩データを追加
            if ($startChanged || $endChanged) {
                $changedBreaks->push([
                    'break_time_id' => $breakTime->id,
                    'requested_break_start' => $break['requested_break_start'],
                    'requested_break_end' => $break['requested_break_end'],
                    'original_break_start' => $breakTime->break_start,
                    'original_break_end' => $breakTime->break_end,
                ]);
            }
        }

        // 出勤、退勤、休憩のいずれかに変更がある場合のみ、勤怠修正申請レコードを correction_requests テーブルに作成
        if ($clockInChanged || $clockOutChanged || $changedBreaks->isNotEmpty()) {
            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => Auth::id(),
                'work_date' => $attendance->work_date,
                'requested_clock_in' => $request->requested_clock_in,
                'requested_clock_out' => $request->requested_clock_out,
                'original_clock_in' => $attendance->clock_in,
                'original_clock_out' => $attendance->clock_out,
                'reason' => $request->reason,
            ]);
        }

        // 休憩申請レコードを correction_break_times テーブルに作成
        foreach ($changedBreaks as $break) {
            if ($correctionRequest) {
                $break['correction_request_id'] = $correctionRequest->id;
                CorrectionBreakTime::create($break);
            }
        }

        return redirect()->route('user.attendances.show', ['id' => $attendance->id])
            ->with('success', '修正申請を受け付けました');
    }
}
