<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_staff_attendance_list(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
            'name' => 'テストスタッフ',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 9月以外の勤怠
        $user->attendanceRecords()->create([
            'date' => '2026-08-31',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?date=2026-09');

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->name === 'テストスタッフ';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 2
                && $records->first()['date'] === '09/08'
                && $records->first()['clock_in'] === '09:00'
                && $records->first()['clock_out'] === '18:00'
                && $records->last()['date'] === '09/05';
        });

        $response->assertViewHas('previousMonth', '2026-08');
        $response->assertViewHas('nextMonth', '2026-10');

        Carbon::setTestNow();
    }
}

