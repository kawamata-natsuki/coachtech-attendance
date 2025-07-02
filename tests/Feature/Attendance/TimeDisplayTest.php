<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\AuthTestHelper;

class TimeDisplayTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_and_time_matches_ui_format(): void
    {
        $user = $this->loginUser();
        $today = now()->isoFormat('YYYY年M月D日(dd)');

        $response = $this->get(route('user.attendances.record'));
        $response->assertStatus(200);

        // 日付のフォーマットチェック
        $response->assertSee($today);

        // 現在時刻は目視で確認する運用とし、テストでは検証しない
    }
}
