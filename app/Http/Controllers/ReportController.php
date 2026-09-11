<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * マイ勤怠レポートを表示する
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ログインしているユーザーを取得する
        $user = $request->user();

        // 現在の日付を取得する
        $today = Carbon::today();

        // 集計開始日を「5ヶ月前の月初」にする
        $startDate = $today->copy()->startOfMonth()->subMonths(5);

        // 集計終了日を「今月末」にする
        $endDate = $today->copy()->endOfMonth();

        // ログインユーザーの過去6ヶ月分の勤怠を取得する
        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        // 勤怠レコードごとの労働時間を確認する
        foreach ($attendanceRecords as $attendanceRecord) {

            // 退勤済みの勤怠だけを集計対象にする
            if (
                !$attendanceRecord->clock_in ||
                !$attendanceRecord->clock_out
            ) {
                continue;
            }

            // 出勤時刻を取得する
            $clockIn = Carbon::parse($attendanceRecord->clock_in);

            // 退勤時刻を取得する
            $clockOut = Carbon::parse($attendanceRecord->clock_out);

            // 出勤から退勤までの時間を分単位で取得する
            $totalMinutes = $clockIn->diffInMinutes($clockOut);

            // 休憩時間を分単位で取得する
            $breakMinutes = 0;

            if ($attendanceRecord->total_break_time) {
                $breakTime = Carbon::parse($attendanceRecord->total_break_time);

                $breakMinutes = ($breakTime->hour * 60)
                    + $breakTime->minute;
            }

            // 実際の労働時間を計算する
            $workMinutes = $totalMinutes - $breakMinutes;

            // 計算した労働時間を一時的に保持する
            $attendanceRecord->report_work_minutes = max(
                $workMinutes,
                0
            );
        }

// 総労働時間を計算する
$totalWorkMinutes = $attendanceRecords->sum(
    fn ($record) => $record->report_work_minutes ?? 0
);

// 勤怠日数を取得する
$attendanceCount = $attendanceRecords->filter(
    fn ($record) => isset($record->report_work_minutes)
)->count();

// 平均労働時間を計算する
$avgWorkMinutes = $attendanceCount > 0
    ? intdiv($totalWorkMinutes, $attendanceCount)
    : 0;

// 残業時間の合計を計算する
$totalOvertimeMinutes = $attendanceRecords->sum(
    function ($record) {
        // 労働時間が8時間を超えた分を残業時間とする
        $workMinutes = $record->report_work_minutes ?? 0;

        return max($workMinutes - (8 * 60), 0);
    }
);

// 基本サマリーを作成する
$summary = [
    'total_work_minutes' => $totalWorkMinutes,
    'total_overtime_minutes' => $totalOvertimeMinutes,
    'avg_work_minutes' => $avgWorkMinutes,
];

// 月次推移を作成する
$monthlyTrend = [];

//6ヶ月分を順番に処理するための繰り返し(最初5、0以上は繰り返す、1ずつ減らす）
//今月を含めて、過去6ヶ月を1ヶ月ずつ取り出して集計する
for ($i = 5; $i >= 0; $i--) {

    // 対象月を取得する
    $month = $today->copy()->startOfMonth()->subMonths($i);//5か月前から0か月（今月）まで

    // 対象月の勤怠だけを取得する
    $monthlyRecords = $attendanceRecords->filter(
        fn ($record) =>
            Carbon::parse($record->date)->format('Y-m') === $month->format('Y-m')
    );

    // 月の労働時間を計算する
    $workMinutes = $monthlyRecords->sum(
        fn ($record) => $record->report_work_minutes ?? 0
    );

    // 月の残業時間を計算する
    $overtimeMinutes = $monthlyRecords->sum(
        fn ($record) => max(
            ($record->report_work_minutes ?? 0) - (8 * 60),
            0
        )
    );

    // 月次データを追加する
    $monthlyTrend[] = [
        'month' => $month->format('Y-m'),
        'work_minutes' => $workMinutes,
        'overtime_minutes' => $overtimeMinutes,
    ];
}

    // 今月の勤怠だけを取得する
    $currentMonthRecords = $attendanceRecords->filter(
    fn ($record) =>
        Carbon::parse($record->date)->format('Y-m') === $today->format('Y-m')
    );

    // 遅刻回数を数える
    $lateCount = $currentMonthRecords->filter(
    fn ($record) =>
        $record->clock_in &&
        Carbon::parse($record->clock_in)->format('H:i') > '09:00'
    )->count();

    // 早退回数を数える
    $earlyLeaveCount = $currentMonthRecords->filter(
    fn ($record) =>
        $record->clock_out &&
        Carbon::parse($record->clock_out)->format('H:i') < '18:00'
    )->count();

    // 長時間労働の日数を数える
    $longWorkCount = $currentMonthRecords->filter(
    fn ($record) =>
        ($record->report_work_minutes ?? 0) > (10 * 60)
    )->count();

    // 異常検知の集計結果を作成する
    $anomalies = [
    'late_count' => $lateCount,
    'early_leave_count' => $earlyLeaveCount,
    'long_work_count' => $longWorkCount,
    ];
   
    // レポート画面に集計結果を渡す
    return view('reports.index', compact(
            'summary',
            'monthlyTrend',
            'anomalies'
        ));
    }
}