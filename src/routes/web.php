<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// ===============================
// 認証関連ルート
// ===============================

// ユーザー登録
Route::get('/register', [RegisterController::class, 'showRegisterView'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
// ログイン
Route::get('/login', [LoginController::class, 'showLoginView'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
// ログアウト
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// メール認証

Route::prefix('email')->name('verification.')->middleware('auth')->group(function () {
    Route::get('/verify', [EmailVerificationController::class, 'notice'])->name('notice');
    Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verify');
    Route::post('/verification-notification', [EmailVerificationController::class, 'resend'])
        ->name('send');
    Route::get('/check', [EmailVerificationController::class, 'check'])
        ->name('check');
});

// ===============================
// ユーザールート（要ログイン）
// ===============================

// 出勤登録画面 
Route::get('/attendance', [AttendanceController::class, 'record'])->name('attendance.record');

// ===============================
// 管理者ルート（要ログイン）
// ===============================