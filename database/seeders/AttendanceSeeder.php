<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();
        $user3 = User::where('email', 'user3@example.com')->firstOrFail();

        // user1：過去5ヶ月の通常勤務
        $this->createUser1PastFiveMonths($user1);

        // user1：当月17日の意図的な勤務パターン
        $this->createUser1CurrentMonth($user1);

        // user2：通常のダミー勤怠
        $this->createNormalAttendance($user2);

        // user3：通常のダミー勤怠
        $this->createNormalAttendance($user3);
    }

    /**
     * user1：過去5ヶ月の通常勤務
     */
    private function createUser1PastFiveMonths(User $user): void
    {
        $today = Carbon::today();

        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = $today->copy()->subMonths($monthOffset);

            $weekdays = [];

            for ($day = 1; $day <= $month->daysInMonth; $day++) {
                $date = $month->copy()->day($day);

                if ($date->isWeekday()) {
                    $weekdays[] = $date;
                }
            }

            // 各月の平日15日分
            foreach (array_slice($weekdays, 0, 15) as $date) {
                $this->createAttendance(
                    $user,
                    $date,
                    '09:00:00',
                    '18:00:00'
                );
            }
        }
    }

    /**
     * user1：当月17日の意図的な勤務パターン
     */
    private function createUser1CurrentMonth(User $user): void
    {
        $today = Carbon::today();

        $weekdays = [];

        for ($day = 1; $day <= $today->daysInMonth; $day++) {
            $date = $today->copy()->day($day);

            if ($date->isWeekday()) {
                $weekdays[] = $date;
            }
        }

        // 当月の平日17日分
        // 課題のダミーデータ作成を優先し、
        // 今日より後の日付も使用する
        $dates = array_slice($weekdays, 0, 17);

        // 通常勤務 10日
        for ($i = 0; $i < 10; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:00:00',
                '18:00:00'
            );
        }

        // 残業 3日
        for ($i = 10; $i < 13; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:00:00',
                '20:00:00'
            );
        }

        // 遅刻 2日
        for ($i = 13; $i < 15; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:30:00',
                '18:00:00'
            );
        }

        // 早退 1日
        $this->createAttendance(
            $user,
            $dates[15],
            '09:00:00',
            '17:00:00'
        );

        // 長時間労働 1日
        $this->createAttendance(
            $user,
            $dates[16],
            '08:00:00',
            '21:00:00'
        );
    }

    /**
     * user2・user3：通常のダミー勤怠
     */
    private function createNormalAttendance(User $user): void
    {
        $today = Carbon::today();

        $weekdays = [];

        for ($day = 1; $day <= $today->daysInMonth; $day++) {
            $date = $today->copy()->day($day);

            if ($date->isWeekday()) {
                $weekdays[] = $date;
            }
        }

        // 当月の平日15日分
        foreach (array_slice($weekdays, 0, 15) as $date) {
            $this->createAttendance(
                $user,
                $date,
                '09:00:00',
                '18:00:00'
            );
        }
    }

    /**
     * 勤怠と休憩を1件ずつ作成する
     */
    private function createAttendance(
        User $user,
        Carbon $date,
        string $clockIn,
        string $clockOut
    ): void {
        $clockInTime = Carbon::parse($clockIn);
        $clockOutTime = Carbon::parse($clockOut);

        // 勤務時間を分単位で計算
        $totalMinutes = $clockInTime->diffInMinutes($clockOutTime);

        // 休憩時間は1時間
        $totalBreakMinutes = 60;

        // 実働時間
        $totalWorkMinutes = $totalMinutes - $totalBreakMinutes;

        // TIME型に変換
        $totalBreakTime = sprintf(
            '%02d:%02d:00',
            intdiv($totalBreakMinutes, 60),
            $totalBreakMinutes % 60
        );

        $totalTime = sprintf(
            '%02d:%02d:00',
            intdiv($totalWorkMinutes, 60),
            $totalWorkMinutes % 60
        );

        // 勤怠レコード作成
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => null,
            'total_break_time' => $totalBreakTime,
            'total_time' => $totalTime,
        ]);

        // 休憩レコード作成
        BreakRecord::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}