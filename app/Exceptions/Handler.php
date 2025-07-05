<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // 現在ログイン中のガードを判定（管理者 or ユーザー）
        $guard = Auth::guard('admin')->check() ? 'admin' : 'web';

        if ($this->isHttpException($exception)) {
            if ($exception->getStatusCode() === 419) {
                return redirect()->route($guard === 'admin' ? 'admin.login' : 'login')->with('error', 'セッションの有効期限が切れました。再度ログインしてください。');
            }
        }

        return parent::render($request, $exception);
    }
}
