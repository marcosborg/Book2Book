<?php

namespace Database\Factories;

use App\Enums\BookCondition;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'description' => fake()->paragraph(),
            'genre' => fake()->word(),
            'language' => fake()->languageCode(),
            'condition' => fake()->randomElement(array_column(BookCondition::cases(), 'value')),
            'cover_image_path' => null,
            'is_available' => true,
        ];
    }
}
