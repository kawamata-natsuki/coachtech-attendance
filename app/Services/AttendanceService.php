<?php

namespace App\Services;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
  // 空レコードの作成（1か月分）
  public static function generateMonthlyAttendances(int $userId, Carbon $targetMonth): Collection
  {
    $rawAttendances = Attendance::with('breakTimes')
      ->where('user_id', $userId)
      ->whereBetween('work_date', [
        $targetMonth->copy()->startOfMonth(),
        $targetMonth->copy()->endOfMonth(),
      ])
      ->get()
      ->mapWithKeys(fn($item) => [$item->work_date->format('Y-m-d') => $item]);

    $attendances = collect();

    for ($day = 1; $day <= $targetMonth->daysInMonth; $day++) {
      $date = $targetMonth->copy()->day($day)->format('Y-m-d');

      $attendance = $rawAttendances[$date] ?? Attendance::firstOrCreate([
        'user_id' => $userId,
        'work_date' => $date,
      ], [
        'work_status' => WorkStatus::OFF,
      ]);

      $attendances->push($attendance);
    }

    return $attendances;
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
