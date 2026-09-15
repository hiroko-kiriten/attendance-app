<?php

// このRequestがAPI v1用であることを指定する

namespace App\Http\Requests\Api\V1;

// LaravelのFormRequestを読み込む
use Illuminate\Foundation\Http\FormRequest;

// 勤怠更新時のバリデーションを担当するRequest
class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * リクエストを許可する
     */
    public function authorize(): bool
    {
        // 認証後のユーザーによる勤怠更新を許可する
        return true;
    }

    /**
     * バリデーションルールを定義する
     */
    public function rules(): array
    {
        return [
            // 勤怠日は必須のYYYY-MM-DD形式にする
            'date' => ['required', 'date_format:Y-m-d'],

            // 出勤時刻は必須の時刻形式にする
            'clock_in' => ['required', 'date_format:H:i:s'],

            // 退勤時刻は任意の時刻形式にする
            'clock_out' => ['nullable', 'date_format:H:i:s'],

            // コメントは任意の文字列にする
            'comment' => ['nullable', 'string'],

            // 休憩時間は任意の時刻形式にする
            'total_break_time' => ['nullable', 'date_format:H:i:s'],

            // 勤務時間は任意の時刻形式にする
            'total_time' => ['nullable', 'date_format:H:i:s'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義する
     */
    public function messages(): array
    {
        return [
            // 勤怠日の必須エラー
            'date.required' => '勤怠日は必須です。',

            // 勤怠日の形式エラー
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',

            // 出勤時刻の必須エラー
            'clock_in.required' => '出勤時刻は必須です。',

            // 出勤時刻の形式エラー
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',

            // 退勤時刻の形式エラー
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',

            // コメントの形式エラー
            'comment.string' => 'コメントは文字列で指定してください。',

            // 休憩時間の形式エラー
            'total_break_time.date_format' => '休憩時間は HH:MM:SS 形式で指定してください。',

            // 勤務時間の形式エラー
            'total_time.date_format' => '勤務時間は HH:MM:SS 形式で指定してください。',
        ];
    }
}
