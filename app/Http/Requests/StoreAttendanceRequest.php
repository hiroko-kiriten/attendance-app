<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    // このリクエストを許可する
    public function authorize(): bool
    {
        return true;
    }

    // バリデーションルールを定義する
    public function rules(): array
    {
        return [
            // actionは4種類のいずれかを必須とする
            'action' => ['required', 'in:clock_in,clock_out,break_in,break_out'],
        ];
    }
}