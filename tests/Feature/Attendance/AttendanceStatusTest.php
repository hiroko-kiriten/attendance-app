<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_status_is_off_when_no_attendance_record_exists(): void
    {
        Carbon::setTestNow('2026-09-09 09:00:00');//テスト中だけ「現在時刻」を2026年9月9日9時00分に固定

        $user = User::factory()->create();

        $response = $this->actingAs($user)//ログイン済みユーザーを作る
            ->get('/attendance');//勤怠登録画面を開く

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '勤務外';//今日の勤怠記録がないユーザーなら、画面表示用ステータスが『勤務外』になる
        });

        Carbon::setTestNow();
    }

    public function test_attendance_status_is_working_after_clock_in(): void
{
    Carbon::setTestNow('2026-09-09 09:00:00');

    $user = User::factory()->create();

    $user->attendanceRecords()->create([
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
        'clock_out' => null,
    ]);

    $response = $this->actingAs($user)
        ->get('/attendance');

    $response->assertStatus(200);

    $response->assertViewHas('user', function ($viewUser) {
        return $viewUser->attendance_status === '出勤中';
    });

    Carbon::setTestNow();
}

    public function test_attendance_status_is_on_break_during_break(): void
{
    Carbon::setTestNow('2026-09-09 12:00:00');

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

    $response = $this->actingAs($user)
        ->get('/attendance');

    $response->assertStatus(200);

    $response->assertViewHas('user', function ($viewUser) {
        return $viewUser->attendance_status === '休憩中';
    });

    Carbon::setTestNow();
}

    public function test_attendance_status_is_finished_after_clock_out(): void
{
    Carbon::setTestNow('2026-09-09 18:00:00');

    $user = User::factory()->create();

    $user->attendanceRecords()->create([
        'date' => '2026-09-09',
        'clock_in' => '09:00:00',
        'clock_out' => '18:00:00',
    ]);

    $response = $this->actingAs($user)
        ->get('/attendance');

    $response->assertStatus(200);

    $response->assertViewHas('user', function ($viewUser) {
        return $viewUser->attendance_status === '退勤済';
    });

    Carbon::setTestNow();
}
public function test_attendance_register_displays_current_date_and_time(): void
{
    // テスト中だけ「現在日時」を固定
    Carbon::setTestNow('2026-09-09 10:34:00');

    $user = User::factory()->create();

    // 勤怠登録画面を開く
    $response = $this->actingAs($user)
        ->get('/attendance');

    $response->assertStatus(200);

    // 現在の日付が正しくViewに渡されていることを確認
    $response->assertViewHas('formattedDate', '2026年09月09日');

    // 現在の時刻が正しくViewに渡されていることを確認
    $response->assertViewHas('formattedTime', '10:34');

    // テスト用の現在日時を解除
    Carbon::setTestNow();
}
}