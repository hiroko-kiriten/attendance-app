<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [//実際に会員登録フォームから登録したもの
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [//usersテーブルに本当に登録されたか
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }
}