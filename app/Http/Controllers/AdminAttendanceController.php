<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;
use App\Http\Requests\AdminAttendanceRequest;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
 * 指定した日の全ユーザーの勤怠一覧を表示する
 *
 * @param Request $request
 * @return View
 */
    public function index(Request $request): View
    {
        // 表示する日付を決める
        $date = $request->date
            ? Carbon::parse($request->date)
            : now();

        // 前日・翌日を取得
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 全ユーザーを取得
        $users = User::all();

        // 指定した日の勤怠記録と、ユーザー・休憩情報をまとめて取得
        $attendanceRecords = AttendanceRecord::with(['user', 'breaks'])
        ->where('date', $date->format('Y-m-d'))
        ->get();

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    /**
 * 指定した勤怠記録の詳細を表示する
 *
 * @param int $id
 * @return View
 */
   public function show(int $id): View
{    
    // 指定したIDの勤怠記録とユーザー・休憩情報を取得
    $attendanceRecord = AttendanceRecord::with(['user', 'breaks'])
        ->findOrFail($id);

    $user = $attendanceRecord->user;

    // Bladeが指定している形式に合わせて休憩を配列にする
    $attendanceRecord['breaks'] = $attendanceRecord->breaks->toArray();
     // 画面へ渡す休憩情報を取得
    $breaks = $attendanceRecord['breaks'];
    // 勤怠詳細画面へデータを渡す
    return view('admin.admin-detail', compact(
        'attendanceRecord',
        'user',
        'breaks'
    ));
}

    /**
 * 管理者が勤怠記録を修正する
 *
 * @param AdminAttendanceRequest $request
 * @param int $id
 * @return RedirectResponse
 */
 public function update(AdminAttendanceRequest $request, int $id): RedirectResponse
{
    // 修正対象の勤怠記録を取得
    $attendanceRecord = AttendanceRecord::findOrFail($id);
    // 勤怠記録と休憩記録の更新をトランザクションで実行
    DB::transaction(function () use ($attendanceRecord, $request) {

        // 出勤・退勤・備考を修正して勤怠記録を更新
        $attendanceRecord->update([
            'clock_in' => $request->new_clock_in,
            'clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
        ]);

        // 修正された休憩時間を取得
        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);

        // 既存の休憩をすべて削除
        $attendanceRecord->breaks()->delete();

        // 休憩時間の合計を0分で初期化
        $totalBreakTime = 0;

        foreach ($breakIns as $index => $breakIn) {

            // 対応する休憩終了時間を取得
            $breakOut = $breakOuts[$index] ?? null;

            // 休憩開始・終了の両方が入力されている場合
            if ($breakIn && $breakOut) {

                // Carbonに変換
                $breakStart = Carbon::parse($breakIn);
                $breakEnd = Carbon::parse($breakOut);

                // 1回分の休憩時間を分単位で計算
                $breakMinutes = $breakStart->diffInMinutes($breakEnd);

                // 合計休憩時間に加算
                $totalBreakTime += $breakMinutes;

                // 1件ずつ新しい休憩を登録
                $attendanceRecord->breaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        }

        // 合計休憩時間を保存
        $attendanceRecord->update([
            'total_break_time' => Carbon::createFromTime(0, 0, 0)
                ->addMinutes($totalBreakTime)
                ->format('H:i:s'),
        ]);

        // 出勤・退勤時間が両方ある場合
        if ($attendanceRecord->clock_in && $attendanceRecord->clock_out) {

            $clockIn = Carbon::parse($attendanceRecord->clock_in);
            $clockOut = Carbon::parse($attendanceRecord->clock_out);

            // 出勤から退勤までの時間を分で計算
            $totalTime = $clockIn->diffInMinutes($clockOut);

            // 休憩時間を引く
            $totalTime -= $totalBreakTime;

            // TIME型に変換して勤務時間を保存
            $attendanceRecord->update([
                'total_time' => Carbon::createFromTime(0, 0, 0)
                    ->addMinutes($totalTime)
                    ->format('H:i:s'),
            ]);
        }
    });

    // 修正後の画面へ戻る
    return redirect()->route('admin.attendance.detail', $id);
}
}