<?php

namespace App\Presenters;

use App\Models\Attendance;
use App\Services\AttendanceService;
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
    $seconds = AttendanceService::calculateBreakTime($this->attendance);
    if ($seconds === 0) return '';

    // 休憩時間のトータル秒数をH:MM形式に変換
    $interval = CarbonInterval::seconds($seconds)->cascade();
    return "{$interval->hours}:" . str_pad($interval->minutes, 2, '0', STR_PAD_LEFT);
  }

  // 勤務時間を合計し、H:MM形式に整形
  public function totalWorkTime(): string
  {
    // 出勤から退勤までのトータル秒数を計算
    $seconds = AttendanceService::calculateWorkTime($this->attendance);
    if ($seconds === 0) return '';

    // 勤務時間のトータル秒数をH:MM形式に変換
    $interval = CarbonInterval::seconds($seconds)->cascade();
    return "{$interval->hours}:" . str_pad($interval->minutes, 2, '0', STR_PAD_LEFT);
  }
}
