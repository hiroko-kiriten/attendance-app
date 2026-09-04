<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{

    public function store(StoreAttendanceRequest $request)
{
    $user = auth()->user();

     $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
        ->whereDate('date', now()->toDateString())
        ->first();

    if ($request->action === 'clock_in') {
         if (!$attendanceRecord) {    
            AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
        ]);

        }
    }

if ($request->action === 'clock_out') {
    if (
        $attendanceRecord &&
        $attendanceRecord->clock_in &&
        !$attendanceRecord->clock_out &&
        !$attendanceRecord->breaks()
            ->whereNull('break_out')
            ->exists()
    ) {
        $clockOut = now();
        $clockIn = Carbon::parse($attendanceRecord->clock_in);

        // 出勤から退勤までの経過時間
        $workSeconds = $clockIn->diffInSeconds($clockOut);

        // 合計休憩時間
        $totalBreakSeconds = 0;

        if ($attendanceRecord->total_break_time) {
            [$hours, $minutes, $seconds] = array_map(
                'intval',
                explode(':', $attendanceRecord->total_break_time)
            );

            $totalBreakSeconds =
                ($hours * 3600) +
                ($minutes * 60) +
                $seconds;
        }

        // 実働時間 ＝ 経過時間 − 休憩時間
        $totalTimeSeconds = $workSeconds - $totalBreakSeconds;

        $hours = floor($totalTimeSeconds / 3600);
        $minutes = floor(($totalTimeSeconds % 3600) / 60);
        $seconds = $totalTimeSeconds % 60;

        $attendanceRecord->update([
            'clock_out' => $clockOut,
            'total_time' => sprintf(
                '%02d:%02d:%02d',
                $hours,
                $minutes,
                $seconds
            ),
        ]);
    }
}

    if ($request->action === 'break_in') {
         if (
        $attendanceRecord &&
        !$attendanceRecord->clock_out &&
        !$attendanceRecord->breaks()
            ->whereNull('break_out')
            ->exists()
    ) {
        $attendanceRecord->breaks()->create([
            'break_in' => now(),
        ]);
    }
    }    

   if ($request->action === 'break_out') {
    if ($attendanceRecord && !$attendanceRecord->clock_out) {
        $break = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        if ($break) {
    $break->update([
        'break_out' => now(),
    ]);

    $totalBreakSeconds = 0;

    foreach ($attendanceRecord->breaks()->get() as $breakRecord) {
        if ($breakRecord->break_in && $breakRecord->break_out) {
            $breakIn = Carbon::parse($breakRecord->break_in);
            $breakOut = Carbon::parse($breakRecord->break_out);

            $totalBreakSeconds += $breakIn->diffInSeconds($breakOut);
        }
    }

    $hours = floor($totalBreakSeconds / 3600);
    $minutes = floor(($totalBreakSeconds % 3600) / 60);
    $seconds = $totalBreakSeconds % 60;

    $attendanceRecord->update([
        'total_break_time' => sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        ),
    ]);
}
    }
}
    return redirect('/attendance/list');
}

    public function register()
{
    $user = auth()->user();

     $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
        ->whereDate('date', now()->toDateString())
        ->with('breaks')
        ->first();

    if (!$attendanceRecord) {
        $attendanceStatus = '勤務外';
    } elseif ($attendanceRecord->clock_out) {
        $attendanceStatus = '退勤済';
    } elseif ($attendanceRecord->breaks->contains(function ($break) {
        return $break->break_in && !$break->break_out;
    })) {
        $attendanceStatus = '休憩中';
    } else {
        $attendanceStatus = '出勤中';
    }

    // 完成品のBladeが $user->attendance_status を使うため、
    // 画面表示用に一時的に値を設定する
    $user->attendance_status = $attendanceStatus;

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