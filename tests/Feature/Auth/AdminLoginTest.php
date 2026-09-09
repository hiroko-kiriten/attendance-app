<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'admin_status' => true,//管理者ユーザーを作成
        ]);

        $response = $this->post('/admin/login', [//管理者ログインを実行
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();

        $this->assertAuthenticatedAs($admin);//ログインできたか確認
    }

    public function test_general_user_cannot_access_admin_attendance_list(): void
{
    $user = User::factory()->create([
        'admin_status' => false,//一般ユーザーを作る
    ]);

    $this->actingAs($user);//そのユーザーがログインしている状態にする

    $response = $this->get('/admin/attendance/list');//管理者専用ページへアクセス
    $response->assertForbidden();//アクセス禁止になる
}
}