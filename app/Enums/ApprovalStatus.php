<?php

namespace App\Enums;

enum ApprovalStatus: string
{
  case PENDING  = 'pending';
  case APPROVED = 'approved';

  public function label(): string
  {
    return match ($this) {
      self::PENDING  => '承認待ち',
      self::APPROVED => '承認済み',
    };
  }

  public function is(self $other): bool
  {
    return $this === $other;
  }
}
