<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_all_user_attendances_are_displayed(): void
    {
        $user = $this->loginCompletedUser();

        // --- 勤怠データ+2日分を作成 ---
        $attendances = collect();
        foreach (range(1, 2) as $i) {
            $attendances->push(Attendance::factory()
                ->for($user)
                ->withBreakTime()
                ->create([
                    'work_date' => today()->addDays($i)->toDateString(),
                ]));
        }

        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);

        // 自分の勤怠情報が全て表示されている
        foreach ($attendances as $attendance) {
            $response->assertSee(today()->locale('ja')->isoFormat('MM/DD(dd)'));
            $response->assertSee('09:00');
            $response->assertSee('18:00');
            $response->assertSee('1:00');
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed_on_attendance_list_page(): void
    {
        $user = $this->loginCompletedUser();

        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);

        // 現在の月が表示されている
        $response->assertSee(today()->format('Y/m'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_attendances_are_displayed_when_navigating_to_previous_month(): void
    {
        $user = $this->loginCompletedUser();

        // --- 前月の勤怠データを作成 ---
        $prevMonth = today()->subMonth()->startOfMonth();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $prevMonth->toDateString(),
            ]);

        // --- 前月の勤怠一覧を表示させる ---
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);
        $response = $this->get(route('user.attendances.index', [
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
    public function test_next_month_attendances_are_displayed_when_navigating_to_next_month(): void
    {
        $user = $this->loginCompletedUser();

        // --- 翌月の勤怠データを作成 ---
        $nextMonth = today()->addMonth()->startOfMonth();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $nextMonth->toDateString(),
            ]);

        // --- 翌月の勤怠一覧を表示させる ---
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);
        $response = $this->get(route('user.attendances.index', [
            'month' => $nextMonth->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 翌月の情報が表示されている
        $response->assertSee($nextMonth->locale('ja')->isoFormat('MM/DD(dd)'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_can_navigate_to_attendance_detail_page(): void
    {
        $user = $this->loginCompletedUser();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        // --- 勤怠一覧ページを開く ---
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移する
        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee($attendance->work_date->format('Y年n月j日'));
    }
}
