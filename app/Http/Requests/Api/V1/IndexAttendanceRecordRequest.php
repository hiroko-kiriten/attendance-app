<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * リクエストを許可する
     */
    public function authorize(): bool
    {
        // 一覧APIは認証不要なので、常に許可する
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            // ユーザーIDは指定された場合のみ整数にする
            'user_id' => ['nullable', 'integer'],

            // 日付はYYYY-MM-DD形式にする
            'date' => ['nullable', 'date_format:Y-m-d'],

            // 月はYYYY-MM形式にする
            'month' => ['nullable', 'date_format:Y-m'],

            // ページ番号は指定された場合のみ整数にする
            'page' => ['nullable', 'integer', 'min:1'],

            // 1ページあたりの件数は1〜100件にする
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'user_id.integer' => 'ユーザーIDは整数で指定してください。',
            'date.date_format' => '日付は YYYY-MM-DD 形式で指定してください。',
            'month.date_format' => '月は YYYY-MM 形式で指定してください。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
            'per_page.integer' => '1ページあたりの件数は整数で指定してください。',
            'per_page.min' => '1ページあたりの件数は1以上で指定してください。',
            'per_page.max' => '1ページあたりの件数は100以下で指定してください。',
        ];
    }
}