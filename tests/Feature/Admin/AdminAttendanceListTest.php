<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_attendance_list_for_specified_date(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-09');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-09-09';
        });

        $response->assertViewHas('attendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()->date === '2026-09-09';
        });

        Carbon::setTestNow();
    }
}