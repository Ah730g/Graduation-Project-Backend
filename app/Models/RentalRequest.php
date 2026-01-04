<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'status',
        'message',
        'requested_at',
        'hidden_by_user_id',
        'duration_type',
        'duration_multiplier',
        'requested_start_date',
        'requested_end_date',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'requested_start_date' => 'date',
        'requested_end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function contract(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Contract::class);
    }
}
