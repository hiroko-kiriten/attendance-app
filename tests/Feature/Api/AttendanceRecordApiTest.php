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
     * 勤怠登録APIで必須項目がない場合はバリデーションエラーになることを確認する
     */
    public function test_create_attendance_record_fails_when_required_field_is_missing(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // dateを指定せずに勤怠登録APIへPOSTする
        $response = $this->postJson('/api/v1/attendance-records', [
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'バリデーションテスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // バリデーションエラーの場合は422になることを確認する
        $response->assertStatus(422);

        // dateにエラーがあることを確認する
        $response->assertJsonValidationErrors(['date']);

        // 日本語のエラーメッセージが返ることを確認する
        $response->assertJsonFragment([
            '勤怠日は必須です。',
        ]);
    }

    /**
     * 未認証ユーザーは勤怠登録APIを利用できないことを確認する
     */
    public function test_guest_cannot_create_attendance_record(): void
    {
        // 認証せずに勤怠登録APIへPOSTする
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '未認証テスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 未認証の場合は401 Unauthorizedになることを確認する
        $response->assertStatus(401);

        // エラーメッセージがJSONで返ることを確認する
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * 他ユーザーの勤怠を更新できないことを確認する
     */
    public function test_cannot_update_other_users_attendance_record(): void
    {
        // 操作するユーザーAを作成する
        $userA = User::factory()->create();

        // 勤怠を所有するユーザーBを作成する
        $userB = User::factory()->create();

        // ユーザーBの勤怠を作成する
        $attendanceRecord = $userB->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'ユーザーBの勤怠',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // ユーザーAをSanctumで認証する
        Sanctum::actingAs($userA, ['*']);

        // ユーザーBの勤怠を更新しようとする
        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-09-24',
                'clock_in' => '08:30:00',
                'clock_out' => '18:00:00',
                'comment' => '不正な更新',
                'total_break_time' => '01:00:00',
                'total_time' => '08:30:00',
            ]
        );

        // 他ユーザーの勤怠なので403 Forbiddenになることを確認する
        $response->assertStatus(403);

        // データベースの勤怠が変更されていないことを確認する
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $userB->id,
            'clock_in' => '09:00:00',
            'comment' => 'ユーザーBの勤怠',
        ]);
    }

     /**
     * 他ユーザーの勤怠を削除できないことを確認する
     */
    public function test_cannot_delete_other_users_attendance_record(): void
    {
        // 操作するユーザーAを作成する
        $userA = User::factory()->create();

        // 勤怠を所有するユーザーBを作成する
        $userB = User::factory()->create();

        // ユーザーBの勤怠を作成する
        $attendanceRecord = $userB->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'ユーザーBの勤怠',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // ユーザーAをSanctumで認証する
        Sanctum::actingAs($userA, ['*']);

        // ユーザーBの勤怠を削除しようとする
        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        // 他ユーザーの勤怠なので403 Forbiddenになることを確認する
        $response->assertStatus(403);

        // データベースに勤怠が残っていることを確認する
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $userB->id,
        ]);
    }

    /**
     * 勤怠登録APIで時刻形式が不正な場合はバリデーションエラーになることを確認する
     */
    public function test_create_attendance_record_fails_with_invalid_clock_in_format(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // clock_inを不正な形式にして勤怠登録APIへPOSTする
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-09-24',
            'clock_in' => '09:00',
            'clock_out' => '18:00:00',
            'comment' => 'バリデーションテスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // バリデーションエラーの場合は422になることを確認する
        $response->assertStatus(422);

        // clock_inにエラーがあることを確認する
        $response->assertJsonValidationErrors(['clock_in']);

        // 日本語のエラーメッセージが返ることを確認する
        $response->assertJsonFragment([
            '出勤時刻は HH:MM:SS 形式で指定してください。',
        ]);
    }

        /**
     * APIから勤怠一覧を取得できることを確認する
     */
    public function test_can_get_attendance_records(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // テスト用の勤怠を作成する
        $user->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '一覧取得テスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 勤怠一覧APIへGETする
        $response = $this->getJson('/api/v1/attendance-records');

        // 一覧取得成功時は200 OKになることを確認する
        $response->assertStatus(200);

        // レスポンスにdataが存在することを確認する
        $response->assertJsonStructure([
            'data',
        ]);

        // 作成した勤怠が一覧に含まれていることを確認する
        $response->assertJsonFragment([
            'date' => '2026-09-24',
            'comment' => '一覧取得テスト',
        ]);
    }

    /**
     * APIから勤怠詳細を取得できることを確認する
     */
    public function test_can_get_attendance_record_detail(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // Sanctumでユーザーを認証済みにする
        Sanctum::actingAs($user, ['*']);

        // 詳細取得対象の勤怠を作成する
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '詳細取得テスト',
            'total_break_time' => '01:00:00',
            'total_time' => '08:00:00',
        ]);

        // 勤怠詳細APIへGETする
        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        // 詳細取得成功時は200 OKになることを確認する
        $response->assertStatus(200);

        // レスポンスに対象勤怠のIDが含まれることを確認する
        $response->assertJsonPath('data.id', $attendanceRecord->id);

        // レスポンスに対象勤怠の日付が含まれることを確認する
        $response->assertJsonPath('data.date', '2026-09-24');

        // レスポンスに対象勤怠のコメントが含まれることを確認する
        $response->assertJsonPath('data.comment', '詳細取得テスト');
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
