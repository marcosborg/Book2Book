<?php

namespace App\Services;

use App\Models\TradeMessage;
use App\Models\TradeRequest;
use App\Models\User;
use App\Notifications\TradeMessageReceived;
use App\Notifications\TradeRequested;
use App\Notifications\TradeStatusChanged;

class NotificationService
{
    public function tradeRequested(TradeRequest $trade, User $from): void
    {
        $trade->owner->notify(new TradeRequested($trade, $from));
    }

    public function tradeStatusChanged(TradeRequest $trade, User $from, string $status): void
    {
        $target = $trade->requester;
        $target->notify(new TradeStatusChanged($trade, $from, $status));
    }

    public function tradeCancelled(TradeRequest $trade, User $from): void
    {
        $target = $trade->owner;
        $target->notify(new TradeStatusChanged($trade, $from, $trade->status->value));
    }

    public function tradeMessageReceived(TradeMessage $message, User $recipient): void
    {
        $recipient->notify(new TradeMessageReceived($message));
    }
}
