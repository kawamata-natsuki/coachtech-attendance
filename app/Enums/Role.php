<?php

namespace App\Enums;

enum Role: string
{
  case ADMIN = 'admin';
  case USER  = 'user';

  public function label(): string
  {
    return match ($this) {
      self::ADMIN => '管理者',
      self::USER  => '一般ユーザー',
    };
  }
}
