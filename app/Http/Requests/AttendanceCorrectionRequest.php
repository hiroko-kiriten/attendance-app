<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $attendanceRecord = AttendanceRecord::where('user_id', auth()->id())
            ->find($this->route('id'));

        if ($attendanceRecord) {
            $this->merge([
                'new_date' => Carbon::parse($attendanceRecord->date)->format('Y-m-d'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'new_date' => ['required', 'date_format:Y-m-d'],

            'new_clock_in' => [
                'required',
                'date_format:H:i',
            ],

            'new_clock_out' => [
                'required',
                'date_format:H:i',
                'after:new_clock_in',
            ],

            'new_break_in.*' => [
                'nullable',
                'date_format:H:i',
                'required_with:new_break_out.*',
            ],

            'new_break_out.*' => [
                'nullable',
                'date_format:H:i',
                'required_with:new_break_in.*',
            ],

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
            'new_date.required' => '日付を入力してください。',
            'new_date.date_format' => '日付の形式が不適切です。',

            'new_clock_in.required' => '出勤時間を入力してください。',
            'new_clock_in.date_format' => '出勤時間の形式が不適切です。',

            'new_clock_out.required' => '退勤時間を入力してください。',
            'new_clock_out.date_format' => '退勤時間の形式が不適切です。',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',

            'new_break_in.*.date_format' => '休憩開始時間の形式が不適切です。',
            'new_break_in.*.required_with' => '休憩終了時間を入力してください。',

            'new_break_out.*.date_format' => '休憩終了時間の形式が不適切です。',
            'new_break_out.*.required_with' => '休憩開始時間を入力してください。',

            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は255文字以内で入力してください。',
        ];
    }
}