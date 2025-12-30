<?php

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('sends and lists trade messages for participants', function () {
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

    $sendResponse = $this->postJson('/api/v1/trades/' . $trade->id . '/messages', [
        'message' => 'Hello!',
    ]);

    $sendResponse->assertCreated()
        ->assertJsonPath('data.message', 'Hello!');

    Sanctum::actingAs($owner);

    $listResponse = $this->getJson('/api/v1/trades/' . $trade->id . '/messages');

    $listResponse->assertOk()
        ->assertJsonCount(1, 'data');
});

it('prevents non-participants from sending messages', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $requester = User::factory()->create();
    $intruder = User::factory()->create();

    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'status' => TradeStatus::Pending,
    ]);

    Sanctum::actingAs($intruder);

    $response = $this->postJson('/api/v1/trades/' . $trade->id . '/messages', [
        'message' => 'Hello!',
    ]);

    $response->assertForbidden();
});
