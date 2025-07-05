<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\CarbonInterval;
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

  //  休憩時間を合計し、H:MM形式に整形
  public static function getBreakTimeSeconds(Attendance $attendance): int
  {
    return $attendance->breakTimes
      ->sum(function ($break) {
        if ($break->break_start && $break->break_end) {
          return $break->break_end->diffInSeconds($break->break_start);
        }
        return 0;
      });
  }
  public static function calculateBreakTime(Attendance $attendance): string
  {
    if (! $attendance->exists) return '';

    // 出勤打刻があれば休憩の合計計算
    if (!$attendance->clock_in) {
      return '';
    }

    $seconds = self::getBreakTimeSeconds($attendance);

    // 休憩がゼロでも「00:00」と表示
    if ($seconds === 0) {
      return '00:00';
    }

    $interval = CarbonInterval::seconds($seconds)->cascade();
    return sprintf('%d:%02d', $interval->hours, $interval->minutes);
  }

  //  勤務時間を合計し、H:MM形式に整形
  public static function getWorkTimeSeconds(Attendance $attendance): int
  {
    if (!$attendance->clock_in || !$attendance->clock_out) {
      return 0;
    }

    return $attendance->clock_out->diffInSeconds($attendance->clock_in)
      - self::getBreakTimeSeconds($attendance);
  }
  public static function calculateWorkTime(Attendance $attendance): string
  {
    if (! $attendance->exists) return '';

    // 出勤 or 退勤が欠けているときは欠けている場合は空欄
    if (!$attendance->clock_in || !$attendance->clock_out) {
      return '';
    }

    $seconds = self::getWorkTimeSeconds($attendance);

    $interval = CarbonInterval::seconds($seconds)->cascade();
    return sprintf('%d:%02d', $interval->hours, $interval->minutes);
  }
}
