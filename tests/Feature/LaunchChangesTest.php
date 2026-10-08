<?php

use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires complete book details and a cover', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/me/books', [
        'title' => 'Um livro',
        'author' => 'Uma autora',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['isbn', 'description', 'genre', 'language', 'condition', 'cover_image']);
});

it('lists genres in a hierarchy', function () {
    $this->getJson('/api/v1/books/genres')
        ->assertOk()
        ->assertJsonStructure(['data' => ['Ficção']]);

    Book::factory()->create(['genre' => 'Ficção / Romance']);
    Book::factory()->create(['genre' => 'Romance']);
    Book::factory()->create(['genre' => 'Não ficção / História']);
    Book::factory()->create(['genre' => 'Biologia']);

    $this->getJson('/api/v1/books/search?genre=Fic%C3%A7%C3%A3o')
        ->assertOk()
        ->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/books/search?genre=Fic%C3%A7%C3%A3o%20%2F%20Romance')
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/books/genres')
        ->assertJsonFragment(['Outros' => ['Biologia']]);
    $this->getJson('/api/v1/books/search?genre=Outros%20%2F%20Biologia')
        ->assertJsonCount(1, 'data');
});

it('filters by distance and rejects a distance search without location', function () {
    $reader = User::factory()->create(['lat' => null, 'lng' => null, 'city' => null]);
    Sanctum::actingAs($reader);

    $this->getJson('/api/v1/books/search?distance_km=20')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('distance_km');

    $reader->update(['lat' => 41.22, 'lng' => -8.55]);
    $nearOwner = User::factory()->create(['lat' => 41.23, 'lng' => -8.55]);
    $farOwner = User::factory()->create(['lat' => 38.18, 'lng' => -8.56]);
    Book::factory()->for($nearOwner, 'owner')->create(['title' => 'Perto']);
    Book::factory()->for($farOwner, 'owner')->create(['title' => 'Longe']);

    $this->getJson('/api/v1/books/search?distance_km=20')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Perto');
});

it('resolves the reader city before a distance search', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([['lat' => '41.22', 'lon' => '-8.55']]),
    ]);

    $reader = User::factory()->create(['city' => 'Ermesinde', 'lat' => null, 'lng' => null]);
    $farOwner = User::factory()->create(['city' => 'Grândola', 'lat' => 38.18, 'lng' => -8.56]);
    Book::factory()->for($farOwner, 'owner')->create();
    Sanctum::actingAs($reader);

    $this->getJson('/api/v1/books/search?distance_km=20')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect($reader->refresh()->lat)->toBe(41.22)
        ->and($reader->lng)->toBe(-8.55);
});

it('shows the offered book and public requester profile to the owner', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $requested = Book::factory()->for($owner, 'owner')->create();
    $offered = Book::factory()->for($reader, 'owner')->create();
    Sanctum::actingAs($reader);

    $tradeId = $this->postJson('/api/v1/trades', [
        'book_id' => $requested->id,
        'offered_book_id' => $offered->id,
        'message' => 'Troco por este livro.',
    ])->assertCreated()->json('data.id');

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/trades/'.$tradeId)
        ->assertOk()
        ->assertJsonPath('data.offered_book.id', $offered->id)
        ->assertJsonPath('data.message', 'Troco por este livro.');

    $this->postJson('/api/v1/trades/'.$tradeId.'/accept')
        ->assertOk();
    expect($requested->refresh()->is_available)->toBeFalse()
        ->and($offered->refresh()->is_available)->toBeFalse();

    $this->getJson('/api/v1/users/'.$reader->id)
        ->assertOk()
        ->assertJsonPath('data.user.id', $reader->id);
});

it('rejects a book offered by another user', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $requested = Book::factory()->for($owner, 'owner')->create();
    $notMine = Book::factory()->for($owner, 'owner')->create();
    Sanctum::actingAs($reader);

    $this->postJson('/api/v1/trades', [
        'book_id' => $requested->id,
        'offered_book_id' => $notMine->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('offered_book_id');
});

it('shows unread messages until the recipient opens the conversation', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $reader->id,
    ]);

    Sanctum::actingAs($reader);
    $this->postJson('/api/v1/trades/'.$trade->id.'/messages', ['message' => 'Olá'])->assertCreated();

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/trades')->assertJsonPath('data.0.unread_messages_count', 1);
    $this->getJson('/api/v1/trades/'.$trade->id.'/messages')->assertOk();
    $this->getJson('/api/v1/trades')->assertJsonPath('data.0.unread_messages_count', 0);
});

it('keeps trade attachments private to participants', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $intruder = User::factory()->create();
    $book = Book::factory()->for($owner, 'owner')->create();
    $trade = TradeRequest::factory()->create([
        'book_id' => $book->id,
        'owner_id' => $owner->id,
        'requester_id' => $reader->id,
    ]);

    Sanctum::actingAs($reader);
    $messageId = $this->post('/api/v1/trades/'.$trade->id.'/messages', [
        'attachment' => UploadedFile::fake()->create('comprovativo.pdf', 20, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $this->post('/api/v1/trades/'.$trade->id.'/messages', [
        'attachment' => UploadedFile::fake()->create('livro.epub', 20, 'application/epub+zip'),
    ])->assertCreated();

    Sanctum::actingAs($intruder);
    $this->get('/api/v1/trades/'.$trade->id.'/messages/'.$messageId.'/attachment')->assertForbidden();

    Sanctum::actingAs($owner);
    $this->get('/api/v1/trades/'.$trade->id.'/messages/'.$messageId.'/attachment')->assertOk();
});
