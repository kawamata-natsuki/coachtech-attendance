<?php

namespace App\Services;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
  // 月別勤怠一覧の取得
  public static function getMonthlyAttendances(int $userId, Carbon $month): Collection
  {
    $start = $month->copy()->startOfMonth();
    $end = $month->copy()->endOfMonth();

    return collect(range(0, $start->diffInDays($end)))
      ->map(function ($i) use ($start, $userId) {
        $date = $start->copy()->addDays($i);

        return Attendance::firstOrNew([
          'user_id' => $userId,
          'work_date' => $date->toDateString(),
        ]);
      });
  }
}
