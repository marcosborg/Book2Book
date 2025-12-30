<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreTradeMessageRequest;
use App\Http\Resources\TradeMessageResource;
use App\Models\TradeMessage;
use App\Models\TradeRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TradeMessageController extends ApiController
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * List messages for a trade request.
     */
    public function index(Request $request, TradeRequest $trade)
    {
        $this->authorize('message', $trade);

        $messages = $trade->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->paginate($this->perPage($request));

        return TradeMessageResource::collection($messages)
            ->additional(['meta' => $this->paginationMeta($messages)]);
    }

    /**
     * Store a new message in a trade request.
     */
    public function store(StoreTradeMessageRequest $request, TradeRequest $trade): TradeMessageResource
    {
        $this->authorize('message', $trade);

        $message = TradeMessage::create([
            'trade_request_id' => $trade->id,
            'sender_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        $recipient = $trade->requester_id === $request->user()->id
            ? $trade->owner
            : $trade->requester;

        $this->notificationService->tradeMessageReceived($message->load('tradeRequest'), $recipient);

        $message->load('sender');

        return (new TradeMessageResource($message))
            ->additional(['meta' => (object) []]);
    }
}
