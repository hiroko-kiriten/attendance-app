<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 休憩記録を扱うモデル
class BreakRecord extends Model
{
    use HasFactory;

    // 使用するテーブルを指定する
    protected $table = 'breaks';

    // 一括代入を許可する項目
    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    // 勤怠記録とのリレーション
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}