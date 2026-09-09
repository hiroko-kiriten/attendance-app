<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminApplicationListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_view_application_list()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        // 一般ユーザーを作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 勤怠記録を作成
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        // 修正申請を作成
        $application = AttendanceCorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'approval_status' => '承認待ち',
        ]);

        // 管理者として申請一覧へアクセス
        $response = $this->actingAs($admin)
            ->get('/admin/stamp_correction_request/list');

        // 正常に画面が表示されることを確認
        $response->assertStatus(200);

        // 申請一覧データがViewに渡されていることを確認
        $response->assertViewHas('applications', function ($applications) use ($application) {
            return $applications->contains('id', $application->id);
        });
    }
}