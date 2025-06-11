<?php

namespace App\Presenters;

use App\Models\Attendance;
use Carbon\CarbonInterval;

class AttendancePresenter
{
  protected Attendance $attendance;

  public function __construct(object $model)
  {
    $this->attendance = $model;
  }

  // 勤怠登録画面の日付表示
  public function workDateForRecord(): string
  {
    $date = $this->attendance->work_date;
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $date->format('Y年m月d日') . '(' . $weekdays[$date->dayOfWeek] . ')';
  }

  // 勤怠一覧画面の日付表示
  public function workDateForIndex(): string
  {
    $date = $this->attendance->work_date;
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $date->format('m/d') . '(' . $weekdays[$date->dayOfWeek] . ')';
  }

  // 出勤時刻をH:i形式で整形
  public function clockInFormatted(): ?string
  {
    return optional($this->attendance->clock_in)->format('H:i');
  }

  // 退勤時刻をH:i形式で整形
  public function clockOutFormatted(): ?string
  {
    return optional($this->attendance->clock_out)->format('H:i');
  }

  // 休憩時間を合計し、H:MM形式に整形
  public function totalBreakTime(): string
  {
    // 休憩時間のトータル秒数を計算
    $total = $this->attendance->breakTimes
      ->filter(fn($bt) => $bt->break_end)
      ->reduce(
        fn($sum, $bt) =>
        $sum + $bt->break_start->diffInSeconds($bt->break_end),
        0
      );
    if ($total === 0) return '';

    // 休憩時間のトータル秒数をH:MM形式に変換
    $interval = CarbonInterval::seconds($total)->cascade();
    $h = $interval->hours;
    return "{$h}:" . str_pad($interval->minutes, 2, '0', STR_PAD_LEFT);
  }

  // 勤務時間を合計し、H:MM形式に整形
  public function totalWorkTime(): string
  {
    // 出勤もしくは退勤が未打刻なら空文字を返す
    if (!$this->attendance->clock_in || !$this->attendance->clock_out) return '';

    // 出勤から退勤までのトータル秒数を計算
    $total = $this->attendance->clock_in->diffInSeconds($this->attendance->clock_out);

    // 休憩時間のトータル秒数を計算
    $break = $this->attendance->breakTimes
      ->filter(fn($bt) => $bt->break_end)
      ->reduce(
        fn($sum, $bt) =>
        $sum + $bt->break_start->diffInSeconds($bt->break_end),
        0
      );

    // 勤務時間の算出
    $net = max(0, $total - $break);

    // 勤務時間のトータル秒数をH:MM形式に変換
    $interval = CarbonInterval::seconds($net)->cascade();
    $h = $interval->hours;
    return "{$h}:" . str_pad($interval->minutes, 2, '0', STR_PAD_LEFT);
  }

  // work_date が未来の日付だったら true、それ以外なら false を返す
  public function isFuture(): bool
  {
    return $this->attendance->work_date->isFuture();
  }
}
