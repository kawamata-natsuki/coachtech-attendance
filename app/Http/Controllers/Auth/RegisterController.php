<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function showRegisterView()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, CreateNewUser $creator)
    {
        // ユーザー作成・メール認証のメール自動送信
        event(new Registered($user = $creator->create($request->validated())));

        // ログインしてセッション再生成（セキュリティ対策）
        Auth::login($user);
        $request->session()->regenerate();

        // メール認証画面へリダイレクト
        return redirect()->route('verification.notice');
    }
}
