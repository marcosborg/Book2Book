<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('searches available books from other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Book::factory()->for($user, 'owner')->create([
        'title' => 'Alpha',
        'is_available' => true,
    ]);

    Book::factory()->for($other, 'owner')->create([
        'title' => 'Alpha',
        'is_available' => true,
    ]);

    Book::factory()->for($other, 'owner')->create([
        'title' => 'Alpha',
        'is_available' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/books/search?q=Alpha');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
