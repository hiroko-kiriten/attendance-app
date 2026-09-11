<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    // テストごとにデータベースを初期化する
    use RefreshDatabase;

    public function test_user_with_no_attendance_records_can_view_report(): void
    {
        // 勤怠記録を持たない一般ユーザーを作成する
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 作成したユーザーとしてログインする
        $response = $this->actingAs($user)
            ->get('/attendance/report');

        // レポート画面が正常に表示されることを確認する
        $response->assertOk();

        // レポート画面に渡される基本サマリーを確認する
        $response->assertViewHas('summary', function ($summary) {

            // 総労働時間が0分であることを確認する
            return $summary['total_work_minutes'] === 0

                // 総残業時間が0分であることを確認する
                && $summary['total_overtime_minutes'] === 0

                // 平均労働時間が0分であることを確認する
                && $summary['avg_work_minutes'] === 0;
        });

        // 月次推移が6ヶ月分作成されていることを確認する
        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {

            // 6ヶ月分のデータがあることを確認する
            if (count($monthlyTrend) !== 6) {
                return false;
            }

            // 6ヶ月すべての労働時間・残業時間が0であることを確認する
            foreach ($monthlyTrend as $month) {
                if (
                    $month['work_minutes'] !== 0 ||
                    $month['overtime_minutes'] !== 0
                ) {
                    return false;
                }
            }

            // すべて問題なければtrueを返す
            return true;
        });

        // 異常検知の集計結果を確認する
        $response->assertViewHas('anomalies', function ($anomalies) {

            // 遅刻・早退・長時間労働がすべて0回であることを確認する
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }
}