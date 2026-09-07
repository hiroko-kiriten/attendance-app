<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminApplicationController;

class ApplicationListMiddleware
{
    public function handle(Request $request, Closure $next)//handle() → Middlewareの処理を行うメソッド。リクエストを受け取って、必要な処理をした後、次へ渡す。
    {
        if (auth()->user()->admin_status) {
            $view = app(AdminApplicationController::class)->index();//管理者だったら、AdminApplicationController の index() を実行して、申請一覧の画面を作る


            return response($view);//作った申請一覧の画面を、HTTPレスポンスとして返す
        }//ifはここまで

        return $next($request);//管理者ではなかった場合、受け取ったリクエストをそのまま次の処理へ渡す
    }
}