<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class LayoutHelper
{
  public static function headerNavView(): ?string
  {
    // 管理者ログインしている場合は管理者用のナビゲーションメニューを返す
    if (Auth::guard('admin')->check()) {
      return 'components.nav.admin';
    }

    // ユーザーログインしている場合はユーザー用のナビゲーションメニューを返す
    if (Auth::guard('web')->check() &&  optional(Auth::guard('web')->user())->hasVerifiedEmail()) {
      return 'components.nav.user';
    }

    return null;
  }
}
