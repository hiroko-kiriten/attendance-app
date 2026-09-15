<?php

// このファイルがApp\Providers名前空間に属することを指定する
namespace App\Providers;

// 勤怠記録モデルを読み込む
use App\Models\AttendanceRecord;

// 勤怠記録に対応するPolicyを読み込む
use App\Policies\AttendanceRecordPolicy;

// 認証・認可の基底クラスを読み込む
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// 認証・認可のサービスプロバイダーを定義する
class AuthServiceProvider extends ServiceProvider
{
    /**
     * モデルとPolicyの対応を定義する
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // AttendanceRecordの認可にはAttendanceRecordPolicyを使用する
        AttendanceRecord::class => AttendanceRecordPolicy::class,
    ];

    /**
     * 認証・認可の設定を登録する
     */
    public function boot(): void
    {
        // 今回は追加設定を行わない
    }
}