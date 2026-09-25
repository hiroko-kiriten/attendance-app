<?php

namespace App\Models;

// メールアドレス認証に必要なインターフェース
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// ユーザー情報を扱うモデル
class User extends Authenticatable implements MustVerifyEmail
{
    // API認証、ファクトリ、通知の機能を利用する
    use HasApiTokens, HasFactory, Notifiable;

    // 一括代入を許可する項目
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    // シリアライズ時に非表示にする項目
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 属性の型を指定する項目
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    // ユーザーが持っている勤怠記録を取得する
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // ユーザーが申請した勤怠修正申請を取得する
    public function attendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}