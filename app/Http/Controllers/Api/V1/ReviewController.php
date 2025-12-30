<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TradeStatus;
use App\Http\Requests\Api\V1\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends ApiController
{
    /**
     * Create a review for a completed trade.
     */
    public function store(StoreReviewRequest $request, TradeRequest $trade): ReviewResource
    {
        $user = $request->user();

        if ($trade->status !== TradeStatus::Completed) {
            throw ValidationException::withMessages([
                'trade' => ['Trade must be completed before reviewing.'],
            ]);
        }

        if ($trade->requester_id !== $user->id && $trade->owner_id !== $user->id) {
            abort(403);
        }

        $reviewedUserId = $trade->requester_id === $user->id
            ? $trade->owner_id
            : $trade->requester_id;

        $existing = Review::where('trade_request_id', $trade->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'trade' => ['You already reviewed this trade.'],
            ]);
        }

        $review = Review::create([
            'trade_request_id' => $trade->id,
            'reviewer_id' => $user->id,
            'reviewed_user_id' => $reviewedUserId,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        $review->load('reviewer');

        return (new ReviewResource($review))
            ->additional(['meta' => (object) []]);
    }

    /**
     * List reviews for a user.
     */
    public function index(Request $request, User $user)
    {
        $reviews = $user->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->paginate($this->perPage($request));

        return ReviewResource::collection($reviews)
            ->additional(['meta' => $this->paginationMeta($reviews)]);
    }
}
