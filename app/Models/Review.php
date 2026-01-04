<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'contract_id',
        'rater_user_id',
        'rated_user_id',
        'post_id',
        'user_id', // Kept for backward compatibility
        'rating',
        'comment',
        'status',
        'revealed_at',
    ];

    protected $casts = [
        'revealed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function rated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    // Legacy relationships for backward compatibility
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Check if review can be edited or deleted
     */
    public function isImmutable(): bool
    {
        return $this->status === 'revealed';
    }
}
