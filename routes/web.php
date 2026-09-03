<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AttendanceController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/attendance/list', [AttendanceController::class, 'index']);


Route::get(
    '/attendance/{id}',
    [AttendanceController::class, 'show']
);

Route::post(
    '/attendance/{id}',
    [AttendanceController::class, 'update']
);

Route::get(
    '/stamp_correction_request/list',
    [AdminApplicationController::class, 'index']
);

Route::get('/attendance', [AttendanceController::class, 'register']);

Route::post('/attendance', [AttendanceController::class, 'store']);

Route::get(
    '/stamp_correction_request/approve/{attendance_correct_request_id}',
    [AdminApplicationController::class, 'show']
);

Route::post(
    '/stamp_correction_request/approve/{attendance_correct_request_id}',
    [AdminApplicationController::class, 'approve']
);

