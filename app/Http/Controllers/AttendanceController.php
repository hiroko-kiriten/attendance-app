<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function store(StoreAttendanceRequest $request)
{
    $user = auth()->user();

     $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
        ->whereDate('date', now()->toDateString())
        ->first();

    if ($request->action === 'clock_in') {
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
        ]);

        $user->attendance_status = '出勤中';
        $user->save();
    }

      if ($request->action === 'clock_out') {
        $attendanceRecord->update([
            'clock_out' => now(),
        ]);

        $user->attendance_status = '退勤済';
        $user->save();
    }

    if ($request->action === 'break_in') {
        $attendanceRecord->breaks()->create([
            'break_in' => now(),
        ]);

        $user->attendance_status = '休憩中';
        $user->save();
    }

    if ($request->action === 'break_out') {
        $break = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        if ($break) {
            $break->update([
                'break_out' => now(),
            ]);
        }

        $user->attendance_status = '出勤中';
        $user->save();
    }

    return redirect('/attendance/list');
}

    public function register()
{
    $user = auth()->user();

    return view('user.attendance-register', [
        'user' => $user,
        'formattedDate' => now()->format('Y年m月d日'),
        'formattedTime' => now()->format('H:i'),
    ]);
}

    public function index(Request $request)
    {
        // 表示する年月を取得
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::now();

        // ログインユーザーの指定月の勤怠を取得
        $attendanceRecords = AttendanceRecord::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();

        // 前月・翌月
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 一覧表示用に整形
        $formattedAttendanceRecords = $attendanceRecords->map(function ($attendanceRecord) {
            return [
                'id' => $attendanceRecord->id,
                'date' => Carbon::parse($attendanceRecord->date)->format('m/d'),
                'clock_in' => $attendanceRecord->clock_in
                    ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendanceRecord->clock_out
                    ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                    : '',
                'total_break_time' => $attendanceRecord->total_break_time,
                'total_time' => $attendanceRecord->total_time,
            ];
        });

        return view('user.user-attendance-list', compact(
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }

    public function show($id)
{
    $attendanceRecord = AttendanceRecord::with('breaks')
        ->findOrFail($id);

    $user = $attendanceRecord->user;

    $application = AttendanceCorrectionRequestModel::where(
        'attendance_record_id',
        $attendanceRecord->id
    )
    ->where('approval_status', '承認待ち')
    ->first();

    $breaks = $attendanceRecord->breaks;

    $data = [
        'id' => $attendanceRecord->id,
        'year' => Carbon::parse($attendanceRecord->date)->format('Y年'),
        'date' => Carbon::parse($attendanceRecord->date)->format('n月j日'),
        'clock_in' => $attendanceRecord->clock_in
            ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
            : '',
        'clock_out' => $attendanceRecord->clock_out
            ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
            : '',
        'comment' => $attendanceRecord->comment,
        'application' => $application,
        'breaks' => $breaks->map(function ($break) {
            return [
                'break_in' => $break->break_in
                    ? Carbon::parse($break->break_in)->format('H:i')
                    : '',
                'break_out' => $break->break_out
                    ? Carbon::parse($break->break_out)->format('H:i')
                    : '',
            ];
        })->toArray(),
    ];

        return view('user.user-detail', compact(
            'data',
            'user'
        ));
}

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        $application = AttendanceCorrectionRequestModel::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => auth()->id(),
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
            'new_date' => $request->new_date,
            'approval_status' => '承認待ち',
            'application_date' => now()->toDateString(),
        ]);

        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);

        foreach ($breakIns as $index => $breakIn) {
            $breakOut = $breakOuts[$index] ?? null;

            // 両方空欄なら登録しない
            if (empty($breakIn) && empty($breakOut)) {
                continue;
            }

            $application->attendanceCorrectionRequestBreaks()->create([
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        }

        return redirect('/attendance/list');
    }
}