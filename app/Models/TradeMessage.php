<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_request_id',
        'sender_id',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function tradeRequest()
    {
        return $this->belongsTo(TradeRequest::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
