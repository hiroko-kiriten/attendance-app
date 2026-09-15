<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCorrectionRequestResource extends JsonResource
{
    /**
     * APIで返す修正申請データを定義する
     */
    public function toArray(Request $request): array
    {
        return [
            // 修正申請ID
            'id' => $this->id,

            // 勤怠ID
            'attendance_record_id' => $this->attendance_record_id,

            // ユーザーID
            'user_id' => $this->user_id,

            // 修正後の日付
            'new_date' => $this->new_date,

            // 修正後の出勤時刻
            'new_clock_in' => $this->new_clock_in,

            // 修正後の退勤時刻
            'new_clock_out' => $this->new_clock_out,

            // 修正理由
            'comment' => $this->comment,

            // 承認ステータス
            'approval_status' => $this->approval_status,

            // 申請日時
            'application_date' => $this->application_date,
        ];
    }
}