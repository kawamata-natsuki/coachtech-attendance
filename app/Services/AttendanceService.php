<?php

namespace App\Services;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
  // 空レコードの作成（今月分）
  public static function generateThisMonthAttendances(int $userId, Carbon $currentMonth): Collection
  {
    $user = User::findOrFail($userId);

    $existingAttendances = Attendance::where('user_id', $userId)
      ->whereBetween('work_date', [
        $currentMonth->copy()->startOfMonth(),
        $currentMonth->copy()->endOfMonth(),
      ])
      ->get();

    // ここで入社日を取得
    $joiningDate = auth()->user()->joining_date;

    $attendances = collect();

    // 未来レコードの上限：今から3ヶ月先まで
    $maxFuture = now()->copy()->addMonth()->endOfMonth();

    for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
      $date = $currentMonth->copy()->day($day);

      $attendance = $existingAttendances->firstWhere('work_date', $date);

      // 条件：翌月末まで、かつ 入社日以降は、システム上未来のレコードを作成しているが、実際は勤務前のためダミーデータ
      if (!$attendance && $date->lte($maxFuture) && $date->gte($joiningDate)) {
        $attendance = Attendance::create([
          'user_id' => $userId,
          'work_date' => $date,
          'work_status' => WorkStatus::OFF,
          'is_dummy' => true,
        ]);
      }

      // それ以外（入社前や3ヶ月以上未来）は日付だけ表示
      if (!$attendance) {
        $attendance = new Attendance([
          'user_id' => $userId,
          'work_date' => $date,
          'work_status' => WorkStatus::OFF,
          'is_dummy' => true,
        ]);
      }

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
