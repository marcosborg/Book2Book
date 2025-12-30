<?php

namespace App\Policies;

use App\Enums\TradeStatus;
use App\Models\TradeRequest;
use App\Models\User;

class TradeRequestPolicy
{
    public function view(User $user, TradeRequest $trade): bool
    {
        return $trade->requester_id === $user->id || $trade->owner_id === $user->id;
    }

    public function accept(User $user, TradeRequest $trade): bool
    {
        return $trade->owner_id === $user->id && $trade->status === TradeStatus::Pending;
    }

    public function decline(User $user, TradeRequest $trade): bool
    {
        return $trade->owner_id === $user->id && $trade->status === TradeStatus::Pending;
    }

    public function cancel(User $user, TradeRequest $trade): bool
    {
        return $trade->requester_id === $user->id && $trade->status === TradeStatus::Pending;
    }

    public function complete(User $user, TradeRequest $trade): bool
    {
        return $trade->owner_id === $user->id && $trade->status === TradeStatus::Accepted;
    }

    public function message(User $user, TradeRequest $trade): bool
    {
        return $trade->requester_id === $user->id || $trade->owner_id === $user->id;
    }
}
