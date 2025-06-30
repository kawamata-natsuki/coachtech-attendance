<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_all_user_attendances_are_displayed(): void
    {
        $admin = $this->loginAdmin();

        // --- スタッフの勤怠データを作成 ---
        $users = collect([
            User::factory()->create(['name' => 'testA']),
            User::factory()->create(['name' => 'testB']),
            User::factory()->create(['name' => 'testC']),
        ]);
        $today = today();
        foreach ($users as $user) {
            Attendance::factory()
                ->for($user)
                ->withBreakTime()
                ->create([
                    'work_date' => $today,
                ]);
        }

        // その日の全ユーザーの勤怠情報が正確な値になっている
        $response = $this->get(route('admin.attendances.index', ['date' => $today]));
        $response->assertStatus(200);
        $response->assertSee($today->format('Y年n月j日'));
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee('09:00');
            $response->assertSee('18:00');
            $response->assertSee('1:00');
            $response->assertSee('8:00');
        }
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_is_displayed_on_navigation(): void
    {
        $admin = $this->loginAdmin();
        $today = today();

        $response = $this->get(route('admin.attendances.index'));
        $response->assertStatus(200);

        // 勤怠一覧画面にその日の日付が表示されている
        $response->assertSee($today->format('Y年n月j日'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_attendances_are_displayed_when_previous_button_clicked(): void
    {
        $admin = $this->loginAdmin();

        // --- スタッフの前日の勤怠データを作成 ---
        $users = collect([
            User::factory()->create(['name' => 'testA']),
            User::factory()->create(['name' => 'testB']),
            User::factory()->create(['name' => 'testC']),
        ]);
        $today = today();
        $targetDay = $today->subDay();
        foreach ($users as $user) {
            Attendance::factory()
                ->for($user)
                ->withBreakTime()
                ->create([
                    'work_date' => $targetDay,
                ]);
        }

        $response = $this->get(route('admin.attendances.index'));
        $response->assertStatus(200);

        // 前日の日付の勤怠情報が表示される
        $response = $this->get(route('admin.attendances.index', [
            'date' => $targetDay->toDateString(),
        ]));
        $response->assertSee($targetDay->format('Y年n月j日'));
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee('09:00');
            $response->assertSee('18:00');
            $response->assertSee('1:00');
            $response->assertSee('8:00');
        }
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_day_attendances_are_displayed_when_next_button_clicked(): void
    {
        $admin = $this->loginAdmin();
        $today = today();
        $nextDay = today()->addDay();

        // --- スタッフの勤怠データを作成 ---
        $users = collect([
            User::factory()->create(['name' => 'testA']),
            User::factory()->create(['name' => 'testB']),
            User::factory()->create(['name' => 'testC']),
        ]);
        foreach ($users as $user) {
            Attendance::factory()
                ->for($user)
                ->withBreakTime()
                ->create([
                    'work_date' => $today,
                ]);
        }

        $response = $this->get(route('admin.attendances.index'));
        $response->assertStatus(200);

        // 翌日の日付の勤怠情報が表示される
        $response = $this->get(route('admin.attendances.index', [
            'date' => $nextDay->toDateString(),
        ]));
        $response->assertStatus(200);
        $response->assertSee($nextDay->format('Y年n月j日'));
        $response->assertDontSee('09:00');
        $response->assertDontSee('18:00');
        $response->assertDontSee('1:00');
        $response->assertDontSee('8:00');
    }
}
