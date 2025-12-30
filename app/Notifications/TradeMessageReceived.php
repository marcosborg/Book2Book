<?php

namespace App\Notifications;

use App\Models\TradeMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TradeMessageReceived extends Notification
{
    use Queueable;

    public function __construct(protected TradeMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'trade_id' => $this->message->trade_request_id,
            'from_user_id' => $this->message->sender_id,
            'snippet' => Str::limit($this->message->message, 120),
        ];
    }
}
