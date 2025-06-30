<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_selected_attendance_details_are_displayed(): void
    {
        $admin = $this->loginAdmin();

        // --- スタッフの特定の日付の勤怠データを作成 ---
        $user = $this->createUser();
        $targetDate = now()->startOfMonth();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $targetDate,
                'clock_in' => $targetDate->setTime(9, 0),
                'clock_out' => $targetDate->setTime(18, 0),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // 詳細画面の内容が選択した情報と一致する
        $response->assertSee($user->name);
        $response->assertSee($targetDate->format('Y年n月j日'));
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

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_message_displayed_when_clock_in_is_after_clock_out(): void
    {
        $admin = $this->loginAdmin();

        // --- 特定のスタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => today()->subDay()->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // ---出勤時間(18:00)、退勤時間(09:00) → 出勤 > 退勤になるように修正 ---
        $response = $this->from(
            route('attendances.show', [
                'id' => $attendance->id,
            ])
        )
            ->put(route('attendances.update', [
                'id' => $attendance->id
            ]), [
                'requested_clock_in' => ['hour' => '18', 'minute' => '00'],
                'requested_clock_out' => ['hour' => '09', 'minute' => '00'],
                'reason' => 'test',
            ]);

        // 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'work_time_invalid' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_message_displayed_when_break_start_is_after_clock_out(): void
    {
        $admin = $this->loginAdmin();

        // --- 特定のスタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => today()->subDay()->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // --- 休憩開始時間を退勤時間(18:00)より後に修正 ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '09', 'minute' => '00'],
            'requested_clock_out' => ['hour' => '18', 'minute' => '00'],
            'requested_breaks' => [
                [
                    'requested_break_start' => ['hour' => '19', 'minute' => '00'],
                    'requested_break_end' => ['hour' => '19', 'minute' => '30'],
                ],
            ],
            'reason' => 'test',
        ]);

        // 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される(→休憩時間が勤務時間外です) 確認!!
        $response->assertInvalid([
            'requested_breaks.0.requested_break_start' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_message_displayed_when_break_end_is_after_clock_out(): void
    {
        $admin = $this->loginAdmin();


        // --- 特定のスタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => today()->subDay()->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // --- 休憩終了時間を退勤時間(18:00)より後に修正 ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '09', 'minute' => '00'],
            'requested_clock_out' => ['hour' => '18', 'minute' => '00'],
            'requested_breaks' => [
                [
                    'requested_break_start' => ['hour' => '17', 'minute' => '30'],
                    'requested_break_end' => ['hour' => '18', 'minute' => '30'],
                ],
            ],
            'reason' => 'test',
        ]);

        //「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される(→休憩時間が勤務時間外です) 確認!!
        $response->assertInvalid([
            'requested_breaks.0.requested_break_end' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_error_message_displayed_when_reason_is_empty(): void
    {
        $admin = $this->loginAdmin();

        // --- 特定のスタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => today()->subDay()->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // --- 備考欄を未入力のまま修正 ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '10', 'minute' => '00'],
            'requested_clock_out' => ['hour' => '19', 'minute' => '00'],
            'requested_breaks' => [
                [
                    'requested_break_start' => ['hour' => '13', 'minute' => '00'],
                    'requested_break_end' => ['hour' => '14', 'minute' => '00'],
                ],
            ],
            'reason' => '',
        ]);

        // 「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }
}
