<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        // 試行回数制限チェック
        if ($request->hasTooManyLoginAttempts()) {
            $request->fireLockoutEvent();
    
            throw ValidationException::withMessages([
                'email' => ['ログインに複数回失敗しました。しばらくしてから再度お試しください。'],
            ])->status(429);
        }
    
        $credentials = $request->only('email', 'password');
    
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
    
            // 成功したら試行回数リセット
            $request->clearLoginAttempts();
    
            return redirect()->route('attendance');
        }
    
        // 失敗したら試行回数カウント＋エラー
        $request->incrementLoginAttempts();
    
        return back()->withErrors([
            'email' => '認証に失敗しました。',
        ])->onlyInput('email');
    }

    // ログアウト処理
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
