<?php

namespace App\Http\Controllers\User;

use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
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
            ? Carbon::createFromFormat('Y-m', $request->input('month'))
            : now()->startOfMonth();

        $attendances = AttendanceService::generateMonthlyAttendances(auth()->id(), $targetMonth);

        return view('shared.attendances.index', [
            'attendances' => $attendances,
            'currentMonth' => $targetMonth,
        ]);
    }

    // 勤怠詳細画面の表示
    public function show($id)
    {
        $attendance = Attendance::findOrFail($id);

        return view('shared.attendances.show', [
            'attendance' => $attendance,
        ]);
    }

    // 勤怠詳細修正の処理
    public function update()
    {
        //
    }
}
