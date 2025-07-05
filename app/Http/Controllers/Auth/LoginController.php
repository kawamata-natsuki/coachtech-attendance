<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Admin;
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
        // 1分間に5回以上のログイン試行があった場合、制限をかける
        $this->ensureIsNotRateLimited($request);

        // 管理者ログインか一般ユーザーログインかを判定
        $guard = $request->is('admin/*') ? 'admin' : 'web';

        // 不要なガードをログアウトしてセッションをクリーンにする
        if ($guard === 'admin') {
            Auth::guard('web')->logout();
        } else {
            Auth::guard('admin')->logout();
        }

        // ログイン試行
        if (! Auth::guard($guard)->attempt($request->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'login' => 'ログイン情報が登録されていません',
            ]);
        }

        // 認証済ユーザーの取得
        $user = Auth::guard($guard)->user();

        // 管理者画面で一般ユーザーがログインしていないかを確認
        if ($guard === 'admin' && ! $user instanceof Admin) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['login' => 'このログイン画面は管理者専用です']);
        }

        // ログイン成功後の処理
        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        // メール未認証ユーザーの場合は認証メール送信
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
        // 現在ログイン中のガードを判定（管理者 or ユーザー）
        $guard = Auth::guard('admin')->check() ? 'admin' : 'web';

        // ログアウト処理
        Auth::guard($guard)->logout();

        // セッションを無効化してCSRFトークンを再生成
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後のリダイレクト先
        return redirect()->route($guard === 'admin' ? 'admin.login' : 'login');
    }

    // ログインの試行回数を制限する処理
    protected function ensureIsNotRateLimited(Request $request): void
    {
        // 1分間に5回以上のログイン試行があった場合、制限をかける
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        // ログイン制限に達している場合は、再試行可能になるまでの残り秒数を取得
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        // ログイン制限中の場合のエラーメッセージ
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
