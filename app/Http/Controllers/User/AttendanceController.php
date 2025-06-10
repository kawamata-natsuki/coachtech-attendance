<?php

namespace App\Http\Controllers\User;

use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

        // 打刻前は new Attendance() で表示、それ以外はDBから取得して表示
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first() ?? new Attendance([
                'user_id' => $user->id,
                'work_date' => $today,
                'work_status' => WorkStatus::OFF,
            ]);

        $statusLabel = $attendance->work_status->label();
        $statusValue = $attendance->work_status->value;

        // 曜日を日本語に変換
        $weekdays = ['日', '月', '火', '水', '木', '金', '土',];
        $formattedDate = $today->format('Y年m月d日') . '(' . $weekdays[$today->dayOfWeek] . ')';

        return view('user.attendances.record', compact('attendance', 'statusLabel', 'statusValue', 'formattedDate'));
    }

    // 勤怠登録の処理
    public function store(Request $request)
    {
        $user = Auth::user();
        $today = today();

        // 打刻時にレコードを作成（1日1回出勤）
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['work_status' => WorkStatus::OFF]
        );

        // リクエストのアクションに応じて打刻処理を分岐
        switch ($request->input('action')) {
            case 'clock_in':
                if ($attendance->clock_in === null) {
                    $attendance->update([
                        'clock_in' => now(),
                        'work_status' => WorkStatus::WORKING,
                    ]);
                }
                break;

            case 'break_start':
                // 休憩ごとに毎回新しいレコードをつくる
                $attendance->breakTimes()->create([
                    'break_start' => now(),
                ]);
                $attendance->update([
                    'work_status' => WorkStatus::BREAK,
                ]);
                break;

            case 'break_end':
                $lastBreak = $attendance->breakTimes()
                    ->whereNull('break_end')
                    ->latest()
                    ->first();

                if ($lastBreak) {
                    $lastBreak->update([
                        'break_end' => now(),
                    ]);

                    $attendance->update([
                        'work_status' => WorkStatus::WORKING,
                    ]);
                }
                break;

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

    // 勤怠一覧画面の表示
    public function index(Request $request)
    {
        $targetMonth = $request->input('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))
            : now()->startOfMonth();

        $rawAttendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('work_date', [
                $targetMonth->copy()->startOfMonth(),
                $targetMonth->copy()->endOfMonth()
            ])
            ->get()
            ->mapWithKeys(fn($item) => [$item->work_date->format('Y-m-d') => $item]);

        $attendances = collect();

        for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
            $date = $targetMonth->copy()->day($day)->format('Y-m-d');

            $attendances->push(
                $rawAttendances[$date] ?? new Attendance([
                    'user_id' => auth()->id(),
                    'work_date' => $date,
                    'work_status' => \App\Enums\WorkStatus::OFF,
                ])
            );
        }

        return view('shared.attendances.index', [
            'attendances' => $attendances,
            'currentMonth' => $targetMonth,
        ]);
    }

    // 勤怠詳細画面の表示
    public function show()
    {
        return view('shared.attendances.show');
    }

    // 勤怠詳細修正の処理
    public function update()
    {
        //
    }
}
