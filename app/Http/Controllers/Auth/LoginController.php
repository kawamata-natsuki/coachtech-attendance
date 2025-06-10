<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // ログイン画面表示
    public function showLoginView()
    {
        return view('auth.login');
    }

    // ログイン処理
    public function store(LoginRequest $request)
    {
        $email = (string) $request->input('email');
        $key = 'login:' . Str::lower($email) . '|' . $request->ip();

        // 1分間に6回以上間違えると60秒間ブロックされる
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts = 5,
            function () use ($request) {
                $credentials = $request->validate([
                    'email' => ['required', 'email'],
                    'password' => ['required'],
                ]);

                if (Auth::attempt($credentials)) {
                    $request->session()->regenerate();
                    return true;
                }

                return false;
            },
            $decaySeconds = 60
        );

        if (! $executed) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "ログイン試行が制限されました。{$seconds}秒後に再度お試しください。",
            ]);
        }

        if ($executed === true) {
            $user = Auth::user();

            if ($user->is_admin) {
                return redirect()->route('admin.attendance.index'); // 管理者用
            }

            return redirect()->route('user.attendance.record'); // 一般ユーザー用
        }

        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }
}
