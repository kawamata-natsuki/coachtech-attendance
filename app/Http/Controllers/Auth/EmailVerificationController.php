<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller
{
    // メール認証画面の表示
    public function notice()
    {
        return view('auth.verify-email');
    }

    // メール認証の処理
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('user.attendances.record');
    }

    // 認証メール再送信
    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    }

    // メール認証済みか確認してリダイレクト
    public function check()
    {
        $user = auth('web')->user();

        return optional($user)->hasVerifiedEmail()
            ? redirect()->route('user.attendances.record')
            : redirect()->away('https://mailtrap.io/');
    }
}
