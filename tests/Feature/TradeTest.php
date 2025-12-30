<?php

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates a trade request', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();

    Sanctum::actingAs($requester);

    $response = $this->postJson('/api/v1/trades', [
        'book_id' => $book->id,
        'message' => 'Interested in trade.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', TradeStatus::Pending->value);
});

it('accepts a trade request and marks the book unavailable', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create(['is_available' => true]);
    $requester = User::factory()->create();

    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'status' => TradeStatus::Pending,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/trades/' . $trade->id . '/accept');

    $response->assertOk()
        ->assertJsonPath('data.status', TradeStatus::Accepted->value);

    expect($book->refresh()->is_available)->toBeFalse();
});

it('declines a trade request', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();

    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'status' => TradeStatus::Pending,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/trades/' . $trade->id . '/decline');

    $response->assertOk()
        ->assertJsonPath('data.status', TradeStatus::Declined->value);
});

it('cancels a trade request', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();

    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'status' => TradeStatus::Pending,
    ]);

    Sanctum::actingAs($requester);

    $response = $this->postJson('/api/v1/trades/' . $trade->id . '/cancel');

    $response->assertOk()
        ->assertJsonPath('data.status', TradeStatus::Cancelled->value);
});

it('completes a trade request', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();

    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'status' => TradeStatus::Accepted,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/trades/' . $trade->id . '/complete');

    $response->assertOk()
        ->assertJsonPath('data.status', TradeStatus::Completed->value);
});
