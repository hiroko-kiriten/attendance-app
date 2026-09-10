<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;//FormRequestと同じ名前になるのを避けるため
use App\Models\AttendanceRecord;
use Carbon\Carbon;//日付・時刻を扱うためのクラス
use Illuminate\Http\Request;//HTTPリクエストを受け取るためのlaravelのクラス
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{

/**
 * 勤怠登録・出退勤・休憩の処理を行う
 *
 * @param StoreAttendanceRequest $request
 * @return RedirectResponse
 */
    public function store(StoreAttendanceRequest $request): RedirectResponse
{
    $user = auth()->user();//現在ログインしているユーザーの情報を取得して $user に代入

    $attendanceRecord = AttendanceRecord::where('user_id', $user->id)//ログインユーザーのIDと一致する勤怠記録を検索
        ->whereDate('date', now()->toDateString())//勤怠記録の日付が今日の日付と一致するものに絞り込む
        ->first();//検索結果の最初の1件を取得

    if ($request->action === 'clock_in') {//送信された操作が「出勤（clock_in）」だった場合に処理を実行する
         if (!$attendanceRecord) {  //勤怠記録がまだ存在しない場合に処理を実行する  
            AttendanceRecord::create([//勤怠記録をデータベースに新しく登録
            'user_id' => $user->id,//勤怠記録にログインユーザーのIDを設定
            'date' => now()->toDateString(),//勤怠記録の日付に今日の日付を設定
            'clock_in' => now(),//勤怠記録に今の時間を設定
        ]);

        }
    }

if ($request->action === 'clock_out') {//送信された操作が「退勤（clock_out）」だった場合に処理を実行
    if (
        $attendanceRecord &&
        $attendanceRecord->clock_in &&
        !$attendanceRecord->clock_out &&
        !$attendanceRecord->breaks()//休憩記録を取得
            ->whereNull('break_out')//休憩終了記録が未入力
            ->exists()//そのような記録が存在するか確認
    ) {//勤怠記録が存在し、出勤済みで、退勤前で、休憩中ではない場合に処理を実行
        $clockOut = now();
        $clockIn = Carbon::parse($attendanceRecord->clock_in);

        // 出勤から退勤までの経過時間
        $workSeconds = $clockIn->diffInSeconds($clockOut);

        // 完了した休憩だけを対象に、合計休憩時間を秒数で計算
    $totalBreakSeconds = $attendanceRecord->breaks
        ->filter(function ($breakRecord) {
            // 休憩開始・終了の両方が登録されている休憩だけ残す
            return $breakRecord->break_in && $breakRecord->break_out;
        })
        ->sum(function ($breakRecord) {
            // 休憩開始時刻をCarbonに変換
            $breakIn = Carbon::parse($breakRecord->break_in);

            // 休憩終了時刻をCarbonに変換
            $breakOut = Carbon::parse($breakRecord->break_out);

            // この休憩の秒数を返す
            return $breakIn->diffInSeconds($breakOut);
        });

            // 実働時間 = 出勤から退勤までの時間 - 休憩時間
            $totalTimeSeconds = $workSeconds - $totalBreakSeconds;

            // 秒 → 時・分・秒に変換
            $hours = floor($totalTimeSeconds / 3600);
            $minutes = floor(($totalTimeSeconds % 3600) / 60);
            $seconds = $totalTimeSeconds % 60;

            $attendanceRecord->update([
                'clock_out' => $clockOut,
                'total_time' => sprintf(
                    '%02d:%02d:%02d',
                    $hours,
                    $minutes,
                    $seconds
            ),
        ]);
    }
}

    if ($request->action === 'break_in') {
         if (
        $attendanceRecord &&
        !$attendanceRecord->clock_out &&
        !$attendanceRecord->breaks()
            ->whereNull('break_out')
            ->exists()//出勤済み・退勤していない・現在休憩中ではない場合に休憩開始できる
    ) {
        $attendanceRecord->breaks()->create([
            'break_in' => now(),
        ]);
    }
    }    

   if ($request->action === 'break_out') {
    if ($attendanceRecord && !$attendanceRecord->clock_out) {
        $break = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();//終了していない休憩を探して終了時刻を入れる処理

        if ($break) {
    $break->update([
        'break_out' => now(),
    ]);

    // 完了した休憩だけを対象に、合計休憩時間を秒数で計算
    $totalBreakSeconds = $attendanceRecord->breaks
    ->filter(function ($breakRecord) {
        // 休憩開始・終了の両方が登録されている休憩だけ残す
        return $breakRecord->break_in && $breakRecord->break_out;
    })
    ->sum(function ($breakRecord) {
        // 休憩開始時刻をCarbonに変換
        $breakIn = Carbon::parse($breakRecord->break_in);

        // 休憩終了時刻をCarbonに変換
        $breakOut = Carbon::parse($breakRecord->break_out);

        // この休憩の秒数を返す
        return $breakIn->diffInSeconds($breakOut);
    });

    $hours = floor($totalBreakSeconds / 3600);//合計休憩秒数を3600（1時間＝3600秒）で割り、整数部分だけを取り出して「時間」に変換
    $minutes = floor(($totalBreakSeconds % 3600) / 60);//floor（床） から来ていて、小数点以下を切り捨てて、下の整数にする関数
    $seconds = $totalBreakSeconds % 60;

    $attendanceRecord->update([
        'total_break_time' => sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        ),
    ]);
}
    }
}
    return redirect('/attendance/list');
}

