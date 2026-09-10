<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * ログインユーザーの勤怠修正申請一覧を表示する
     *
     * @return View
     */
    public function index(): View
    {
        // 現在ログインしているユーザーを取得する
        $user = Auth::user();

        // ログインユーザーの勤怠修正申請を取得する
        $applications = AttendanceCorrectionRequest::with('attendanceRecord')
            ->where('user_id', $user->id)
            ->get();

        // 申請一覧を画面表示用の形式に整える
        $formattedApplications = $applications->map(function ($application) {
            return [
                // 申請IDを設定する
                'id' => $application->id,

                // 承認状態を設定する
                'approval_status' => $application->approval_status,

                // 修正後の日付を指定した形式に変換する
                'date' => $application->new_date->format('Y/m/d'),

                // 申請時のコメントを設定する
                'comment' => $application->comment,

                // 申請日を設定する
                'application_date' => $application->application_date,
            ];
        });

        // 整形した申請一覧とユーザー情報をViewへ渡す
        return view('user.user-application-list', compact(
            'formattedApplications',
            'user'
        ));
    }
}