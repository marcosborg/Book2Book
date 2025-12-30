<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreTradeRequest;
use App\Http\Resources\TradeRequestResource;
use App\Models\Book;
use App\Models\TradeRequest as TradeRequestModel;
use App\Services\NotificationService;
use App\Services\TradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TradeController extends ApiController
{
    public function __construct(
        protected TradeService $tradeService,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * List trade requests for authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $request->query('role', 'all');
        $status = $request->query('status');

        $query = TradeRequestModel::query()->with(['book', 'requester', 'owner']);

        if ($role === 'requester') {
            $query->where('requester_id', $user->id);
        } elseif ($role === 'owner') {
            $query->where('owner_id', $user->id);
        } else {
            $query->where(function ($sub) use ($user) {
                $sub->where('requester_id', $user->id)
                    ->orWhere('owner_id', $user->id);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $trades = $query->latest()->paginate($this->perPage($request));

        return TradeRequestResource::collection($trades)
            ->additional(['meta' => $this->paginationMeta($trades)]);
    }

    /**
     * Create a trade request.
     */
    public function store(StoreTradeRequest $request): TradeRequestResource
    {
        $user = $request->user();
        $book = Book::findOrFail($request->input('book_id'));

        $trade = $this->tradeService->createTrade($user, $book, $request->input('message'));
        $trade->load(['book', 'requester', 'owner']);

        $this->notificationService->tradeRequested($trade, $user);

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Show a trade request.
     */
    public function show(TradeRequestModel $trade): TradeRequestResource
    {
        $this->authorize('view', $trade);

        $trade->load(['book', 'requester', 'owner']);

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Accept a trade request.
     */
    public function accept(TradeRequestModel $trade): TradeRequestResource|JsonResponse
    {
        $this->authorize('accept', $trade);

        try {
            $trade = $this->tradeService->acceptTrade($trade->load('book'));
        } catch (\Throwable $exception) {
            Log::warning('Trade accept failed', ['trade_id' => $trade->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }

        $trade->load(['book', 'requester', 'owner']);
        $this->notificationService->tradeStatusChanged($trade, request()->user(), $trade->status->value);

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Decline a trade request.
     */
    public function decline(TradeRequestModel $trade): TradeRequestResource
    {
        $this->authorize('decline', $trade);

        $trade = $this->tradeService->declineTrade($trade);
        $trade->load(['book', 'requester', 'owner']);

        $this->notificationService->tradeStatusChanged($trade, request()->user(), $trade->status->value);

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Cancel a trade request.
     */
    public function cancel(TradeRequestModel $trade): TradeRequestResource
    {
        $this->authorize('cancel', $trade);

        $trade = $this->tradeService->cancelTrade($trade);
        $trade->load(['book', 'requester', 'owner']);

        $this->notificationService->tradeCancelled($trade, request()->user());

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Complete a trade request.
     */
    public function complete(TradeRequestModel $trade): TradeRequestResource
    {
        $this->authorize('complete', $trade);

        $trade = $this->tradeService->completeTrade($trade);
        $trade->load(['book', 'requester', 'owner']);

        $this->notificationService->tradeStatusChanged($trade, request()->user(), $trade->status->value);

        return (new TradeRequestResource($trade))
            ->additional(['meta' => (object) []]);
    }
}
