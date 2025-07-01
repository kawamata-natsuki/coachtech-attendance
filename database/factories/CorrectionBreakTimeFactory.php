<?php

namespace Database\Factories;

use App\Models\CorrectionBreakTime;
use App\Models\CorrectionRequest;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionBreakTimeFactory extends Factory
{
    protected $model = CorrectionBreakTime::class;

    public function definition(): array
    {
        return [
            'requested_break_start' => now()->setTime(13, 0),
            'requested_break_end' => now()->setTime(14, 0),
            'original_break_start' => now()->setTime(12, 0),
            'original_break_end' => now()->setTime(13, 0),
        ];
    }
}
