<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminLoginController;

Route::get('/', function () {
    return view('welcome');
});

// 一般ユーザー
Route::middleware('auth')->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'register']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    Route::get('/attendance/list', [AttendanceController::class, 'index']);

    Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);

    Route::get(
        '/stamp_correction_request/list',
        [ApplicationController::class, 'index']
    );
});

// 管理者

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get(
        '/admin/attendance/list',
        [AdminAttendanceController::class, 'index']
    );

    Route::get(
        '/admin/attendance/{id}',
        [AdminAttendanceController::class, 'show']
    );

    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'show']
    );

    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'approve']
    );
});

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm']);

Route::post('/admin/login', [AdminLoginController::class, 'login']);