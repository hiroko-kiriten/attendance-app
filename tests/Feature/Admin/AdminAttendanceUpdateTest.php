<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_staff_attendance(): void
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

        $response = $this->actingAs($admin)
            ->post('/attendance/' . $attendanceRecord->id, [
                'new_clock_in' => '09:30:00',
                'new_clock_out' => '18:30:00',
                'new_break_in' => [
                    '12:00:00',
                ],
                'new_break_out' => [
                    '13:30:00',
                ],
                'comment' => '管理者による修正',
            ]);

        $response->assertRedirect(
            route('attendance.detail', $attendanceRecord->id)
        );//Controllerにあるroute('admin.attendance.detail', $id);を確認

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'comment' => '管理者による修正',
            'total_break_time' => '01:30:00',
            'total_time' => '07:30:00',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:30:00',
        ]);
    }
}