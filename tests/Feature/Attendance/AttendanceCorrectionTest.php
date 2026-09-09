<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_attendance_correction_request(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id, [//現在のルートがPOST /attendance/{id}だから
                'new_date' => '2026-09-08',
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'comment' => '出退勤時間を修正してください',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'new_date' => '2026-09-08',
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:30:00',
            'comment' => '出退勤時間を修正してください',
            'approval_status' => '承認待ち',
            'application_date' => '2026-09-09',
        ]);

        Carbon::setTestNow();
    }
}
