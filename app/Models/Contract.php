<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'rental_request_id',
        'payment_id',
        'user_id',
        'post_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'status',
        'terms',
        'owner_signature',
        'renter_signature',
        'owner_signed_at',
        'renter_signed_at',
        'payment_confirmed_by_owner',
        'payment_confirmed_at',
        'cancelled_by_admin',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'owner_signed_at' => 'datetime',
        'renter_signed_at' => 'datetime',
        'payment_confirmed_by_owner' => 'boolean',
        'payment_confirmed_at' => 'datetime',
        'cancelled_by_admin' => 'boolean',
    ];

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if stay is completed (contract expired)
     */
    public function isStayCompleted(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Get the owner user for this contract
     */
    public function getOwnerUser()
    {
        return $this->post ? $this->post->user : null;
    }

    /**
     * Get the renter user for this contract
     */
    public function getRenterUser()
    {
        if ($this->rentalRequest && $this->rentalRequest->user) {
            return $this->rentalRequest->user;
        }
        return $this->user;
    }
}
