<?php

namespace Database\Seeders;

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
        $endDate = Carbon::parse('2025-06-30');

        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            $date = $startDate->copy();
            while ($date->lte($endDate)) {
                if ($date->isWeekday()) {
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $date->copy()->setTime(9, 0),
                        'clock_out' => $date->copy()->setTime(18, 0),
                        'work_status' => 'working',
                    ]);

                    BreakTime::create([
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
