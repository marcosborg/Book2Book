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
            'isbn' => ['required', 'string', 'max:32'],
            'description' => ['required', 'string', 'max:2000'],
            'genre' => ['required', 'string', 'max:120'],
            'language' => ['required', 'string', 'max:60'],
            'condition' => ['required', Rule::in(array_column(BookCondition::cases(), 'value'))],
            'cover_image' => ['required', 'image', 'max:4096'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cover_image.required' => 'Add at least one photo of the book.',
            'genre.required' => 'Choose a book genre.',
        ];
    }
}
