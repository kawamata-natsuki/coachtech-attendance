<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;
use App\Http\Controllers\User\CorrectionRequestController as UserCorrectionRequestController;
use Illuminate\Support\Facades\Route;

// 確認！パス名だぶりのとこ！
// /attendance/{id}
// /stamp_correction_request/list

// ===============================
// 認証ルート（要ログイン）
// ===============================

Route::get('/register', [RegisterController::class, 'showRegisterView'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

Route::get('/login', [LoginController::class, 'showLoginView'])->name('user.login');
Route::post('/login', [LoginController::class, 'store']);
Route::get('/admin/login', [LoginController::class, 'showLoginView'])->name('admin.login');
Route::post('admin/login', [LoginController::class, 'store']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

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
Route::middleware(['auth', 'verified'])
    ->name('user.')
    ->group(function () {
        Route::get('/attendance', [UserAttendanceController::class, 'record'])->name('attendances.record');
        Route::post('/attendance', [UserAttendanceController::class, 'store'])->name('attendances.store');

        Route::get('/attendance/list', [UserAttendanceController::class, 'index'])->name('attendances.index');

        Route::get('/attendance/{id}', [UserAttendanceController::class, 'show'])->name('attendances.show');
        Route::post('/attendance/{id}', [UserAttendanceController::class, 'update'])->name('attendances.update');

        Route::get('/stamp_correction_request/list', [UserCorrectionRequestController::class, 'index'])->name('correction-requests.index');
    });

// ===============================
// 管理者ルート（要ログイン）
// ===============================


//Route::middleware(['auth', 'verified', 'admin'])
//    ->name('admin.')
//    ->group(function () {
//        // 勤怠一覧画面
//        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.index');

//        // 勤怠詳細画面
//        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
//        Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
//
//// スタッフ一覧画面
//Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('staff.index');

// スタッフ別勤怠一覧画面
//Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('attendance.staff');
//Route::get('/admin/attendance/staff/{id}/export', [AdminAttendanceController::class, 'exportCsv'])->name('attendance.export');

// 申請一覧画面
//Route::get('/admin/stamp_correction_request/list', [AdminCorrectionRequestController::class, 'index'])->name('correction-request.index');

// 修正申請承認画面
//Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'show'])->name('correction-request.show');
//Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'approve'])->name('correction-request.approve');
//    });