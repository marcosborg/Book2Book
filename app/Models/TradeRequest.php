<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'offered_book_id',
        'requester_id',
        'owner_id',
        'status',
        'message',
        'accepted_at',
        'declined_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => TradeStatus::class,
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    public function offeredBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'offered_book_id')->withTrashed();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages()
    {
        return $this->hasMany(TradeMessage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId)
            ->orWhere('owner_id', $userId);
    }
}
