<?php

namespace App\Models;

use App\Enums\BookCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'description',
        'genre',
        'language',
        'condition',
        'cover_image_path',
        'is_available',
    ];

    protected $casts = [
        'condition' => BookCondition::class,
        'is_available' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trades()
    {
        return $this->hasMany(TradeRequest::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }
}
