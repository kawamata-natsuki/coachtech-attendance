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
        return [
            'work_date' => today()->subDay()->toDateString(),
            'requested_clock_in' =>  now()->setTime(10, 0),
            'requested_clock_out' =>  now()->setTime(19, 0),
            'original_clock_in' =>  now()->setTime(9, 0),
            'original_clock_out' =>   now()->setTime(18, 0),
            'reason' => '電車遅延のため',
            'approval_status' => \App\Enums\ApprovalStatus::PENDING,
        ];
    }
}
