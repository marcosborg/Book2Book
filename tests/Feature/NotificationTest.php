<?php

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use App\Notifications\TradeMessageReceived;
use App\Notifications\TradeRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates a notification when a trade is requested', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();

    Sanctum::actingAs($requester);

    $this->postJson('/api/v1/trades', [
        'book_id' => $book->id,
    ])->assertCreated();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'notifiable_type' => User::class,
        'type' => TradeRequested::class,
    ]);
});

it('creates a notification when a trade message is sent', function () {
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

    $this->postJson('/api/v1/trades/' . $trade->id . '/messages', [
        'message' => 'Ping',
    ])->assertCreated();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'notifiable_type' => User::class,
        'type' => TradeMessageReceived::class,
    ]);
});
