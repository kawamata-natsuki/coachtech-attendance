<?php

namespace App\Presenters;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Support\Collection;
use App\Services\AttendanceService;
use Carbon\CarbonInterval;

class AttendancePresenter
{
  protected Attendance $attendance;
  protected ?CorrectionRequest $correctionRequest = null;

  public function __construct(Attendance $attendance, ?CorrectionRequest $correctionRequest = null)
  {
    $this->attendance = $attendance;
    $this->correctionRequest = $correctionRequest;
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

  public function requestedClockIn(): ?string
  {
    return optional($this->correctionRequest?->requested_clock_in ?? $this->attendance->clock_in)->format('H:i');
  }

  public function requestedClockOut(): ?string
  {
    return optional($this->correctionRequest?->requested_clock_out ?? $this->attendance->clock_out)->format('H:i');
  }

  public function isCorrectionDisabled(): bool
  {
    return $this->correctionRequest !== null;
  }

  public function displayReason(): string
  {
    return old('reason', $this->correctionRequest->reason ?? '');
  }

  public function breaks(): Collection
  {
    return $this->correctionRequest
      ? $this->correctionRequest->correctionBreakTimes
      : $this->attendance->breakTimes;
  }

  public function nextBreakIndex(): int
  {
    return $this->breaks()->count();
  }

  public function workDateForShow(): string
  {
    return $this->attendance->work_date->format('Y年n月j日');
  }
}
