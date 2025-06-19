<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

// 	Guardチェック、ログイン判定
class AuthHelper
{
  // 現在のリクエストが管理者ログインページかどうかを判定
  public static function isAdminLoginPage(): bool
  {
    return Request::is('admin/login');
  }

  // 現在のリクエストが管理者用ルートかどうか（admin/*）
  public static function isAdminRoute(): bool
  {
    return Request::is('admin/*');
  }
}
