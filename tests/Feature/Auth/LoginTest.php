<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([//テスト用ユーザーをDBに作る
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [//ログイン処理を行う
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();

        $this->assertAuthenticatedAs($user);//このユーザーとしてログイン状態になっている
    }
}