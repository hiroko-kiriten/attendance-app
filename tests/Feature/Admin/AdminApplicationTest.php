<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_attendance_correction_request(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
            'comment' => '修正前',
        ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $application = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'new_date' => '2026-09-09',
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:30:00',
            'comment' => '修正申請の内容',
            'approval_status' => '承認待ち',
            'application_date' => '2026-09-09',
        ]);

        $application->attendanceCorrectionRequestBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:30:00',
        ]);

        $response = $this->actingAs($admin)
            ->post('/stamp_correction_request/approve/' . $application->id);

        $response->assertRedirect('/stamp_correction_request/list');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'date' => '2026-09-09',
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'comment' => '修正申請の内容',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:30:00',
        ]);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $application->id,
            'approval_status' => '承認済み',
        ]);
    }
}