<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // このRequestの利用を許可する
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 修正対象の勤怠IDを取得
        $id = $this->route('id');

        // 管理者はユーザーを限定せず勤怠を取得
        if (auth()->user()?->admin_status) {
            $attendanceRecord = AttendanceRecord::find($id);
        } else {
            // 一般ユーザーは自分の勤怠だけ取得
            $attendanceRecord = AttendanceRecord::where('user_id', auth()->id())
                ->find($id);
        }

        // 勤怠日をnew_dateとしてセット
        if ($attendanceRecord) {
            $this->merge([
                'new_date' => Carbon::parse($attendanceRecord->date)->format('Y-m-d'),
            ]);
        }
    }

    public function rules(): array
    {
        // 管理者は秒まで、一般ユーザーは分までの時刻形式を使用
        $timeFormat = auth()->user()?->admin_status ? 'H:i:s' : 'H:i';

        return [
            // 勤怠日
            'new_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            // 出勤時間
            'new_clock_in' => [
                'required',
                "date_format:{$timeFormat}",
            ],

            // 退勤時間
            'new_clock_out' => [
                'required',
                "date_format:{$timeFormat}",
                'after:new_clock_in',
            ],

            // 休憩開始時間
            'new_break_in.*' => [
                'nullable',
                "date_format:{$timeFormat}",
                'required_with:new_break_out.*',
            ],

            // 休憩終了時間
            'new_break_out.*' => [
                'nullable',
                "date_format:{$timeFormat}",
                'required_with:new_break_in.*',
            ],

            // 備考
            'comment' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // 日付
            'new_date.required' => '日付を入力してください。',
            'new_date.date_format' => '日付の形式が不適切です。',

            // 出勤時間
            'new_clock_in.required' => '出勤時間を入力してください。',
            'new_clock_in.date_format' => '出勤時間の形式が不適切です。',

            // 退勤時間
            'new_clock_out.required' => '退勤時間を入力してください。',
            'new_clock_out.date_format' => '退勤時間の形式が不適切です。',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',

            // 休憩開始時間
            'new_break_in.*.date_format' => '休憩開始時間の形式が不適切です。',
            'new_break_in.*.required_with' => '休憩終了時間を入力してください。',

            // 休憩終了時間
            'new_break_out.*.date_format' => '休憩終了時間の形式が不適切です。',
            'new_break_out.*.required_with' => '休憩開始時間を入力してください。',

            // 備考
            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は255文字以内で入力してください。',
        ];
    }
}