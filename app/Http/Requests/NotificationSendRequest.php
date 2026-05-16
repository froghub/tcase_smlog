<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationSendRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required','in:sms,email'],
            'message' => ['required','string'],
            'recipients' => ['required','array'],
            'recipients.*' => ['required','string'],
            'priority' => [
                'string',
                'in:high,default'
            ]
        ];
    }
}
