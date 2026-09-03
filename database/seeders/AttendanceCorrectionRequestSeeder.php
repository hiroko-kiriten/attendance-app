<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();

        // user1の勤怠データを取得
        $attendances = AttendanceRecord::where('user_id', $user1->id)
            ->orderBy('date')
            ->get();

        // 勤怠データが存在することを確認
        if ($attendances->count() < 2) {
            return;
        }

        // 承認待ちの修正申請
        $pendingAttendance = $attendances->first();

        $pendingRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $pendingAttendance->id,
            'user_id' => $user1->id,
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:00:00',
            'comment' => '出勤時刻の修正をお願いします。',
            'approval_status' => '承認待ち',
            'new_date' => $pendingAttendance->date,
            'application_date' => now()->toDateString(),
        ]);

        AttendanceCorrectionRequestBreak::create([
            'attendance_correction_request_id' => $pendingRequest->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 承認済みの修正申請
        $approvedAttendance = $attendances->get(1);

        $approvedRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $approvedAttendance->id,
            'user_id' => $user1->id,
            'new_clock_in' => '08:30:00',
            'new_clock_out' => '18:00:00',
            'comment' => '出勤時刻の修正をお願いします。',
            'approval_status' => '承認済み',
            'new_date' => $approvedAttendance->date,
            'application_date' => now()->toDateString(),
        ]);

        AttendanceCorrectionRequestBreak::create([
            'attendance_correction_request_id' => $approvedRequest->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}