<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_view_all_users_names_and_emails(): void
    {
        $admin = $this->loginAdmin();

        // --- スタッフのデータを作成 ---
        $users = collect([
            User::factory()->create([
                'name'  => 'testA',
                'email' => 'testA@example.com'
            ]),
            User::factory()->create([
                'name' => 'testB',
                'email' => 'testB@example.com'
            ]),
            User::factory()->create([
                'name' => 'testC',
                'email' => 'testC@example.com'
            ]),
        ]);

        $response = $this->get(route('admin.staff.index'));
        $response->assertStatus(200);

        // 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
        $response->assertSee('testA');
        $response->assertSee('testA@example.com');
        $response->assertSee('testB');
        $response->assertSee('testB@example.com');
        $response->assertSee('testC');
        $response->assertSee('testC@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_user_attendance_information_is_displayed_correctly(): void

    {
        $admin = $this->loginAdmin();

        // --- スタッフのデータを作成 ---
        $user = $this->createUser();
        $today = today();
        $targetDay = $today->subDay();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $targetDay,
            ]);

        $response = $this->get(route('admin.staff.index'));
        $response->assertStatus(200);
        $response = $this->get(route('admin.attendances.staff', [
            'id' => $user->id,
            'month' => $targetDay->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 勤怠情報が正確に表示される
        $response->assertSee($user->user_name);
        $response->assertSee($targetDay->format('Y/m'));
        $response->assertSee($attendance->work_date->locale('ja')->isoFormat('MM/DD(dd)'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }


    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_attendance_information_is_displayed_when_previous_button_clicked(): void
    {
        $admin = $this->loginAdmin();

        // --- 前月の勤怠データを作成 ---
        $user = $this->createUser();
        $prevMonth = today()->subMonth()->startOfMonth();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $prevMonth->toDateString(),
            ]);

        // --- 前月の勤怠一覧を表示させる ---
        $response = $this->get(route('admin.attendances.staff', [
            'id' => $user->id,
            'month' => $prevMonth->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 前月の情報が表示されている
        $response->assertSee($prevMonth->locale('ja')->isoFormat('MM/DD(dd)'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_next_month_attendance_information_is_displayed_when_next_button_clicked(): void
    {
        $admin = $this->loginAdmin();

        // --- 翌月の勤怠一覧を表示させる ---
        $user = $this->createUser();
        $nextMonth = today()->addMonth()->startOfMonth();
        $response = $this->get(route('admin.attendances.staff', [
            'id' => $user->id,
            'month' => $nextMonth->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 翌月の情報が表示されている
        $response->assertSee($nextMonth->locale('ja')->isoFormat('MM/DD(dd)'));
        $response->assertDontSee('09:00');
        $response->assertDontSee('18:00');
        $response->assertDontSee('1:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_attendance_detail_page_is_displayed_when_detail_button_clicked(): void
    {
        $admin = $this->loginAdmin();

        // --- 特定のスタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $targetDay = today()->subDay();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $targetDay->toDateString(),
            ]);

        $response = $this->get(route('admin.attendances.staff', [
            'id' => $user->id,
        ]));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移する
        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSee($user->name);
        $response->assertSee($targetDay->format('Y年n月j日'));
        $html = $response->getContent();
        $this->assertStringContainsString('09', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
        $this->assertStringContainsString('18', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
        $this->assertStringContainsString('12', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
        $this->assertStringContainsString('13', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
    }
}
