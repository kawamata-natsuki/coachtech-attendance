<?php

namespace Tests\Feature\Attendance;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 休憩ボタンが正しく機能する
     */
    public function test_user_can_start_break_successfully(): void
    {
        $user = $this->loginWorkingUser();

        // 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // --- 休憩入処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $latestBreak = $attendance->breakTimes()->latest()->first();
        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->break_start);

        // --- 画面確認（リダイレクト先） ---
        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 休憩は一日に何回でもできる
     */
    public function test_user_can_start_multiple_breaks_in_one_day(): void
    {
        $user = $this->loginWorkingUser();

        // --- 休憩前の休憩件数を確認 ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $initialBreakCount = $attendance->breakTimes()->count();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 1回目の休憩 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- 1回目の休憩終了 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);

        // --- 2回目の休憩開始 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- 2回目の休憩終了 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $latestBreak = $attendance->breakTimes()->latest()->first();
        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->break_start);
        $this->assertNotNull($latestBreak->break_end);

        // --- 休憩件数が+2されていることを確認（1回目と2回目）
        $attendance->refresh();
        $currentBreakCount = $attendance->breakTimes()->count();
        $this->assertEquals($initialBreakCount + 2, $currentBreakCount);

        // 画面上に「休憩入」ボタンが表示される
        $response = $this->get(route('user.attendances.record'));
        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能する
     */
    public function test_user_can_end_break_successfully(): void
    {
        $user = $this->loginWorkingUser();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 休憩入処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
        $response = $this->get(route('user.attendances.record'));
        $response->assertSee('休憩戻');

        // --- 休憩戻処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $latestBreak = $attendance->breakTimes()->latest()->first();
        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->break_end);
        $this->assertEquals(
            WorkStatus::WORKING,
            $attendance->work_status
        );

        // --- 画面確認（リダイレクト先） ---
        $response = $this->get(route('user.attendances.record'));
        $response->assertSee('出勤中');
    }

    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_user_can_end_multiple_breaks_in_one_day(): void
    {
        $user = $this->loginWorkingUser();

        // --- 休憩前の休憩件数を確認 ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $initialBreakCount = $attendance->breakTimes()->count();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 1回目の休憩 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- 1回目の休憩終了 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);

        // --- 2回目の休憩開始 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $latestBreak = $attendance->breakTimes()->latest()->first();
        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->break_start);
        $this->assertEquals(
            WorkStatus::BREAK,
            $attendance->work_status
        );

        // --- 休憩件数が+2されていることを確認（1回目と2回目）
        $attendance->refresh();
        $currentBreakCount = $attendance->breakTimes()->count();
        $this->assertEquals($initialBreakCount + 2, $currentBreakCount);

        // 画面上に「休憩戻」ボタンが表示される
        $response = $this->get(route('user.attendances.record'));
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_user_can_see_break_times_in_attendance_list(): void
    {
        $user = $this->loginWorkingUser();

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // --- 休憩入処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);

        // --- 休憩戻処理 ---
        $response = $this->post(route('user.attendances.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);

        // --- DBチェック ---
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
        $this->assertNotNull($attendance);
        $latestBreak = $attendance->breakTimes()->latest()->first();
        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->break_start);
        $this->assertNotNull($latestBreak->break_end);

        // --- DBで休憩の時間を意図的に1時間に調整 ---
        $latestBreak->update([
            'break_start' => now()->setTime(12, 0, 0),
            'break_end'   => now()->setTime(13, 0, 0),
        ]);

        // 勤怠一覧画面に休憩時刻が正確に記録されている
        $response = $this->get(route('user.attendances.index'));
        $response->assertStatus(200);
        $response->assertSee(today()->locale('ja')->isoFormat('MM/DD(dd)'));
        $response->assertSee('1:00');
    }
}
