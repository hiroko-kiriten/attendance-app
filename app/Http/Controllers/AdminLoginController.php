<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログイン画面を表示する
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        // 管理者ログイン画面を表示する
        return view('admin.admin-login');
    }

    /**
     * 管理者ログイン処理を行う
     *
     * @param AdminLoginRequest $request
     * @return RedirectResponse
     */
    public function login(AdminLoginRequest $request): RedirectResponse
    {
        // バリデーション済みのログイン情報を取得する
        $credentials = $request->validated();

        // メールアドレス・パスワード・管理者権限を確認してログインする
        if (
            Auth::attempt([
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'admin_status' => true,
            ])
        ) {
            // ログイン成功時にセッションIDを再生成する
            $request->session()->regenerate();

            // 管理者の勤怠一覧画面へ移動する
            return redirect('/admin/attendance/list');
        }

        // ログインに失敗した場合はエラーメッセージを表示して戻る
        return back()
            ->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])
            // 入力されたメールアドレスを保持する
            ->withInput($request->only('email'));
    }
}