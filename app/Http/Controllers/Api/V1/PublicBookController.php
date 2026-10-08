<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SearchBooksRequest;
use App\Http\Resources\BookPublicResource;
use App\Models\Book;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PublicBookController extends ApiController
{
    /**
     * Search available books.
     */
    public function search(SearchBooksRequest $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user && ($request->filled('distance_km') || $request->input('order') === 'distance')) {
            app(LocationService::class)->fillCoordinates($user);
        }
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
            $genre = $request->string('genre')->toString();
            [$mainGenre, $subGenre] = array_pad(explode(' / ', $genre, 2), 2, null);
            $knownSubgenres = $this->genreTree()[$mainGenre] ?? [];

            $query->where(function ($books) use ($genre, $subGenre, $knownSubgenres) {
                $books->where('books.genre', $genre)
                    ->orWhere('books.genre', 'like', $genre.' / %');

                if ($subGenre && in_array($subGenre, $knownSubgenres, true)) {
                    $books->orWhere('books.genre', $subGenre);
                } elseif (! $subGenre && $knownSubgenres) {
                    $books->orWhereIn('books.genre', $knownSubgenres);
                }
            });
        }

        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        $hasCoords = $lat !== null && $lng !== null;

        if (($distance !== null || $order === 'distance') && ! $hasCoords) {
            throw ValidationException::withMessages([
                'distance_km' => ['Indica a tua cidade ou coordenadas no perfil antes de pesquisar por distância.'],
            ]);
        }

        if ($hasCoords) {
            $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(users.lat)) * cos(radians(users.lng) - radians(?)) + sin(radians(?)) * sin(radians(users.lat))))';
            $bindings = [$lat, $lng, $lat];

            $query->join('users', 'users.id', '=', 'books.user_id')
                ->whereNotNull('users.lat')
                ->whereNotNull('users.lng')
                ->addSelect('books.*')
                ->selectRaw($distanceSql.' as distance_km', $bindings);

            if ($distance !== null) {
                $query->whereRaw($distanceSql.' <= CAST(? AS DECIMAL(10, 2))', [...$bindings, (float) $distance]);
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

    public function genres(): JsonResponse
    {
        return response()->json(['data' => $this->genreTree(), 'meta' => (object) []]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function genreTree(): array
    {
        $genres = config('genres');
        $knownSubgenres = collect($genres)->flatten()->all();

        foreach (Book::query()->whereNotNull('genre')->distinct()->pluck('genre') as $storedGenre) {
            if (str_contains($storedGenre, ' / ')) {
                [$main, $sub] = explode(' / ', $storedGenre, 2);
                $genres[$main] ??= [];
                if ($sub && ! in_array($sub, $genres[$main], true)) {
                    $genres[$main][] = $sub;
                }
            } elseif (! in_array($storedGenre, $knownSubgenres, true) && ! array_key_exists($storedGenre, $genres)) {
                $genres['Outros'] ??= [];
                if (! in_array($storedGenre, $genres['Outros'], true)) {
                    $genres['Outros'][] = $storedGenre;
                }
            }
        }

        return $genres;
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
