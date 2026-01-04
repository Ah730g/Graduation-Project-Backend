<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Contract;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReviewController extends Controller
{
    /**
     * Create a rating after stay completion
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $contract = Contract::with(['post', 'rentalRequest', 'reviews'])->findOrFail($request->contract_id);

        // Eligibility check 1: Stay must be completed (contract expired)
        if (!$contract->isStayCompleted()) {
            return response()->json([
                'message' => 'You can only rate after the stay is completed. Please wait until the rental period ends.'
            ], 400);
        }

        // Eligibility check 2: User must be either owner or renter
        $owner = $contract->getOwnerUser();
        $renter = $contract->getRenterUser();

        if (!$owner || !$renter) {
            return response()->json(['message' => 'Invalid contract. Unable to determine parties.'], 400);
        }

        $isOwner = $owner->id === $user->id;
        $isRenter = $renter->id === $user->id;

        if (!$isOwner && !$isRenter) {
            return response()->json([
                'message' => 'You can only rate if you were part of this rental agreement.'
            ], 403);
        }

        // Determine who is being rated
        $ratedUserId = $isOwner ? $renter->id : $owner->id;

        // Eligibility check 3: User can only submit one rating per contract
        $existingReview = Review::where('contract_id', $contract->id)
            ->where('rater_user_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already submitted a rating for this stay.'
            ], 400);
        }

        // Eligibility check 4: Cannot rate yourself
        if ($user->id === $ratedUserId) {
            return response()->json([
                'message' => 'You cannot rate yourself.'
            ], 400);
        }

        // Create the rating (hidden by default)
        $review = Review::create([
            'contract_id' => $contract->id,
            'rater_user_id' => $user->id,
            'rated_user_id' => $ratedUserId,
            'post_id' => $contract->post_id, // For backward compatibility
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'hidden',
        ]);

        // Check if both parties have submitted ratings
        $this->checkAndRevealRatings($contract);

        return response()->json([
            'message' => 'Rating submitted successfully. It will be revealed once both parties submit their ratings or after 14 days.',
            'review' => $review->load(['rater', 'rated']),
        ], 201);
    }

    /**
     * Get reviews for a user (only revealed ones are visible)
     */
    public function index(Request $request, $userId = null)
    {
        $user = $request->user();
        $targetUserId = $userId ?? $user->id;

        if (!$targetUserId) {
            return response()->json(['message' => 'User ID required'], 400);
        }

        // Get revealed reviews where this user was rated
        $reviews = Review::where('rated_user_id', $targetUserId)
            ->where('status', 'revealed')
            ->with(['rater:id,name,avatar', 'contract.post:id,Title'])
            ->orderBy('revealed_at', 'desc')
            ->get();

        return response()->json($reviews);
    }

    /**
     * Get reviews for a specific contract
     * Users can see their own hidden reviews and all revealed reviews
     */
    public function getContractReviews(Request $request, $contractId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with(['reviews.rater', 'reviews.rated'])->findOrFail($contractId);

        // Check if user is part of this contract
        $owner = $contract->getOwnerUser();
        $renter = $contract->getRenterUser();
        $isOwner = $owner && $owner->id === $user->id;
        $isRenter = $renter && $renter->id === $user->id;

        if (!$isOwner && !$isRenter) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reviews = $contract->reviews->map(function ($review) use ($user) {
            // Users can see their own reviews (even if hidden) and all revealed reviews
            $canSee = $review->status === 'revealed' || $review->rater_user_id === $user->id;
            
            if ($canSee) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'status' => $review->status,
                    'revealed_at' => $review->revealed_at,
                    'rater' => $review->rater ? [
                        'id' => $review->rater->id,
                        'name' => $review->rater->name,
                        'avatar' => $review->rater->avatar,
                    ] : null,
                    'rated' => $review->rated ? [
                        'id' => $review->rated->id,
                        'name' => $review->rated->name,
                    ] : null,
                ];
            }
            
            return null;
        })->filter()->values();

        return response()->json($reviews);
    }

    /**
     * Get user reputation
     */
    public function getReputation($userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $reputation = $user->getReputation();

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'average_rating' => $reputation['average_rating'],
            'total_reviews' => $reputation['total_reviews'],
        ]);
    }

    /**
     * Update a review (only allowed if not revealed)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $review = Review::findOrFail($id);

        // Check ownership
        if ($review->rater_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Immutability check: cannot edit revealed reviews
        if ($review->isImmutable()) {
            return response()->json([
                'message' => 'This rating has been revealed and cannot be edited.'
            ], 400);
        }

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($request->only(['rating', 'comment']));

        // Recheck reveal status after update
        $this->checkAndRevealRatings($review->contract);

        return response()->json([
            'message' => 'Rating updated successfully',
            'review' => $review->load(['rater', 'rated']),
        ]);
    }

    /**
     * Delete a review (only allowed if not revealed)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $review = Review::findOrFail($id);

        // Check ownership
        if ($review->rater_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Immutability check: cannot delete revealed reviews
        if ($review->isImmutable()) {
            return response()->json([
                'message' => 'This rating has been revealed and cannot be deleted.'
            ], 400);
        }

        $contract = $review->contract;
        $review->delete();

        // Recheck reveal status after deletion
        $this->checkAndRevealRatings($contract);

        return response()->json(['message' => 'Rating deleted successfully']);
    }

    /**
     * Check if both ratings are submitted and reveal them
     * Or reveal single rating after 14 days
     */
    private function checkAndRevealRatings(Contract $contract)
    {
        $reviews = Review::where('contract_id', $contract->id)
            ->where('status', 'hidden')
            ->get();

        if ($reviews->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $stayEndDate = Carbon::parse($contract->end_date);
        $daysSinceStayEnd = $now->diffInDays($stayEndDate);
        $hasBeen14Days = $daysSinceStayEnd >= 14;

        // Case A: Both users have submitted ratings - reveal both immediately
        // There should be exactly 2 reviews (one from owner, one from renter)
        if ($reviews->count() === 2) {
            Review::where('contract_id', $contract->id)
                ->where('status', 'hidden')
                ->update([
                    'status' => 'revealed',
                    'revealed_at' => $now,
                ]);
            return;
        }

        // Case B: 14 days have passed - reveal the submitted rating(s)
        if ($hasBeen14Days) {
            Review::where('contract_id', $contract->id)
                ->where('status', 'hidden')
                ->update([
                    'status' => 'revealed',
                    'revealed_at' => $now,
                ]);
        }
    }

    /**
     * Get contracts eligible for rating (completed stays where user hasn't rated yet)
     */
    public function getEligibleContracts(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get all expired contracts where user is owner or renter
        $contracts = Contract::where('status', 'expired')
            ->where(function($q) use ($user) {
                // User is owner
                $q->whereHas('post', function($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                })
                // Or user is renter
                ->orWhere(function($subQ) use ($user) {
                    $subQ->where('user_id', $user->id)
                         ->orWhereHas('rentalRequest', function($reqQ) use ($user) {
                             $reqQ->where('user_id', $user->id);
                         });
                });
            })
            ->with(['post:id,Title,Address', 'reviews' => function($q) use ($user) {
                $q->where('rater_user_id', $user->id);
            }])
            ->get()
            ->map(function($contract) use ($user) {
                // Check if user already rated
                $userReview = $contract->reviews->first();
                $hasRated = $userReview ? true : false;
                
                // Get the other party
                $owner = $contract->getOwnerUser();
                $renter = $contract->getRenterUser();
                $isOwner = $owner && $owner->id === $user->id;
                $otherParty = $isOwner ? $renter : $owner;
                
                return [
                    'contract_id' => $contract->id,
                    'post' => $contract->post ? [
                        'id' => $contract->post->id,
                        'title' => $contract->post->Title,
                        'address' => $contract->post->Address,
                    ] : null,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'other_party' => $otherParty ? [
                        'id' => $otherParty->id,
                        'name' => $otherParty->name,
                        'avatar' => $otherParty->avatar,
                    ] : null,
                    'has_rated' => $hasRated,
                    'user_review' => $hasRated ? [
                        'id' => $userReview->id,
                        'rating' => $userReview->rating,
                        'comment' => $userReview->comment,
                        'status' => $userReview->status,
                    ] : null,
                ];
            });

        return response()->json($contracts);
    }
}

