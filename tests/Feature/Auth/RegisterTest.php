<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_fails_when_name_is_missing(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        // 「お名前を入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_fails_when_email_is_missing(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => 'Tanaka Kanata',
            'email' => '',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        // 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_register_fails_when_password_is_too_short(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => 'Tanaka Kanata',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        // 「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_register_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => 'Tanaka Kanata',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'password1234',
        ]);

        // 「パスワードと一致しません」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_fails_when_password_is_missing(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => 'Tanaka Kanata',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'pass1234',
        ]);

        // 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_register_succeeds_with_valid_input(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            '_token' => csrf_token(),
            'name' => 'Tanaka Kanata',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        // データベースに登録したユーザー情報が保存される
        $this->assertDatabaseHas('users', [
            'name' => 'Tanaka Kanata',
            'email' => 'test@example.com',
        ]);
    }
}
