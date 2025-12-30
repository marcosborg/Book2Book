<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class MvpSeeder extends Seeder
{
    /**
     * Seed sample users and books for MVP testing.
     */
    public function run(): void
    {
        $users = User::factory()->count(10)->create();

        Book::factory()
            ->count(50)
            ->make()
            ->each(function ($book) use ($users) {
                $book->user_id = $users->random()->id;
                $book->save();
            });
    }
}
