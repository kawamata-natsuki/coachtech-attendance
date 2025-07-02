<?php

namespace Tests\Feature\Attendance;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\AuthTestHelper;


class ClockOutTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_user_can_clock_out_successfully(): void
    {
        $user = $this->loginWorkingUser();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
        $response->assertSee('退勤');

        // --- 退勤処理 ---
        $response = $this->post(route('user.attendances.store'), [
            '_token' => csrf_token(),
            'action' => 'clock_out',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_out);
        $this->assertEquals(
            WorkStatus::COMPLETED,
            $attendance->work_status
        );

        // --- 退勤後の画面確認 ---
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertSee('退勤');
        $response->assertSee('退勤済');
    }

    /**
     * 退勤時刻が管理画面で確認できる
     */
    public function test_user_can_see_their_clock_out_time_on_attendance_page(): void
    {
        $user = $this->loginUser();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 出勤処理 ---
        $response = $this->post(route('user.attendances.store'), [
            '_token' => csrf_token(),
            'action' => 'clock_in',
        ]);
        $response->assertStatus(302);

        // --- 退勤処理 ---
        $response = $this->post(route('user.attendances.store'), [
            '_token' => csrf_token(),
            'action' => 'clock_out',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
        $this->assertNotNull($attendance->clock_out);
        $this->assertEquals(
            WorkStatus::COMPLETED,
            $attendance->work_status
        );

        // --- DBで退勤時刻を18:00に意図的に調整 ---
        $attendance->update([
            'clock_out' => now()->setTime(18, 0, 0),
        ]);

        // 管理画面(=勤怠一覧画面)に退勤時刻が正確に記録されている
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);
        $response->assertSee('18:00');
    }
}
