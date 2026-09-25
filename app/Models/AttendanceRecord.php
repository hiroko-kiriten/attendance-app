<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 勤怠記録を扱うモデル
class AttendanceRecord extends Model
{
    use HasFactory;

    // 一括代入を許可する項目
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'total_break_time',
        'total_time',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 休憩記録とのリレーション
    public function breaks()
    {
        return $this->hasMany(BreakRecord::class);
    }

    // 修正申請とのリレーション
    public function applications()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}