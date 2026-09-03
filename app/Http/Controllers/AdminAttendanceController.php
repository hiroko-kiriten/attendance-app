<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 表示する日付を決める
        $date = $request->date
            ? Carbon::parse($request->date)
            : now();

        // 前日・翌日を取得
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 全ユーザーを取得
        $users = User::all();

        // 指定した日の勤怠記録を取得
        $attendanceRecords = AttendanceRecord::where('date', $date->format('Y-m-d'))->get();

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    public function show($id)
    {
        $attendanceRecord = AttendanceRecord::find($id);

        $user = $attendanceRecord->user;

        $breaks = $attendanceRecord->breaks;

        return view('admin.admin-detail', compact(
            'attendanceRecord',
            'user',
            'breaks'
        ));
    }
}