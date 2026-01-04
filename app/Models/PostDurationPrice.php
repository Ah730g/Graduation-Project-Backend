<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostDurationPrice extends Model
{
    protected $fillable = [
        'post_id',
        'duration_type',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

