<?php

namespace Database\Factories;

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradeRequest>
 */
class TradeRequestFactory extends Factory
{
    protected $model = TradeRequest::class;

    public function definition(): array
    {
        $owner = User::factory();
        $book = Book::factory()->for($owner, 'owner');

        return [
            'book_id' => $book,
            'requester_id' => User::factory(),
            'owner_id' => $owner,
            'status' => TradeStatus::Pending,
            'message' => fake()->sentence(),
        ];
    }
}
