<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 勤怠修正申請の休憩時間を扱うモデル
class AttendanceCorrectionRequestBreak extends Model
{
    use HasFactory;

    // 一括代入を許可する項目
    protected $fillable = [
        'attendance_correction_request_id',
        'break_in',
        'break_out',
    ];

    // 勤怠修正申請との関連
    public function attendanceCorrectionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class);
    }
}