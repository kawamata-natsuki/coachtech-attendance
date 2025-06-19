<?php

namespace Database\Seeders;

use App\Enums\WorkStatus;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::parse('2025-04-01');
        $endDate = Carbon::parse('2025-06-12');

        $holidays = [
            '2025-04-29',
            '2025-05-03',
            '2025-05-04',
            '2025-05-05',
            '2025-05-06',
        ];

        $users = User::all();

        foreach ($users as $user) {
            $date = $startDate->copy();

            while ($date->lte($endDate)) {
                $isHoliday = in_array($date->toDateString(), $holidays);

                if ($date->isWeekday() && !$isHoliday) {
                    // 平日：出勤レコード＋休憩
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $date->copy()->setTime(9, 0),
                        'clock_out' => $date->copy()->setTime(18, 0),
                        'work_status' => WorkStatus::COMPLETED,
                    ]);
                    $attendance->breakTimes()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $date->copy()->setTime(12, 0),
                        'break_end' => $date->copy()->setTime(13, 0),
                    ]);
                }

                $date->addDay();
            }
        }
    }
}
