<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_staff_attendance_detail(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
            'name' => 'テストスタッフ',
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
            'comment' => '通常勤務',
        ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);

        $response->assertViewHas('attendanceRecord', function ($record) {//勤怠情報
            return $record->id !== null
                && $record->date === '2026-09-08'
                && $record->clock_in === '09:00:00'
                && $record->clock_out === '18:00:00';
        });

        $response->assertViewHas('user', function ($viewUser) {//スタッフ情報
            return $viewUser->name === 'テストスタッフ';
        });

        $response->assertViewHas('breaks', function ($breaks) {//休憩情報
            return count($breaks) === 1
                && $breaks[0]['break_in'] === '12:00:00'
                && $breaks[0]['break_out'] === '13:00:00';
        });

        Carbon::setTestNow();
    }
}