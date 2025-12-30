<?php

namespace App\Services;

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TradeService
{
    public function createTrade(User $requester, Book $book, ?string $message = null): TradeRequest
    {
        if (! $book->is_available) {
            throw ValidationException::withMessages([
                'book_id' => ['Book is not available.'],
            ]);
        }

        if ($book->user_id === $requester->id) {
            throw ValidationException::withMessages([
                'book_id' => ['You cannot request your own book.'],
            ]);
        }

        $hasPending = TradeRequest::query()
            ->where('book_id', $book->id)
            ->where('requester_id', $requester->id)
            ->where('status', TradeStatus::Pending)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'book_id' => ['You already have a pending request for this book.'],
            ]);
        }

        return DB::transaction(function () use ($requester, $book, $message) {
            return TradeRequest::create([
                'book_id' => $book->id,
                'requester_id' => $requester->id,
                'owner_id' => $book->user_id,
                'status' => TradeStatus::Pending,
                'message' => $message,
            ]);
        });
    }

    public function acceptTrade(TradeRequest $trade): TradeRequest
    {
        return DB::transaction(function () use ($trade) {
            if ($trade->status !== TradeStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['Trade is not pending.'],
                ]);
            }

            if (! $trade->book->is_available) {
                throw ValidationException::withMessages([
                    'book_id' => ['Book is not available.'],
                ]);
            }

            $trade->status = TradeStatus::Accepted;
            $trade->accepted_at = now();
            $trade->save();

            $trade->book()->update(['is_available' => false]);

            return $trade;
        });
    }

    public function declineTrade(TradeRequest $trade): TradeRequest
    {
        if ($trade->status !== TradeStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['Trade is not pending.'],
            ]);
        }

        $trade->status = TradeStatus::Declined;
        $trade->declined_at = now();
        $trade->save();

        return $trade;
    }

    public function cancelTrade(TradeRequest $trade): TradeRequest
    {
        if ($trade->status !== TradeStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['Trade is not pending.'],
            ]);
        }

        $trade->status = TradeStatus::Cancelled;
        $trade->cancelled_at = now();
        $trade->save();

        return $trade;
    }

    public function completeTrade(TradeRequest $trade): TradeRequest
    {
        if ($trade->status !== TradeStatus::Accepted) {
            throw ValidationException::withMessages([
                'status' => ['Trade is not accepted.'],
            ]);
        }

        $trade->status = TradeStatus::Completed;
        $trade->completed_at = now();
        $trade->save();

        return $trade;
    }
}
