<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SearchBooksRequest;
use App\Http\Resources\BookPublicResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicBookController extends ApiController
{
    /**
     * Search available books.
     */
    public function search(SearchBooksRequest $request)
    {
        $user = Auth::guard('sanctum')->user();
        $lat = $request->input('lat', $user?->lat);
        $lng = $request->input('lng', $user?->lng);
        $distance = $request->input('distance_km');
        $order = $request->input('order', 'recent');

        $query = Book::query()
            ->available()
            ->with(['owner' => function ($ownerQuery) {
                $ownerQuery->withAvg('reviewsReceived', 'rating');
            }]);

        if ($user) {
            $query->where('user_id', '!=', $user->id);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($request->filled('genre')) {
            $query->where('genre', $request->input('genre'));
        }

        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        $hasCoords = $lat !== null && $lng !== null;

        if ($hasCoords) {
            $query->join('users', 'users.id', '=', 'books.user_id')
                ->whereNotNull('users.lat')
                ->whereNotNull('users.lng')
                ->addSelect('books.*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(users.lat)) * cos(radians(users.lng) - radians(?)) + sin(radians(?)) * sin(radians(users.lat)))) as distance_km',
                    [$lat, $lng, $lat]
                );

            if ($distance !== null) {
                $query->having('distance_km', '<=', (float) $distance);
            }

            if ($order === 'distance') {
                $query->orderBy('distance_km');
            }
        }

        if ($order !== 'distance' || ! $hasCoords) {
            $query->orderByDesc('books.created_at');
        }

        $books = $query->paginate($this->perPage($request));

        return BookPublicResource::collection($books)
            ->additional(['meta' => $this->paginationMeta($books)]);
    }

    /**
     * Show public book details.
     */
    public function show(Request $request, Book $book)
    {
        $user = $request->user();

        if (! $book->is_available && (! $user || $book->user_id !== $user->id)) {
            abort(404);
        }

        $book->load(['owner' => function ($ownerQuery) {
            $ownerQuery->withAvg('reviewsReceived', 'rating');
        }]);

        return (new BookPublicResource($book))
            ->additional(['meta' => (object) []]);
    }
}
