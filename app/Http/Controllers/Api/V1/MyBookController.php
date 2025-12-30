<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SetBookAvailabilityRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MyBookController extends ApiController
{
    /**
     * List authenticated user's books.
     */
    public function index(Request $request)
    {
        $books = $request->user()
            ->books()
            ->latest()
            ->paginate($this->perPage($request));

        return BookResource::collection($books)
            ->additional(['meta' => $this->paginationMeta($books)]);
    }

    /**
     * Create a book in authenticated user's library.
     */
    public function store(StoreBookRequest $request): BookResource
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }

        $book = $request->user()->books()->create($data);

        return (new BookResource($book))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Show a book from authenticated user's library.
     */
    public function show(Book $book): BookResource
    {
        $this->authorize('view', $book);

        return (new BookResource($book))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Update a book from authenticated user's library.
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image_path) {
                Storage::disk('public')->delete($book->cover_image_path);
            }

            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }

        $book->fill($data);
        $book->save();

        return (new BookResource($book->refresh()))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Delete a book from authenticated user's library.
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(['data' => ['message' => 'Book deleted.'], 'meta' => (object) []]);
    }

    /**
     * Update book availability.
     */
    public function availability(SetBookAvailabilityRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $book->is_available = $request->input('is_available');
        $book->save();

        return (new BookResource($book->refresh()))
            ->additional(['meta' => (object) []]);
    }
}
