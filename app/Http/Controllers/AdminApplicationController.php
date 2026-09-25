<?php

namespace App\Http\Controllers;

// DBトランザクションを使用するため読み込む
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCorrectionRequest;
// リダイレクトレスポンスの型を使用するため読み込む
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminApplicationController extends Controller
{
    /**
     * 勤怠修正申請の一覧を表示する
     *
     * @return View
     */
    public function index(): View
    {
        // 勤怠修正申請とユーザー・勤怠記録をまとめて取得する
        $applications = AttendanceCorrectionRequest::with([
            'user',
            'attendanceRecord'
        ])->get();

        // 管理者の申請一覧画面へデータを渡す
        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * 指定した勤怠修正申請の詳細を表示する
     *
     * @param int $attendance_correct_request_id
     * @return View
     */
    public function show(int $attendance_correct_request_id): View
    {
        // 指定したIDの申請とユーザー・修正休憩情報を取得する
        $application = AttendanceCorrectionRequest::with([
            'user',
            'attendanceCorrectionRequestBreaks'
        ])->findOrFail($attendance_correct_request_id);

        // 申請者のユーザー情報を取得する
        $user = $application->user;

        // 管理者の申請詳細画面へデータを渡す
        return view('admin.admin-application-detail', compact(
            'application',
            'user'
        ));
    }

    /**
     * 勤怠修正申請を承認する
     *
     * @param int $attendance_correct_request_id
     * @return RedirectResponse
     */
    public function approve(int $attendance_correct_request_id): RedirectResponse
    {
        // 勤怠情報と申請情報の更新をトランザクションで実行する
        DB::transaction(function () use ($attendance_correct_request_id) {

            // 指定したIDの申請と勤怠・修正休憩情報を取得する
            $application = AttendanceCorrectionRequest::with([
                'attendanceRecord',
                'attendanceCorrectionRequestBreaks'
            ])->findOrFail($attendance_correct_request_id);

            // 修正対象の勤怠記録を取得する
            $attendanceRecord = $application->attendanceRecord;

            // 勤怠情報を修正申請の内容で更新
            $attendanceRecord->update([
                'date' => $application->new_date,
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 既存の休憩を削除
            $attendanceRecord->breaks()->delete();

            // 修正申請された休憩を登録
            foreach ($application->attendanceCorrectionRequestBreaks as $requestBreak) {

                // 修正申請の休憩情報を勤怠記録へ登録する
                $attendanceRecord->breaks()->create([
                    'break_in' => $requestBreak->break_in,
                    'break_out' => $requestBreak->break_out,
                ]);
            }

            // 申請を承認済みに変更
            $application->approval_status = '承認済み';

            // 変更した申請情報を保存する
            $application->save();
        });

        // 承認処理後に申請一覧画面へ戻る
        return redirect('/stamp_correction_request/list');
    }
}