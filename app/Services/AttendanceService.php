<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class AttendanceService
{
  public static function calculateBreakDuration(Attendance $attendance): string
  {
    // 休憩時間を
    $minutes = $attendance->breakTimes->sum(
      fn($bt) =>
      // break_endにデータがあれば、
      $bt->break_end
        ? Carbon::parse($bt->break_start)->diffInMinutes($bt->break_end)
        : 0
    );

    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    return "{$hours}:" . str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT);
  }

  public static function calculateWorkDuration(Attendance $attendance): string
  {
    if (!$attendance->clock_in || !$attendance->clock_out) return '';

    $total = Carbon::parse($attendance->clock_in)->diffInSeconds($attendance->clock_out);
    $break = $attendance->breakTimes->sum(fn($bt) => $bt->break_end
      ? Carbon::parse($bt->break_start)->diffInSeconds($bt->break_end)
      : 0);

    $net = max(0, $total - $break);
    $interval = CarbonInterval::seconds($net)->cascade();
    return "{$interval->hours}:" . str_pad($interval->minutes, 2, '0', STR_PAD_LEFT);
  }
}
