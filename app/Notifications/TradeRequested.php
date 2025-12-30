<?php

namespace App\Notifications;

use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TradeRequested extends Notification
{
    use Queueable;

    public function __construct(
        protected TradeRequest $trade,
        protected User $from
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'trade_id' => $this->trade->id,
            'book_id' => $this->trade->book_id,
            'from_user_id' => $this->from->id,
            'snippet' => $this->trade->message ? Str::limit($this->trade->message, 120) : null,
        ];
    }
}
