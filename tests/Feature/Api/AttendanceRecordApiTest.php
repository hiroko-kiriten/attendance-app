<?php

namespace Tests\Feature\Api;

use App\Models\User;
// テストごとにデータベースを初期化する機能を読み込む
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
      // 各テスト前にデータベースを初期化する
    use RefreshDatabase;

    /**
     * APIから勤怠を新規登録できることを確認する
     */
    public function test_can_create_attendance_record(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // 勤怠登録APIへPOSTする
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIからのテスト登録',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 登録成功時は201 Createdになることを確認する
        $response->assertStatus(201);

        // レスポンスに登録した勤怠データが含まれることを確認する
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.date', '2026-09-24');

        // データベースに勤怠が登録されたことを確認する
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /**
     * APIから自分の勤怠を更新できることを確認する
     */
    public function test_can_update_own_attendance_record(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // 更新対象の勤怠を作成する
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '更新前',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 勤怠の所有者がテストユーザーと一致することを確認する
        $this->assertSame($user->id, $attendanceRecord->user_id);

        // Policyが更新を許可していることを確認する
        $this->assertTrue($user->can('update', $attendanceRecord));

        // HTTPリクエスト時の認証ユーザーを確認する
        $this->assertAuthenticatedAs($user);

        // 勤怠更新APIへPUTする
        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-09-24',
                'clock_in' => '08:30:00',
                'clock_out' => '18:00:00',
                'comment' => '更新後',
                'total_break_time' => '01:00:00',
                'total_time' => '08:30:00',
            ]
        );

        // 403の詳しいレスポンス内容を確認する
        $response->dump();

        // 更新成功時は200 OKになることを確認する
        $response->assertStatus(200);

        // レスポンスに更新後の勤怠データが含まれることを確認する
        $response->assertJsonPath('data.clock_in', '08:30:00');
        $response->assertJsonPath('data.comment', '更新後');

        // データベースの勤怠が更新されたことを確認する
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '08:30:00',
            'comment' => '更新後',
        ]);
    }

    /**
     * APIから自分の勤怠を削除できることを確認する
     */
    public function test_can_delete_own_attendance_record(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // 削除対象の勤怠を作成する
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '削除テスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 勤怠削除APIへDELETEする
        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        // 削除成功時は204 No Contentになることを確認する
        $response->assertNoContent();

        // データベースから勤怠が削除されたことを確認する
        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }
}
