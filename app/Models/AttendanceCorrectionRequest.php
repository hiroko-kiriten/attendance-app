<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
    'attendance_record_id',
    'user_id',
    'new_clock_in',
    'new_clock_out',
    'comment',
    'new_date',
    'approval_status',
];
    
    protected $casts = [
    'new_date' => 'date',
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }


     public function attendanceCorrectionRequestBreaks()
     {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class);
     } 

    }

