<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\BookCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => ['sometimes', 'required', 'string', 'max:32'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'genre' => ['sometimes', 'required', 'string', 'max:120'],
            'language' => ['sometimes', 'required', 'string', 'max:60'],
            'condition' => ['sometimes', 'required', Rule::in(array_column(BookCondition::cases(), 'value'))],
            'cover_image' => ['sometimes', 'nullable', 'image', 'max:4096'],
            'is_available' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
