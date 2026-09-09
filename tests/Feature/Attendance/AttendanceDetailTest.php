<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_attendance_detail(): void
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
            ->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertStatus(200);

        $response->assertViewHas('data', function ($data) {
            return $data['date'] === '9月8日'//Controllerでn → 先頭に0を付けない月、j → 先頭に0を付けない日と指定
                && $data['clock_in'] === '09:00'
                && $data['clock_out'] === '18:00'
                && $data['comment'] === '通常勤務';
        });

        Carbon::setTestNow();
    }

    public function test_user_cannot_view_other_users_attendance_detail(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceRecord = $otherUser->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertStatus(404);

        Carbon::setTestNow();
    }
}