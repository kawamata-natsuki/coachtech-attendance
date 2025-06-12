<?php

namespace App\Services;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
  // 空レコードの作成（今月分）
  public static function generateThisMonthAttendances(int $userId, Carbon $currentMonth): Collection
  {
    // 今月の日付範囲に合わせて勤怠レコードを取得
    $existingAttendances = Attendance::where('user_id', $userId)
      ->whereBetween('work_date', [
        $currentMonth->copy()->startOfMonth(),
        $currentMonth->copy()->endOfMonth(),
      ])
      ->get(); // get() を使ってレコードを取得

    $attendances = collect();

    // 今月の日付をループして、まだ作成されていないレコードだけ作成
    for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
      $date = $currentMonth->copy()->day($day)->format('Y-m-d');

      // 現在のレコードが存在するかどうかを確認
      $attendance = $existingAttendances->firstWhere('work_date', $date);

      if (!$attendance) {
        // その日付にレコードがなければ作成
        $attendance = Attendance::updateOrCreate(
          [
            'user_id' => $userId,
            'work_date' => $date,
          ],
          [
            'work_status' => WorkStatus::OFF,
          ]
        );
      }

      // 勤怠レコードをコレクションに追加
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
