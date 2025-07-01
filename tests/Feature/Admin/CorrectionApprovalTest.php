<?php

namespace Tests\Feature\Admin;

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionBreakTime;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers\AuthTestHelper;
use Tests\TestCase;

class CorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_all_pending_correction_requests_are_displayed(): void
    {
        $admin = $this->loginAdmin();
        $subDay = today()->subDay();
        $status = ApprovalStatus::class;

        // --- スタッフ3人分の前日の勤怠データを作成 ---
        $users = User::factory()->count(3)->create();
        $names = ['testA', 'testB', 'testC'];
        foreach ($users as $index => $user) {
            $user->update(['name' => $names[$index]]);
        }
        foreach ($users as $index => $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $subDay,
            ]);

            CorrectionRequest::factory()->create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'work_date' => $subDay,
                'reason' => "test" . ($index + 1),
            ]);
        }

        // --- 修正申請一覧ページを開き、承認待ちのタブを開く ---
        $response = $this->get(route('correction-requests.index', [
            'status' => 'pending',
        ]));
        $response->assertStatus(200);

        // 全ユーザーの未承認の修正申請が表示される
        foreach ($users as $index => $user) {
            $response->assertSee($user->name);
            $response->assertSee("test" . ($index + 1));
        };
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_all_approved_correction_requests_are_displayed(): void
    {
        $admin = $this->loginAdmin();
        $subDay = today()->subDay();
        $status = ApprovalStatus::class;

        // --- スタッフ3人分の前日の勤怠データを作成 ---
        $users = User::factory()->count(3)->create();
        $names = ['testA', 'testB', 'testC'];
        foreach ($users as $index => $user) {
            $user->update(['name' => $names[$index]]);
        }
        foreach ($users as $index => $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $subDay,
            ]);

            // --- スタッフ3人分の前日の勤怠データを修正、承認済にする ---
            CorrectionRequest::factory()->create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'work_date' => $subDay,
                'reason' => "test" . ($index + 1),
                'approval_status' => ApprovalStatus::APPROVED,
            ]);
        }

        // --- 修正申請一覧ページを開き、承認待ちのタブを開く ---
        $response = $this->get(route('correction-requests.index', [
            'status' => 'approved',
        ]));
        $response->assertStatus(200);

        // 全ユーザーの承認済みの修正申請が表示される
        foreach ($users as $index => $user) {
            $response->assertSee($user->name);
            $response->assertSee("test" . ($index + 1));
        };
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_correction_request_details_are_displayed_correctly(): void
    {
        $admin = $this->loginAdmin();
        $subDay = today()->subDay();

        // --- スタッフの前日の勤怠データを作成 ---
        $user = $this->loginUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $subDay,
            ]);
        $breakTime = $attendance->breakTimes()->first();

        // --- 申請データを作成 ---
        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $subDay,
            'reason' => "test",
        ]);
        CorrectionBreakTime::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_time_id' => $breakTime->id,
        ]);

        $correctionBreakTime = CorrectionBreakTime::where('correction_request_id', $correctionRequest->id)->first();

        $response = $this->get(route('admin.correction-requests.show', [
            'attendance_correct_request' => $correctionRequest->id,
        ]));
        $response->assertStatus(200);

        // 申請内容が正しく表示されている
        $response->assertSee($attendance->user->name);
        $response->assertSee($attendance->work_date->format('Y年n月j日'));
        $response->assertSee($correctionRequest->requested_clock_in->format('H:i'));
        $response->assertSee($correctionRequest->requested_clock_out->format('H:i'));
        $response->assertSee($correctionBreakTime->requested_break_start->format('H:i'));
        $response->assertSee($correctionBreakTime->requested_break_end->format('H:i'));
        $response->assertSee('test');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_correction_request_approval_process_works_correctly(): void
    {
        $admin = $this->loginAdmin();
        $subDay = today()->subDay();

        // --- スタッフの前日の勤怠データを作成 ---
        $user = $this->createUser();
        $attendance = Attendance::factory()
            ->for($user)
            ->withBreakTime()
            ->create([
                'work_date' => $subDay,
            ]);
        $breakTime = $attendance->breakTimes()->first();

        // --- 申請データを作成 ---
        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $subDay,
            'reason' => "test",
        ]);
        CorrectionBreakTime::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_time_id' => $breakTime->id,
        ]);

        $correctionBreakTime = CorrectionBreakTime::where('correction_request_id', $correctionRequest->id)->first();

        $response = $this->get(route('admin.correction-requests.show', [
            'attendance_correct_request' => $correctionRequest->id,
        ]));
        $response->assertStatus(200);

        $response = $this->post(route('admin.correction-requests.approve', [
            'attendance_correct_request' => $correctionRequest->id,
        ]));
        $response->assertStatus(302);

        // 修正申請が承認され、勤怠情報が更新される
        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $correctionRequest->requested_clock_in,
            'clock_out' => $correctionRequest->requested_clock_out,
        ]);
        $this->assertDatabaseHas('break_times', [
            'id' => $breakTime->id,
            'break_start' => $correctionBreakTime->requested_break_start,
            'break_end' => $correctionBreakTime->requested_break_end,
        ]);
    }
}
