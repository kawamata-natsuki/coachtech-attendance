<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\AuthTestHelper;

class WorkStatusTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_work_status_shows_correctly_when_off(): void
    {
        $user = $this->loginUser();
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 画面上に表示されているステータスが「勤務外」となる
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_work_status_shows_correctly_when_working(): void
    {
        $user = $this->loginWorkingUser();
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 画面上に表示されているステータスが「出勤中」となる
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_work_status_shows_correctly_when_break(): void
    {
        $user = $this->loginBreakUser();
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 画面上に表示されているステータスが「休憩中」となる
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_work_status_shows_correctly_when_completed(): void
    {
        $user = $this->loginCompletedUser();
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 画面上に表示されているステータスが「退勤済」となる
        $response->assertSee('退勤済');
    }
}
