<?php

namespace Tests\Feature\Report;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    // テストごとにデータベースを初期化する
    use RefreshDatabase;

    /**
     * ログインユーザーがマイ勤怠レポートを表示できることを確認する
     */
    public function test_user_can_view_attendance_report(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // 今月の1日を取得する
        $firstDayOfMonth = now()->startOfMonth();

        // 8時間勤務の勤怠を作成する
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfMonth->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
        ]);

        // 9時間勤務の勤怠を作成する
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfMonth->copy()->addDay()->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
            'total_break_time' => '01:00:00',
        ]);

        // ログインユーザーとしてレポート画面へアクセスする
        $response = $this->actingAs($user)
            ->get(route('attendance.report'));

        // HTTPステータスが200であることを確認する
        $response->assertOk();

        // レポート画面が表示されていることを確認する
        $response->assertViewIs('reports.index');

        // 基本集計値を確認する
        $response->assertViewHas('summary', [
            'total_work_minutes' => 1020,
            'total_overtime_minutes' => 60,
            'avg_work_minutes' => 510,
        ]);
    }

    /**
     * 未ログインユーザーがマイ勤怠レポートを表示できないことを確認する
     */
    public function test_guest_cannot_view_attendance_report(): void
    {
        // 未ログイン状態でレポート画面へアクセスする
        $response = $this->get(route('attendance.report'));

        // ログイン画面へリダイレクトされることを確認する
        $response->assertRedirect(route('login'));
    }
}