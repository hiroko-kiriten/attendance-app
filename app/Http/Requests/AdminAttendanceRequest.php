<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceRequest extends FormRequest
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
        'new_clock_in' => ['required', 'date_format:H:i:s'],//出勤時間。必須、09:00のような形式。
        'new_clock_out' => ['required', 'date_format:H:i:s', 'after:new_clock_in'],//退勤時間。必須、出勤時間より後。
        'new_break_in.*' => ['nullable', 'date_format:H:i:s', 'required_with:new_break_out.*'],
        'new_break_out.*' => ['nullable', 'date_format:H:i:s', 'required_with:new_break_in.*'],//休憩時間。複数あるので * を使っています。空欄も許可。
        'comment' => ['nullable', 'string', 'max:255'],
    ];
}

    public function messages(): array
{
    return [
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