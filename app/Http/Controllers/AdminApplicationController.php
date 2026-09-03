<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCorrectionRequest;

class AdminApplicationController extends Controller
{
    public function index()
    {
        $applications = AttendanceCorrectionRequest::with([
            'user',
            'attendanceRecord'
        ])->get();

        return view('admin.admin-application-list', compact('applications'));
    }

    public function show($attendance_correct_request_id)
    {
        $application = AttendanceCorrectionRequest::with([
            'user',
            'attendanceCorrectionRequestBreaks'
        ])->findOrFail($attendance_correct_request_id);

        $user = $application->user;

        return view('admin.admin-application-detail', compact(
            'application',
            'user'
        ));
    }

    public function approve($attendance_correct_request_id)
    {
        DB::transaction(function () use ($attendance_correct_request_id) {
            $application = AttendanceCorrectionRequest::with([
                'attendanceRecord',
                'attendanceCorrectionRequestBreaks'
            ])->findOrFail($attendance_correct_request_id);

            $attendanceRecord = $application->attendanceRecord;

            // 勤怠情報を修正申請の内容で更新
            $attendanceRecord->update([
                'date' => $application->new_date,
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 既存の休憩を削除
            $attendanceRecord->breaks()->delete();

            // 修正申請された休憩を登録
            foreach ($application->attendanceCorrectionRequestBreaks as $requestBreak) {
                $attendanceRecord->breaks()->create([
                    'break_in' => $requestBreak->break_in,
                    'break_out' => $requestBreak->break_out,
                ]);
            }

            // 申請を承認済みに変更
            $application->approval_status = '承認済み';
            $application->save();
        });

        return redirect('/stamp_correction_request/list');
    }
}