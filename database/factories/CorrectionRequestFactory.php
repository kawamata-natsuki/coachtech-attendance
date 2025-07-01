<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        return [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $this->faker->date(),
            'requested_clock_in' =>  now()->setTime(10, 0),
            'requested_clock_out' =>  now()->setTime(19, 0),
            'original_clock_in' =>  now()->setTime(9, 0),
            'original_clock_out' =>   now()->setTime(18, 0),
            'reason' => '電車遅延のため',
            'approval_status' => \App\Enums\ApprovalStatus::PENDING,
        ];
    }
}
