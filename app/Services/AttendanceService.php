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

  // 休憩時間の合計
  public static function calculateBreakTime(Attendance $attendance): int
  {
    return $attendance->breakTimes
      ->filter(fn($bt) => $bt->break_end)
      ->reduce(
        fn($sum, $bt) => $sum + $bt->break_start->diffInSeconds($bt->break_end),
        0
      );
  }

  // 勤務時間の合計
  public static function calculateWorkTime(Attendance $attendance): int
  {
    if (!$attendance->clock_in || !$attendance->clock_out) return 0;

    $total = $attendance->clock_in->diffInSeconds($attendance->clock_out);
    $break = self::calculateBreakTime($attendance);
    return max(0, $total - $break);
  }
}
