<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceRecordController;

// APIバージョン1
Route::prefix('v1')->group(function () {

    // 読み取り系API（認証不要）
    Route::get('/attendance-records', [AttendanceRecordController::class, 'index']);
    Route::get('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);

    // 書き込み系API（Sanctum認証が必要）
    Route::middleware('auth:sanctum')->group(function () {

        // 勤怠を登録
        Route::post('/attendance-records', [AttendanceRecordController::class, 'store']);

        // 勤怠を更新
        Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);

        // 勤怠を削除
        Route::delete('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy']);
    });
});