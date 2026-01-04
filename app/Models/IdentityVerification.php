<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerification extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'document_front_url',
        'document_back_url',
        'full_name',
        'document_number',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'issue_date',
        'expiry_date',
        'address',
        'status',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'date_of_birth' => 'date',
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
