<?php

namespace App\Enums;

enum WorkStatus: string
{
  case OFF = 'off';
  case WORKING = 'working';
  case BREAK = 'break';
  case COMPLETED = 'completed';

  public function label(): string
  {
    return match ($this) {
      self::OFF => '勤務外',
      self::WORKING => '出勤中',
      self::BREAK => '休憩中',
      self::COMPLETED => '退勤済',
    };
  }

  public function is(self $other): bool
  {
    return $this === $other;
  }
}
