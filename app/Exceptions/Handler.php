<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
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
        $guard = auth('admin')->check() ? 'admin' : 'web';

        if ($exception instanceof TokenMismatchException) {
            return redirect()->guest(route($guard === 'admin' ? 'admin.login' : 'login'))
                ->with('error', 'セッションの有効期限が切れました。再度ログインしてください。');
        }

        return parent::render($request, $exception);
    }
}
