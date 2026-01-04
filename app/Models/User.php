<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'role',
        'status',
        'identity_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function post() : HasMany
    {
        return $this->hasMany(Post::class);
    }
    public function savedPost() : HasMany
    {
        return $this->hasMany(SavedPost::class);
    }
    public function rentalRequests() : HasMany
    {
        return $this->hasMany(RentalRequest::class);
    }
    public function contracts() : HasMany
    {
        return $this->hasMany(Contract::class);
    }
    public function reviews() : HasMany
    {
        return $this->hasMany(Review::class, 'user_id'); // Legacy
    }

    public function ratingsGiven() : HasMany
    {
        return $this->hasMany(Review::class, 'rater_user_id');
    }

    public function ratingsReceived() : HasMany
    {
        return $this->hasMany(Review::class, 'rated_user_id');
    }

    /**
     * Get user's reputation (average rating and total count)
     * Only counts revealed reviews
     */
    public function getReputation(): array
    {
        $revealedReviews = $this->ratingsReceived()
            ->where('status', 'revealed')
            ->get();

        $averageRating = $revealedReviews->avg('rating') ?? 0;
        $totalReviews = $revealedReviews->count();

        return [
            'average_rating' => round($averageRating, 2),
            'total_reviews' => $totalReviews,
        ];
    }
    public function identityVerifications() : hasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }
    
    public function payments() : HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function notifications() : HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
