<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreakRecordResource extends JsonResource
{
    /**
     * APIで返す休憩データを定義する
     */
    public function toArray(Request $request): array
    {
        return [
            // 休憩ID
            'id' => $this->id,

            // 勤怠ID
            'attendance_record_id' => $this->attendance_record_id,

            // 休憩開始時刻
            'break_in' => $this->break_in,

            // 休憩終了時刻
            'break_out' => $this->break_out,
        ];
    }
}