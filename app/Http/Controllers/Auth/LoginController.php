<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
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

        // ログイン試行
        if (! Auth::attempt($request->only('email', 'password'))) {
            // ログイン失敗カウント
            RateLimiter::hit($this->throttleKey($request), 60);

            // ログイン失敗時のエラーメッセージ
            throw ValidationException::withMessages([
                'login' => 'ログイン情報が登録されていません',
            ]);
        }

        // ログイン成功後の処理
        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ロール確認：アクセス元URLに応じてログイン許可を絞る
        if (
            $request->is('admin/login') && $user->role !== Role::ADMIN
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'login' => 'このログイン画面は管理者専用です',
            ]);
        }

        if (
            $request->is('login') && $user->role === Role::ADMIN
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'login' => '管理者は管理者専用のログイン画面からログインしてください',
            ]);
        }

        // メール未認証の確認
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // ログインユーザーのリダイレクト先をを指定
        return redirect()->route(
            $request->input('login_type') === 'admin'
                ? 'admin.attendances.index'
                : 'user.attendances.record'
        );
    }

    // ログアウト処理
    public function logout(Request $request)
    {
        $user = Auth::user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user->role === Role::ADMIN) {
            return redirect()->route('admin.login');
        } else {
            return redirect()->route('user.login');
        }
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
