<?php

namespace App\Enums;

enum TradeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
