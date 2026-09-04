<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin-login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->validated();

        if (
            Auth::attempt([
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'admin_status' => true,
            ])
        ) {
            $request->session()->regenerate();

            return redirect('/admin/attendance/list');
        }

        return back()
            ->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])
            ->withInput($request->only('email'));
    }
}