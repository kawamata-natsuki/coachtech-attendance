<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;
use App\Http\Controllers\User\CorrectionRequestController as UserCorrectionRequestController;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// ===============================
// 認証ルート
// ===============================

// 一般ユーザー
Route::get('/register', [RegisterController::class, 'showRegisterView'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/login', [LoginController::class, 'showLoginView'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

// 管理者
Route::get('/admin/login', [LoginController::class, 'showLoginView'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'store'])->name('admin.login.store');

// ログアウト（共通）
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// 一般ユーザー用メール認証
Route::prefix('email')->name('verification.')->middleware('auth:web')->group(function () {
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
Route::middleware(['auth:web', 'verified'])
    ->name('user.')
    ->group(function () {
        Route::get('/attendance', [UserAttendanceController::class, 'record'])->name('attendances.record');
        Route::post('/attendance', [UserAttendanceController::class, 'store'])->name('attendances.store');

        Route::get('/attendance/list', [UserAttendanceController::class, 'index'])->name('attendances.index');
    });

// ===============================
// 管理者ルート（要ログイン）
// ===============================

Route::middleware(['auth:admin', 'verified'])
    ->name('admin.')
    ->group(function () {
        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendances.index');

        Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('staff.index');

        Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('attendances.staff');
        Route::get('/admin/attendance/staff/{id}/export', [AdminAttendanceController::class, 'exportCsv'])->name('attendances.export');

        // 修正申請承認画面
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'show'])->name('correction-requests.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'approve'])->name('correction-requests.approve');
    });

// ===============================
// 共通ルート（要ログイン）
// ===============================

// 勤怠詳細画面（共通パス）
Route::middleware(['auth', 'verified'])->get('/attendance/{id}', function (Request $request, $id) {
    if (Auth::guard('admin')->check()) {
        $controller = AdminAttendanceController::class;
    } elseif (Auth::guard('web')->check()) {
        $controller = UserAttendanceController::class;
    } else {
        abort(403);
    }
    return app($controller)->show($request, $id);
})->name('attendances.show');

Route::middleware(['auth', 'verified'])->put('/attendance/{id}', function (AttendanceCorrectionRequest $request, $id) {
    if (Auth::guard('admin')->check()) {
        $controller = AdminAttendanceController::class;
    } elseif (Auth::guard('web')->check()) {
        $controller = UserAttendanceController::class;
    } else {
        abort(403);
    }
    return app($controller)->update($request, $id);
})->name('attendances.update');

Route::middleware(['auth', 'verified'])->get('/stamp_correction_request/list', function (Request $request) {
    if (Auth::guard('admin')->check()) {
        $controller = AdminCorrectionRequestController::class;
    } elseif (Auth::guard('web')->check()) {
        $controller = UserCorrectionRequestController::class;
    } else {
        abort(403);
    }
    return app($controller)->index($request);
})->name('correction-requests.index');
