<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates, updates, and deletes a book', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $createResponse = $this->post('/api/v1/me/books', [
        'title' => 'Book A',
        'author' => 'Author A',
        'condition' => 'good',
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $createResponse->assertCreated();
    $bookId = $createResponse->json('data.id');

    $updateResponse = $this->put('/api/v1/me/books/' . $bookId, [
        'title' => 'Book A+',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.title', 'Book A+');

    $deleteResponse = $this->delete('/api/v1/me/books/' . $bookId);
    $deleteResponse->assertOk();

    expect(Book::withTrashed()->find($bookId))->not->toBeNull();
});

it('lists only authenticated user books', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Book::factory()->count(2)->for($user, 'owner')->create();
    Book::factory()->count(1)->for($other, 'owner')->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me/books');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});
