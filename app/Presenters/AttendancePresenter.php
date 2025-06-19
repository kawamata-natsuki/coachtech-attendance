<?php

namespace App\Presenters;

use App\Services\AttendanceService;
use App\Models\Attendance;
use Carbon\CarbonInterval;

class AttendancePresenter
{
  protected Attendance $attendance;

  public function __construct(Attendance $attendance)
  {
    $this->attendance = $attendance;
  }

  // 勤怠登録画面の日付表示:2025年6月20日(金)
  public function workDateForRecord(): string
  {
    return $this->attendance->work_date
      ->locale('ja')
      ->isoFormat('YYYY年M月D日(dd)');
  }

  // 勤怠一覧画面の日付表示:06/20(金)
  public function workDateForIndex(): string
  {
    return $this->attendance->work_date
      ->locale('ja')
      ->isoFormat('MM/DD(dd)');
  }

  // 出勤時刻をH:i形式に整形
  public function clockInFormatted(): ?string
  {
    return optional($this->attendance->clock_in)->format('H:i');
  }

  // 退勤時刻をH:i形式に整形
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
    return sprintf('%d:%02d', $interval->hours, $interval->minutes);
  }

  // 勤務時間を合計し、H:MM形式に整形
  public function totalWorkTime(): string
  {
    // 出勤から退勤までのトータル秒数を計算
    $seconds = AttendanceService::calculateWorkTime($this->attendance);
    if ($seconds === 0) return '';

    // 勤務時間のトータル秒数をH:MM形式に変換
    $interval = CarbonInterval::seconds($seconds)->cascade();
    return sprintf('%d:%02d', $interval->hours, $interval->minutes);
  }

  // Bladeで「詳細」リンクを有効にするかどうかの判定用
  public function isViewable(): bool
  {
    return !is_null($this->attendance->id)
      && $this->attendance->work_date->lte(now());
  }
}
