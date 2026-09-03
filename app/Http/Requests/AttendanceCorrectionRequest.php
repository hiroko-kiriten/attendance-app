<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_date' => ['required', 'date_format:Y-m-d'],
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
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
            'new_break_out.*.date_format' => '休憩終了時間の形式が不適切です。',

            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は255文字以内で入力してください。',
        ];
    }
}