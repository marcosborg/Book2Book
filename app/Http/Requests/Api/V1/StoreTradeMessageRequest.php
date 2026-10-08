<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreTradeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required_without:attachment', 'nullable', 'string', 'max:2000'],
            'attachment' => ['required_without:message', 'nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf', 'epub'])->max('10mb')],
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.required_without' => 'Write a message or attach a file.',
            'attachment.max' => 'The attachment may not exceed 10 MB.',
        ];
    }
}
