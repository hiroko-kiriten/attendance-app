<?php

// このControllerが所属する名前空間を指定
namespace App\Http\Controllers;

// Userモデルを使用するため読み込む
use App\Models\User;

// AttendanceRecordモデルを使用するため読み込む
use App\Models\AttendanceRecord;

// 日付・時刻を扱うCarbonを読み込む
use Carbon\Carbon;

// HTTPリクエストを受け取るRequestを読み込む
use Illuminate\Http\Request;

// 管理者側のスタッフ関連処理をまとめるController
class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧画面
     */
    public function index()
    {
        // 管理者ではない一般ユーザーだけを取得する
        $users = User::where('admin_status', false)->get();

        // スタッフ一覧画面に取得したユーザー情報を渡す
        return view('admin.staff-list', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧画面
     */
    public function show(Request $request, $id)
    {
        // 指定されたIDの一般ユーザーを取得する
        // admin_statusがfalseなので管理者は対象外
        // 該当するユーザーがいなければ404エラーを表示する
        $user = User::where('admin_status', false)
            ->findOrFail($id);

        // URLにdateが指定されていれば、その日付を使用する
        // 指定されていなければ、現在の日付を使用する
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::now();

        // 指定されたスタッフの勤怠記録を取得する
        // $dateと同じ年・月の勤怠だけを取得する
        // 日付の新しい順に並べる
        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')
            ->get();

        // 現在表示している月の前月を取得する
        $previousMonth = $date->copy()
            ->subMonth()
            ->format('Y-m');

        // 現在表示している月の翌月を取得する
        $nextMonth = $date->copy()
            ->addMonth()
            ->format('Y-m');

        // Bladeで表示しやすい形に勤怠データを整える
        $formattedAttendanceRecords = $attendanceRecords->map(function ($attendanceRecord) {

            // 1件の勤怠記録を表示用の配列に変換する
            return [
                // 勤怠記録のID
                'id' => $attendanceRecord->id,

                // 日付を「月/日」の形式にする
                'date' => Carbon::parse($attendanceRecord->date)->format('m/d'),

                // 出勤時間を「時:分」の形式にする
                // 出勤時間がなければ空文字にする
                'clock_in' => $attendanceRecord->clock_in
                    ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
                    : '',

                // 退勤時間を「時:分」の形式にする
                // 退勤時間がなければ空文字にする
                'clock_out' => $attendanceRecord->clock_out
                    ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                    : '',

                // 合計休憩時間
                'total_break_time' => $attendanceRecord->total_break_time,

                // 合計勤務時間
                'total_time' => $attendanceRecord->total_time,
            ];
        });

        // スタッフ別勤怠一覧画面を表示する
        // Bladeで使用するデータをまとめて渡す
        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}