/**
 * 勤怠登録画面を表示する
 *
 * @return View
 */
    public function register(): View
{
    $user = auth()->user();

     $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
        ->whereDate('date', now()->toDateString())
        ->with('breaks')
        ->first();

    if (!$attendanceRecord) {
        $attendanceStatus = '勤務外';
    } elseif ($attendanceRecord->clock_out) {
        $attendanceStatus = '退勤済';
    } elseif ($attendanceRecord->breaks->contains(function ($break) {//休憩記録の中に、条件（休憩中）に当てはまるものが1件でもあるかを確認
        return $break->break_in && !$break->break_out;
    })) {
        $attendanceStatus = '休憩中';
    } else {
        $attendanceStatus = '出勤中';
    }

    // 完成品のBladeが $user->attendance_status を使うため、
    // 画面表示用に一時的に値を設定する
    $user->attendance_status = $attendanceStatus;

    return view('user.attendance-register', [
        'user' => $user,
        'formattedDate' => now()->format('Y年m月d日'),
        'formattedTime' => now()->format('H:i'),
    ]);
}
/**
 * ログインユーザーの指定月の勤怠一覧を表示する
 *
 * @param Request $request
 * @return View
 */
    public function index(Request $request): View
    {
        // 表示する年月を取得
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::now();

        // ログインユーザーの指定月の勤怠を取得
        $attendanceRecords = AttendanceRecord::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')           
            ->get();

        // 前月・翌月
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 一覧表示用に整形
        $formattedAttendanceRecords = $attendanceRecords->map(function ($attendanceRecord) {
            return [
                'id' => $attendanceRecord->id,
                'date' => Carbon::parse($attendanceRecord->date)->format('m/d'),
                'clock_in' => $attendanceRecord->clock_in
                    ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendanceRecord->clock_out
                    ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                    : '',
                'total_break_time' => $attendanceRecord->total_break_time,
                'total_time' => $attendanceRecord->total_time,
            ];
        });

        return view('user.user-attendance-list', compact(//compact() は、指定した変数をまとめて配列にして、Blade（View）へ渡すためのPHP関数
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
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
    $attendanceRecord = AttendanceRecord::with('breaks')
        ->where('user_id', auth()->id())
        ->findOrFail($id);//指定したIDのデータを取得し、見つからなければ404エラーを発生させる

    $user = $attendanceRecord->user;

    $application = AttendanceCorrectionRequestModel::where(
        'attendance_record_id',
        $attendanceRecord->id
    )
    ->where('approval_status', '承認待ち')
    ->first();

    $breaks = $attendanceRecord->breaks;

    $data = [
        'id' => $attendanceRecord->id,
        'year' => Carbon::parse($attendanceRecord->date)->format('Y年'),
        'date' => Carbon::parse($attendanceRecord->date)->format('n月j日'),
        'clock_in' => $attendanceRecord->clock_in
            ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
            : '',
        'clock_out' => $attendanceRecord->clock_out
            ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
            : '',
        'comment' => $attendanceRecord->comment,
        'application' => $application,
        'breaks' => $breaks->map(function ($break) {
            return [
                'break_in' => $break->break_in
                    ? Carbon::parse($break->break_in)->format('H:i')
                    : '',
                'break_out' => $break->break_out
                    ? Carbon::parse($break->break_out)->format('H:i')
                    : '',
            ];
        })->toArray(),
    ];

        return view('user.user-detail', compact(
            'data',
            'user'
        ));
}

/**
 * 勤怠修正申請を登録する
 *
 * @param AttendanceCorrectionRequest $request
 * @param int $id
 * @return RedirectResponse
 */
    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $attendanceRecord = AttendanceRecord::where('user_id', auth()->id())
    ->findOrFail($id);

        $application = AttendanceCorrectionRequestModel::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => auth()->id(),
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
            'new_date' => $request->new_date,
            'approval_status' => '承認待ち',
            'application_date' => now()->toDateString(),
        ]);

        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);

        foreach ($breakIns as $index => $breakIn) {
            $breakOut = $breakOuts[$index] ?? null;

            // 両方空欄なら登録しないで次の処理に進む
            if (empty($breakIn) && empty($breakOut)) {
                continue;
            }

            $application->attendanceCorrectionRequestBreaks()->create([
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        }
        return redirect('/attendance/list');
    }
}