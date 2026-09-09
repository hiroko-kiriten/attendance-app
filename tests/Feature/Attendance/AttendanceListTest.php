<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_attendance_list(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');

        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-09-08',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');//2026年9月の勤怠だけを取得

        $response->assertStatus(200);

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['date'] === '09/08'
                && $records->first()['clock_in'] === '09:00'
                && $records->first()['clock_out'] === '18:00'
                && $records->first()['total_break_time'] === '01:00:00'
                && $records->first()['total_time'] === '08:00:00';
        });

        Carbon::setTestNow();
    }

    public function test_user_cannot_view_other_users_attendance_records(): void
{
    Carbon::setTestNow('2026-09-09 10:00:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->attendanceRecords()->create([
        'date' => '2026-09-08',
        'clock_in' => '09:00:00',
        'clock_out' => '18:00:00',
        'total_break_time' => '01:00:00',
        'total_time' => '08:00:00',
    ]);

    $otherUser->attendanceRecords()->create([
        'date' => '2026-09-08',
        'clock_in' => '10:00:00',
        'clock_out' => '19:00:00',
        'total_break_time' => '01:00:00',
        'total_time' => '08:00:00',
    ]);

    $response = $this->actingAs($user)
        ->get('/attendance/list?date=2026-09');//ログインしているユーザー自身の勤怠だけが表示される

    $response->assertStatus(200);

    $response->assertViewHas('formattedAttendanceRecords', function ($records) {
        return $records->count() === 1
            && $records->first()['clock_in'] === '09:00';
    });

    Carbon::setTestNow();
}
}