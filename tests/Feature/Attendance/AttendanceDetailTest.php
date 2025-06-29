<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_displays_logged_in_user_name(): void
    {
        $user = $this->loginCompletedUser();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // 名前がログインユーザーの名前になっている
        $response->assertSee($attendance->user->name);
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_displays_selected_date(): void
    {
        $user = $this->loginUser();

        // --- 特定の日付の勤怠データを作成 ---
        $targetDate = now()->startOfMonth();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $targetDate->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // 日付が選択した日付になっている
        $response->assertSee($targetDate->format('Y年n月j日'));
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_clock_in_and_out_times(): void
    {
        $user = $this->loginCompletedUser();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $html = $response->getContent();
        $this->assertStringContainsString('09', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
        $this->assertStringContainsString('18', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_break_times(): void
    {
        $user = $this->loginCompletedUser();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $html = $response->getContent();
        $this->assertStringContainsString('12', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
        $this->assertStringContainsString('13', $html);
        $this->assertStringContainsString(':', $html);
        $this->assertStringContainsString('00', $html);
    }
}
