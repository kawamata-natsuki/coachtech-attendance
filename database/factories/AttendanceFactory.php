<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
            'clock_out' => now()->setTime(18, 0),
            'work_status' => WorkStatus::COMPLETED,
        ];
    }

    public function withBreakTime(): static
    {
        return $this->afterCreating(function (Attendance $attendance) {
            $attendance->breakTimes()->create([
                'break_start' => now()->setTime(12, 0),
                'break_end' => now()->setTime(13, 0),
            ]);
        });
    }
}
