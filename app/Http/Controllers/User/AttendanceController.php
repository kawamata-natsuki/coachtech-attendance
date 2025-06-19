<?php

namespace App\Http\Controllers\User;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
                        'clock_in'      => now(),
                        'work_status'   => WorkStatus::WORKING,
                    ]);
                }
                break;

            // 休憩入
            case 'break_start':
                $attendance->breakTimes()->create([
                    'break_start' => now(),
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
                        'break_end' => now(),
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
                        'clock_out' => now(),
                        'work_status' => WorkStatus::COMPLETED,
                    ]);
                }
                break;
        }
        return redirect()->route('user.attendances.record');
    }
}
