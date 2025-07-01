<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\AuthTestHelper;


class EmailVerifyTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $response = $this->get(route('register'));
        $response->assertStatus(200);

        // --- ユーザー登録＋認証メール送信 ---
        $response = $this->post(route('register'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);
        $response->assertRedirect('/email/verify');

        // 登録したメールアドレス宛に認証メールが送信されている
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_clicking_verification_button_redirects_to_verification_site(): void
    {
        Notification::fake();

        $response = $this->get(route('register'));
        $response->assertStatus(200);

        // --- ユーザー登録＋認証メール送信 ---
        $response = $this->post(route('register'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);
        $response->assertRedirect('/email/verify');

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        // メール認証サイトに遷移する
        $response = $this->get(route('verification.check'));
        $response->assertRedirect('https://mailtrap.io/');
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_successful_email_verification_redirects_to_attendance_registration(): void
    {
        Notification::fake();

        // ユーザー登録
        $this->post(route('register'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        // 認証URLを作成（本来はメールに含まれるURL）
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 認証リンクにアクセスして認証を完了
        $response = $this->actingAs($user)->get($verificationUrl);

        // 勤怠登録画面にリダイレクトされることを確認
        $response->assertRedirect(route('user.attendances.record'));
    }
}
