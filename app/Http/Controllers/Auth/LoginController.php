<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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
        // アクセス制限の確認
        // 1分間に5回以上のログイン試行があった場合、制限をかける

        $this->ensureIsNotRateLimited($request);
        $guard = $request->is('admin/*') ? 'admin' : 'web';


        // ログイン試行
        if (! Auth::guard($guard)->attempt($request->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey($request), 60);

            // ログイン失敗時のエラーメッセージ
            throw ValidationException::withMessages([
                'login' => 'ログイン情報が登録されていません',
            ]);
        }

        $user = Auth::guard($guard)->user();
        if ($guard === 'admin' && ! $user instanceof \App\Models\Admin) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['login' => 'このログイン画面は管理者専用です']);
        }

        // ログイン成功後の処理
        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        // メール未認証の確認
        if (! optional($user)->hasVerifiedEmail()) {
            optional($user)->sendEmailVerificationNotification();
        }

        // ログイン後のリダイレクト先
        return redirect()->route(
            $guard === 'admin'
                ? 'admin.attendances.index'
                : 'user.attendances.record'
        );
    }

    // ログアウト処理
    public function logout(Request $request)
    {
        $guard = Auth::guard('admin')->check() ? 'admin' : 'web';

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($guard === 'admin' ? 'admin.login' : 'user.login');
    }

    // ログインの試行回数を制限する処理
    protected function ensureIsNotRateLimited(Request $request): void
    {
        // 1分間に5回以上のログイン試行があった場合、制限をかける
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "ログイン試行が制限されました。{$seconds}秒後に再度お試しください。",
        ]);
    }

    // レート制限のキー(email+IP)を生成する
    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
