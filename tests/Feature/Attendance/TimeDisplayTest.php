<?php

namespace Tests\Feature\Attendance;

use App\Enums\WorkStatus;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
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

        // 時刻部分の形式(HH:MM)チェック
        $this->assertMatchesRegularExpression(
            '/<div[^>]*id="server-time"[^>]*>\s*\d{2}:\d{2}\s*<\/div>/',
            $response->getContent()
        );
    }
}
