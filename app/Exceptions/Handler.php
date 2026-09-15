<?php

// このファイルがApp\Exceptions名前空間に属することを指定する
namespace App\Exceptions;

// Laravelの例外処理基底クラスを読み込む
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

// 例外クラスの型を読み込む
use Throwable;

// API例外をJSONで返すためのRequestクラスを読み込む
use Illuminate\Http\Request;

// HTTP例外を読み込む
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// 例外処理を担当するクラス
class Handler extends ExceptionHandler
{
    /**
     * セッションに保存しない入力項目を定義する
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 例外処理のコールバックを登録する
     */
    public function register(): void
    {
        // APIの例外をJSON形式で返す
        $this->renderable(function (Throwable $e, Request $request) {
            // APIリクエストの場合だけJSONレスポンスを返す
            if ($request->is('api/*')) {
                // HTTPステータスコードを取得する
                $status = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                // エラーメッセージをJSONで返す
                return response()->json([
                    'message' => $e->getMessage(),
                ], $status);
            }
        });

        // 通常の例外レポート処理
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}