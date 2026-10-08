<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BookPublicResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserPublicResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PublicUserController extends ApiController
{
    public function show(User $user): JsonResponse
    {
        $user->loadAvg('reviewsReceived', 'rating');

        return response()->json([
            'data' => [
                'user' => new UserPublicResource($user),
                'books' => BookPublicResource::collection($user->books()->available()->latest()->limit(12)->get()),
                'reviews' => ReviewResource::collection($user->reviewsReceived()->with('reviewer')->latest()->limit(10)->get()),
            ],
            'meta' => (object) [],
        ]);
    }
}
