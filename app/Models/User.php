<?php

namespace App\Models;

// メールアドレス認証に必要なインターフェース
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    // ユーザー情報や通知などの機能を利用する
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
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
    //ユーザーが申請した勤怠修正申請を取得する
    public function attendanceCorrectionRequests()
{
    return $this->hasMany(AttendanceCorrectionRequest::class);
}

}
