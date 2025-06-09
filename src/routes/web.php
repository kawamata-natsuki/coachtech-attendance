<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// ===============================
// 認証関連ルート
// ===============================

// ログイン
Route::get('/login', [LoginController::class, 'showLoginView'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

// ログアウト
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ===============================
// ユーザールート（要ログイン）
// ===============================

// 出勤登録画面 
Route::get('/attendance', [AttendanceController::class, 'record'])->name('attendance.record');

// ===============================
// 管理者ルート（要ログイン）
// ===============================