<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceService
{
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
