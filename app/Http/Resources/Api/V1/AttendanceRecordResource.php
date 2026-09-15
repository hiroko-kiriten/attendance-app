<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * APIで返すデータを定義する
     */
    public function toArray(Request $request): array
    {
        return [
            // 勤怠ID
            'id' => $this->id,

            // ユーザーID
            'user_id' => $this->user_id,

        // userがEager Loadingされている場合だけ返す
        'user' => $this->whenLoaded('user', function () {
            return [
                // ユーザーID
                'id' => $this->user->id,

                // ユーザー名
                'name' => $this->user->name,
            ];
    }),

            // 勤怠日
            'date' => $this->date,

            // 出勤時刻
            'clock_in' => $this->clock_in,

            // 退勤時刻
            'clock_out' => $this->clock_out,

            // 勤務時間
            'total_time' => $this->total_time,

            // 休憩時間
            'total_break_time' => $this->total_break_time,

            // 備考
            'comment' => $this->comment,

            // breaksがEager Loadingされている場合だけ返す
            'breaks' => BreakRecordResource::collection(
                $this->whenLoaded('breaks')
            ),

            // applicationsがEager Loadingされている場合だけ返す
            'applications' => AttendanceCorrectionRequestResource::collection(
                $this->whenLoaded('applications')
            ),
        ];
    }
}