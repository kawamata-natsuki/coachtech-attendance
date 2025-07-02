<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\AuthTestHelper;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_when_email_is_missing(): void
    {
        $user = $this->createUser();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->post('/login', [
            '_token' => csrf_token(),
            'email' => '',
            'password' => 'guest123'
        ]);

        // 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_when_password_is_missing(): void
    {
        $user = $this->createUser();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->post('/login', [
            '_token' => csrf_token(),
            'email' => 'guest@example.com',
            'password' => '',
        ]);

        // 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_when_user_credentials_do_not_match(): void
    {
        $user = $this->createUser();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->post('/login', [
            '_token' => csrf_token(),
            'email' => 'wrong@example.com',
            'password' => 'guest123'
        ]);

        // 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'login' => 'ログイン情報が登録されていません',
        ]);
    }
}
