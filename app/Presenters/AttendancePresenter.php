<?php

namespace App\Presenters;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendancePresenter
{
  protected Attendance $attendance;

  public function __construct(Attendance $attendance)
  {
    $this->attendance = $attendance;
  }

  // 勤怠登録画面の日付表示
  public function workDateForRecord(): string
  {
    $date = $this->attendance->work_date;
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $date->format('Y年n月j日') . '(' . $weekdays[$date->dayOfWeek] . ')';
  }
}
