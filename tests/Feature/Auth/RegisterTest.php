<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Models\User;

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

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();//実際にはメールを送信せず通知を記録する

        $this->post('/register', [//会員登録を実行する
            'name' => '認証テストユーザー',
            'email' => 'verification@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'verification@example.com')->first();//登録したユーザーを取得する

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);//認証メール通知が送られたことを確認する
    }

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->unverified()->create();//未認証ユーザーを作成する

        $this->actingAs($user);//そのユーザーとしてログインする

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(//認証用の一時URLを作成する
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($verificationUrl);//認証リンクへアクセスする

        $response->assertRedirect();//認証後にリダイレクトされることを確認する

        $this->assertTrue($user->fresh()->hasVerifiedEmail());//メール認証済みになったことを確認する
    }
}