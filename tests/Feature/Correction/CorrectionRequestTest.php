<?php

namespace Tests\Feature\Correction;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_message_displayed_when_clock_in_is_after_clock_out(): void
    {
        $user = $this->loginCompletedUser();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // --- 出勤時間(18:00)、退勤時間(09:00) → 出勤 > 退勤になるように逆転 ---
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
        $user = $this->loginUser();
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

        // --- 休憩開始時間を退勤（18:00）より後に修正して送信 ---
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

        // 「休憩時間が不適切な値です」というバリデーションメッセージが表示される（→休憩時間が勤務時間外です）確認!!
        $response->assertInvalid([
            'requested_breaks.0.requested_break_start' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_error_message_displayed_when_break_end_is_after_clock_out(): void
    {
        $user = $this->loginUser();
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

        // --- 休憩終了時間を退勤（18:00）より後に修正して送信 ---
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

        // 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される（→休憩時間が勤務時間外です）確認!!
        $response->assertInvalid([
            'requested_breaks.0.requested_break_end' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_error_message_displayed_when_reason_is_empty(): void
    {
        $user = $this->loginUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->create([
                'work_date' => today()->subDay()->toDateString(),
            ]);

        $response = $this->get(route('attendances.show', [
            'id' => $attendance->id,
        ]));
        $response->assertStatus(200);

        // --- 備考欄未入力のまま送信 ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '09', 'minute' => '00'],
            'requested_clock_out' => ['hour' => '18', 'minute' => '00'],
            'reason' => '',
        ]);

        // 「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertInvalid([
            'reason' => '備考を記入してください',
        ]);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_correction_request_is_processed(): void
    {
        $user = $this->loginUser();
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

        // --- 勤怠詳細を修正し保存処理をする ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '10', 'minute' => '30'],
            'requested_clock_out' => ['hour' => '19', 'minute' => '30'],
            'requested_breaks' => [
                [
                    'requested_break_start' => ['hour' => '13', 'minute' => '15'],
                    'requested_break_end' => ['hour' => '14', 'minute' => '15'],
                ],
            ],
            'reason' => '電車遅延のため',
        ]);

        // --- 修正申請がDBに保存されていることを確認 ---
        $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($correctionRequest);
        $this->assertDatabaseHas('correction_break_times', [
            'correction_request_id' => $correctionRequest->id,
        ]);
        $this->assertDatabaseHas('correction_requests', [
            'attendance_id' => $attendance->id,
            'reason' => '電車遅延のため',
        ]);

        // 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
        $user = $this->loginAdmin();

        // --- 修正申請承認画面 ---
        $correctionRequestId = $attendance->correctionRequests()->latest()->first()->id;
        $response = $this->get(route('admin.correction-requests.show', $correctionRequestId));
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($correctionRequest->work_date->format('Y年n月j日'));
        $response->assertSee('10:30');
        $response->assertSee('19:30');
        $response->assertSee('13:15');
        $response->assertSee('14:15');
        $response->assertSee('電車遅延のため');

        // --- 申請一覧画面 ---
        $response = $this->get(route('correction-requests.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee($correctionRequest->work_date->format('Y/m/d'));
        $response->assertSee('電車遅延のため');
        $response->assertSee($correctionRequest->created_at->format('Y/m/d'));
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_pending_requests_are_displayed_for_logged_in_user(): void
    {
        $user = $this->loginUser();
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

        // --- 各勤怠データで修正申請を作成 ---
        foreach ($attendances as $index => $attendance) {
            $reason = "test{$index}";

            $response = $this->get(route('attendances.show', [
                'id' => $attendance->id,
            ]));
            $response->assertStatus(200);

            // --- 勤怠詳細を修正し保存処理をする ---
            $response = $this->from(
                route('attendances.show', ['id' => $attendance->id])
            )->put(route('attendances.update', ['id' => $attendance->id]), [
                'requested_clock_in' => ['hour' => '10', 'minute' => '30'],
                'requested_clock_out' => ['hour' => '19', 'minute' => '30'],
                'requested_breaks' => [
                    [
                        'requested_break_start' => ['hour' => '13', 'minute' => '15'],
                        'requested_break_end' => ['hour' => '14', 'minute' => '15'],
                    ],
                ],
                'reason' => $reason,
            ]);

            // --- 修正申請がDBに保存されていることを確認 ---
            $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)->latest()->first();
            $this->assertNotNull($correctionRequest);
            $this->assertDatabaseHas('correction_break_times', [
                'correction_request_id' => $correctionRequest->id,
            ]);
            $this->assertDatabaseHas('correction_requests', [
                'attendance_id' => $attendance->id,
                'reason' => $reason,
            ]);
        }

        // 申請一覧に自分の申請が全て表示されている
        $response = $this->get(route('correction-requests.index', ['status' => 'pending']));
        $response->assertStatus(200);
        foreach ($attendances as $index => $attendance) {
            $response->assertSee('承認待ち');
            $response->assertSee($user->name);
            $response->assertSee($correctionRequest->work_date->format('Y/m/d'));
            $response->assertSee($reason);
            $response->assertSee($correctionRequest->created_at->format('Y/m/d'));
        }
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_requests_are_displayed_for_admin(): void
    {
        $user = $this->loginUser();
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

        // --- 各勤怠データで修正申請を作成→承認済にする ---
        foreach ($attendances as $index => $attendance) {
            $reason = "test{$index}";

            $response = $this->get(route('attendances.show', [
                'id' => $attendance->id,
            ]));
            $response->assertStatus(200);

            // --- 勤怠詳細を修正し保存処理をする ---
            $response = $this->from(
                route('attendances.show', ['id' => $attendance->id])
            )->put(route('attendances.update', ['id' => $attendance->id]), [
                'requested_clock_in' => ['hour' => '10', 'minute' => '30'],
                'requested_clock_out' => ['hour' => '19', 'minute' => '30'],
                'requested_breaks' => [
                    [
                        'requested_break_start' => ['hour' => '13', 'minute' => '15'],
                        'requested_break_end' => ['hour' => '14', 'minute' => '15'],
                    ],
                ],
                'reason' => $reason,
            ]);

            // --- 修正申請がDBに保存されていることを確認 ---
            $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)->latest()->first();
            $this->assertNotNull($correctionRequest);
            $this->assertDatabaseHas('correction_break_times', [
                'correction_request_id' => $correctionRequest->id,
            ]);
            $this->assertDatabaseHas('correction_requests', [
                'attendance_id' => $attendance->id,
                'reason' => $reason,
            ]);

            // --- 修正申請を承認済にする ---
            $correctionRequest->update([
                'approval_status' => ApprovalStatus::APPROVED,
            ]);
        }

        // 申請一覧に自分の申請が全て表示されている
        $response = $this->get(route('correction-requests.index', ['status' => 'approved']));
        $response->assertStatus(200);
        foreach ($attendances as $index => $attendance) {
            $response->assertSee('承認済み');
            $response->assertSee($user->name);
            $response->assertSee($correctionRequest->work_date->format('Y/m/d'));
            $response->assertSee($reason);
            $response->assertSee($correctionRequest->created_at->format('Y/m/d'));
        }
    }

    /**
     * 各申請の「詳細」を押下すると申請詳細画面に遷移する
     */
    public function test_clicking_details_navigates_to_request_detail_page(): void
    {
        $user = $this->loginUser();
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

        // --- 勤怠詳細を修正し保存処理をする ---
        $response = $this->from(
            route('attendances.show', ['id' => $attendance->id])
        )->put(route('attendances.update', ['id' => $attendance->id]), [
            'requested_clock_in' => ['hour' => '10', 'minute' => '30'],
            'requested_clock_out' => ['hour' => '19', 'minute' => '30'],
            'requested_breaks' => [
                [
                    'requested_break_start' => ['hour' => '13', 'minute' => '15'],
                    'requested_break_end' => ['hour' => '14', 'minute' => '15'],
                ],
            ],
            'reason' => '電車遅延のため',
        ]);

        // --- 修正申請がDBに保存されていることを確認 ---
        $correctionRequest = CorrectionRequest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($correctionRequest);
        $this->assertDatabaseHas('correction_break_times', [
            'correction_request_id' => $correctionRequest->id,
        ]);
        $this->assertDatabaseHas('correction_requests', [
            'attendance_id' => $attendance->id,
            'reason' => '電車遅延のため',
        ]);

        // 申請詳細画面(→勤怠詳細画面)に遷移する 確認!!
        $response = $this->get(route('correction-requests.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response = $this->get(route('attendances.show', [
            'id' => $correctionRequest->attendance_id,
            'request_id' => $correctionRequest->id,
        ]));
        $response->assertStatus(200);
    }
}
