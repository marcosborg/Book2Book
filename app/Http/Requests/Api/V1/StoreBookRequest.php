<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\BookCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
            'genre' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:60'],
            'condition' => ['nullable', Rule::in(array_column(BookCondition::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }
}
