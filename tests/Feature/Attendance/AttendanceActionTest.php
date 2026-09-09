<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clock_in(): void
    {
        Carbon::setTestNow('2026-09-09 09:00:00');//時刻を固定

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',//実際の出勤処理を実行
            ]);

        $response->assertRedirect('/attendance/list');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',//DB側の clock_in カラムが TIME 型
        ]);//DBに登録されたことを確認

        Carbon::setTestNow();
    }

    public function test_user_cannot_clock_in_twice_on_the_same_day(): void
{
    Carbon::setTestNow('2026-09-09 09:00:00');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/attendance', [
            'action' => 'clock_in',
        ]);

    Carbon::setTestNow('2026-09-09 10:00:00');

    $this->actingAs($user)
        ->post('/attendance', [
            'action' => 'clock_in',
        ]);

    $this->assertDatabaseCount('attendance_records', 1);//勤怠レコードが1件しか存在しないことを確認

    $this->assertDatabaseHas('attendance_records', [
        'user_id' => $user->id,
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
    ]);

    Carbon::setTestNow();
}

    public function test_user_can_start_break(): void
{
    Carbon::setTestNow('2026-09-09 09:00:00');

    $user = User::factory()->create();

    $user->attendanceRecords()->create([
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
        'clock_out' => null,
    ]);

    Carbon::setTestNow('2026-09-09 12:00:00');

    $response = $this->actingAs($user)
        ->post('/attendance', [
            'action' => 'break_in',
        ]);

    $response->assertRedirect('/attendance/list');

    $this->assertDatabaseHas('breaks', [
        'break_in' => '12:00:00',
        'break_out' => null,
    ]);

    Carbon::setTestNow();
}

    public function test_user_can_end_break(): void
{
    Carbon::setTestNow('2026-09-09 09:00:00');

    $user = User::factory()->create();

    $attendanceRecord = $user->attendanceRecords()->create([
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
        'clock_out' => null,
    ]);

    $attendanceRecord->breaks()->create([
        'break_in' => '12:00:00',
        'break_out' => null,
    ]);

    Carbon::setTestNow('2026-09-09 12:30:00');

    $response = $this->actingAs($user)
        ->post('/attendance', [
            'action' => 'break_out',
        ]);

    $response->assertRedirect('/attendance/list');

    $this->assertDatabaseHas('breaks', [
        'attendance_record_id' => $attendanceRecord->id,
        'break_in' => '12:00:00',
        'break_out' => '12:30:00',
    ]);

    $this->assertDatabaseHas('attendance_records', [
        'id' => $attendanceRecord->id,
        'total_break_time' => '00:30:00',
    ]);

    Carbon::setTestNow();
}

    public function test_user_can_clock_out_and_total_time_is_calculated(): void
{
    Carbon::setTestNow('2026-09-09 09:00:00');

    $user = User::factory()->create();

    $attendanceRecord = $user->attendanceRecords()->create([
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
        'clock_out' => null,
    ]);

    $attendanceRecord->breaks()->create([
        'break_in' => '12:00:00',
        'break_out' => '12:30:00',
    ]);

    Carbon::setTestNow('2026-09-09 18:00:00');

    $response = $this->actingAs($user)
        ->post('/attendance', [
            'action' => 'clock_out',
        ]);

    $response->assertRedirect('/attendance/list');

    $this->assertDatabaseHas('attendance_records', [
        'id' => $attendanceRecord->id,
        'clock_out' => '18:00:00',
        'total_time' => '08:30:00',
    ]);

    Carbon::setTestNow();
}
}