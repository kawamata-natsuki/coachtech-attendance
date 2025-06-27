<?php

namespace Tests\Feature\Attendance;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_user_can_clock_in_successfully(): void
    {
        $user = $this->loginUser();

        // 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」（＝出勤中）になる
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // --- 出勤処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'clock_in',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
        $this->assertEquals(WorkStatus::WORKING, $attendance->work_status);

        // --- 出勤後の画面確認 ---
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_user_cannot_clock_in_twice_on_same_day(): void
    {
        $user = $this->loginCompletedUser();

        // 画面上に「出勤」ボタンが表示されない
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が管理画面で確認できる
     */
    public function test_user_can_see_their_clock_in_time_on_attendance_page(): void
    {
        $user = $this->loginUser();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 出勤処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'clock_in',
        ]);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);

        // 管理画面（勤怠一覧画面）に出勤時刻が正確に記録されている
        $clockInTime = $attendance->clock_in->format('H:i');
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);
        $response->assertSee($clockInTime);
    }
}
