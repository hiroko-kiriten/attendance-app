<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApplicationListMiddleware;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('welcome');
});

// 一般ユーザー
Route::middleware(['auth', 'verified'])->group(function () {

Route::get('/attendance', [AttendanceController::class, 'register']);
Route::post('/attendance', [AttendanceController::class, 'store']);

Route::get('/attendance/list', [AttendanceController::class, 'index']);

Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show']);

// マイ勤怠レポート
Route::get('/attendance/report',[ReportController::class, 'index'])->name('attendance.report');

Route::get('/attendance/{id}', [AttendanceController::class, 'show']);

Route::post('/attendance/{id}', [AttendanceController::class, 'update']);

Route::get(
        '/stamp_correction_request/list',
        [ApplicationController::class, 'index']
    )->middleware(ApplicationListMiddleware::class);
}); 

// 管理者

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get(
        '/admin/attendance/list',
        [AdminAttendanceController::class, 'index']
    );

    Route::get(
    '/admin/stamp_correction_request/list',
    [AdminApplicationController::class, 'index']
);

    Route::get(
        '/admin/attendance/{id}',
        [AdminAttendanceController::class, 'show']
    )->name('admin.attendance.detail');

    Route::post(
        '/admin/attendance/{id}',
        [AdminAttendanceController::class, 'update']
    );

    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'show']
    );

    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'approve']
    );

    Route::get(
    '/admin/staff/list',
    [AdminStaffController::class, 'index']
);

    Route::get(
    '/admin/attendance/staff/{id}',
    [AdminStaffController::class, 'show']
);

    // スタッフ別月次勤怠一覧をCSV出力
Route::get(
    '/admin/attendance/staff/{id}/csv',
    [AdminStaffController::class, 'csv']
);

});

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm']);

Route::post('/admin/login', [AdminLoginController::class, 'login']);