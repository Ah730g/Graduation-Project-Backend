<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\Post;
use App\Models\Notification;
use App\Services\RentalDurationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Create a booking request
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'message' => 'nullable|string|max:1000',
            'duration_type' => 'required|in:day,week,month,test_10s,test_30s',
            'duration_multiplier' => 'required|integer|min:1|max:120',
        ]);

        $post = Post::findOrFail($request->post_id);

        // First, check and expire any contracts that have passed their end date (real-time expiration check)
        $this->expireExpiredContracts($post->id);

        // Check if user owns the post
        if ($post->user_id === $user->id) {
            return response()->json(['message' => 'You cannot book your own apartment'], 403);
        }

        // Refresh post to get latest status after expiration check
        $post->refresh();

        // Check if post is available (not rented and not draft)
        if ($post->status === 'rented' || $post->status === 'draft') {
            return response()->json(['message' => 'This apartment is not available for booking'], 403);
        }

        // Check if apartment has active/scheduled contracts (excluding expired ones)
        $now = now();
        $activeContracts = \App\Models\Contract::where('post_id', $post->id)
            ->whereIn('status', ['active', 'signed', 'pending_signing', 'pending'])
            ->with('rentalRequest')
            ->get();
        
        // Check each contract to see if it's truly active (not expired)
        $hasActiveContract = false;
        foreach ($activeContracts as $contract) {
            $isActive = false;
            
            // Check if this is a test duration contract
            if ($contract->rentalRequest && 
                in_array($contract->rentalRequest->duration_type, ['test_10s', 'test_30s'])) {
                
                // For test durations, calculate actual expiration time
                $startDateTime = $contract->created_at ?? $contract->start_date;
                $secondsToAdd = 0;
                
                if ($contract->rentalRequest->duration_type === 'test_10s') {
                    $secondsToAdd = $contract->rentalRequest->duration_multiplier * 10;
                } elseif ($contract->rentalRequest->duration_type === 'test_30s') {
                    $secondsToAdd = $contract->rentalRequest->duration_multiplier * 30;
                }
                
                $expirationTime = $startDateTime->copy()->addSeconds($secondsToAdd);
                $isActive = $now->lessThan($expirationTime);
                
            } else {
                // For regular date-based contracts, check if end_date hasn't passed
                $isActive = $now->format('Y-m-d') <= $contract->end_date->format('Y-m-d');
            }
            
            if ($isActive) {
                $hasActiveContract = true;
                break;
            }
        }
        
        if ($hasActiveContract) {
            return response()->json(['message' => 'This apartment is currently rented or has scheduled bookings'], 403);
        }

        // Check if apartment has any approved or in-progress rental requests (prevent double booking)
        // But exclude those with expired contracts
        $hasActiveBooking = RentalRequest::where('post_id', $post->id)
            ->whereIn('status', [
                'approved', 
                'awaiting_payment', 
                'payment_received', 
                'payment_confirmed', 
                'contract_signing',
                'contract_signed'
            ])
            ->where(function($q) {
                $q->whereDoesntHave('contract')
                  ->orWhereHas('contract', function($subQ) {
                      // Exclude cancelled contracts
                      $subQ->where('status', '!=', 'cancelled')
                           ->where('status', '!=', 'expired')
                           ->where(function($dateQ) {
                               // Only consider contracts that haven't expired
                               $dateQ->whereDate('end_date', '>=', now()->format('Y-m-d'));
                           });
                  });
            })
            ->exists();
        
        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'This apartment is already booked. Another user has an approved rental request for this apartment.'
            ], 403);
        }

        // Check if user already has a pending request for this post
        $existingRequest = RentalRequest::where('user_id', $user->id)
            ->where('post_id', $request->post_id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'You already have a pending request for this apartment'], 400);
        }

        // Calculate start and end dates
        $startDate = RentalDurationService::getNextAvailableStartDate($request->post_id);
        $endDate = RentalDurationService::calculateEndDate(
            $startDate,
            $request->duration_type,
            $request->duration_multiplier
        );

        // Check for overlapping rentals
        if (RentalDurationService::hasOverlap($request->post_id, $startDate, $endDate)) {
            return response()->json([
                'message' => 'This apartment is already booked for the requested period. Please select a different duration.',
                'next_available_start' => $startDate->format('Y-m-d'),
            ], 400);
        }

        $bookingRequest = RentalRequest::create([
            'user_id' => $user->id,
            'post_id' => $request->post_id,
            'status' => 'pending',
            'message' => $request->message,
            'duration_type' => $request->duration_type,
            'duration_multiplier' => $request->duration_multiplier,
            'requested_start_date' => $startDate,
            'requested_end_date' => $endDate,
        ]);

        // Notify apartment owner
        Notification::create([
            'user_id' => $post->user_id,
            'type' => 'booking_request',
            'title' => 'New Booking Request',
            'message' => "{$user->name} has requested to book your apartment: {$post->Title}",
            'data' => [
                'booking_request_id' => $bookingRequest->id,
                'post_id' => $post->id,
                'renter_id' => $user->id,
                'name' => $user->name,
                'title' => $post->Title,
            ],
        ]);

        return response()->json([
            'message' => 'Booking request submitted successfully',
            'booking_request' => $bookingRequest->load(['user', 'post']),
        ], 201);
    }

    /**
     * Get user's booking requests (as renter)
     */
    public function myRequests(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $requests = RentalRequest::with(['post.postimage', 'post.user', 'contract' => function($query) {
                $query->where('status', '!=', 'draft')
                      ->select('id', 'rental_request_id', 'payment_confirmed_by_owner', 'status', 'cancelled_by_admin');
            }])
            ->where('user_id', $user->id)
            ->where(function($query) use ($user) {
                $query->whereNull('hidden_by_user_id')
                      ->orWhere('hidden_by_user_id', '!=', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Get booking requests for user's apartments (as owner)
     */
    public function receivedRequests(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $requests = RentalRequest::with(['user', 'post.postimage', 'contract' => function($query) {
                $query->where('status', '!=', 'draft')
                      ->select('id', 'rental_request_id', 'payment_confirmed_by_owner', 'status', 'cancelled_by_admin');
            }])
            ->whereHas('post', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(function($query) use ($user) {
                $query->whereNull('hidden_by_user_id')
                      ->orWhere('hidden_by_user_id', '!=', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Approve booking request
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $bookingRequest = RentalRequest::with('post')->findOrFail($id);

        // Check if user owns the apartment
        if ($bookingRequest->post->user_id !== $user->id) {
            return response()->json(['message' => 'You can only approve requests for your own apartments'], 403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been processed'], 400);
        }

        // Update status to approved, which means awaiting payment
        $bookingRequest->update(['status' => 'approved']);

        // Notify renter
        Notification::create([
            'user_id' => $bookingRequest->user_id,
            'type' => 'booking_approved',
            'title' => 'Booking Request Approved',
            'message' => "Your booking request for {$bookingRequest->post->Title} has been approved. Please proceed to payment.",
            'data' => [
                'booking_request_id' => $bookingRequest->id,
                'post_id' => $bookingRequest->post_id,
                'title' => $bookingRequest->post->Title,
            ],
        ]);

        return response()->json([
            'message' => 'Booking request approved successfully',
            'booking_request' => $bookingRequest->load(['user', 'post']),
        ]);
    }

    /**
     * Reject booking request
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $bookingRequest = RentalRequest::with('post')->findOrFail($id);

        // Check if user owns the apartment
        if ($bookingRequest->post->user_id !== $user->id) {
            return response()->json(['message' => 'You can only reject requests for your own apartments'], 403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been processed'], 400);
        }

        $bookingRequest->update(['status' => 'rejected']);

        // Notify renter
        Notification::create([
            'user_id' => $bookingRequest->user_id,
            'type' => 'booking_rejected',
            'title' => 'Booking Request Rejected',
            'message' => "Your booking request for {$bookingRequest->post->Title} has been rejected.",
            'data' => [
                'booking_request_id' => $bookingRequest->id,
                'post_id' => $bookingRequest->post_id,
                'title' => $bookingRequest->post->Title,
            ],
        ]);

        return response()->json([
            'message' => 'Booking request rejected successfully',
            'booking_request' => $bookingRequest->load(['user', 'post']),
        ]);
    }

    /**
     * Cancel booking request (by renter or owner)
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $bookingRequest = RentalRequest::with(['post', 'contract' => function($query) {
            $query->select('id', 'rental_request_id', 'payment_confirmed_by_owner', 'status');
        }])->findOrFail($id);

        // Check if user owns the request or the apartment
        $isRenter = $bookingRequest->user_id === $user->id;
        $isOwner = $bookingRequest->post->user_id === $user->id;

        if (!$isRenter && !$isOwner) {
            return response()->json(['message' => 'You can only cancel your own booking requests or requests for your apartments'], 403);
        }

        // Check if owner has confirmed payment receipt - if yes, cannot cancel
        $contract = \App\Models\Contract::where('rental_request_id', $bookingRequest->id)->first();
        if ($contract && $contract->payment_confirmed_by_owner) {
            return response()->json(['message' => 'Cannot cancel request after owner has confirmed payment receipt. Please contact support.'], 400);
        }

        // Can cancel in all stages except after payment confirmation by owner
        // Statuses that can be cancelled: pending, approved, awaiting_payment, payment_received, payment_confirmed (if not confirmed by owner)
        $cancellableStatuses = ['pending', 'approved', 'awaiting_payment', 'payment_received', 'payment_confirmed'];
        if (!in_array($bookingRequest->status, $cancellableStatuses)) {
            return response()->json(['message' => 'This request cannot be cancelled'], 400);
        }

        $oldStatus = $bookingRequest->status;
        $bookingRequest->update(['status' => 'cancelled']);

        // If there's a contract, cancel it too
        if ($contract) {
            $contract->update(['status' => 'cancelled']);
            // Restore the post to active status
            if ($contract->post && $contract->post->status === 'rented') {
                $contract->post->update(['status' => 'active']);
            }
        }

        // Notify the other party
        $otherUserId = $isRenter ? $bookingRequest->post->user_id : $bookingRequest->user_id;
        Notification::create([
            'user_id' => $otherUserId,
            'type' => 'booking_cancelled',
            'title' => 'Booking Request Cancelled',
            'message' => $isRenter 
                ? "{$user->name} has cancelled their booking request for {$bookingRequest->post->Title}."
                : "The owner has cancelled the booking request for {$bookingRequest->post->Title}.",
            'data' => [
                'booking_request_id' => $bookingRequest->id,
                'post_id' => $bookingRequest->post_id,
                'renter_id' => $bookingRequest->user_id,
                'name' => $user->name,
                'title' => $bookingRequest->post->Title,
            ],
        ]);

        return response()->json([
            'message' => 'Booking request cancelled successfully',
            'booking_request' => $bookingRequest->load(['user', 'post']),
        ]);
    }

    /**
     * Expire contracts that have passed their end date (real-time check)
     * This handles both regular date-based contracts and test duration contracts (seconds-based)
     */
    private function expireExpiredContracts($postId)
    {
        $now = now();
        
        // Find contracts that should be expired
        $contractsToCheck = \App\Models\Contract::where('post_id', $postId)
            ->whereIn('status', ['active', 'signed', 'pending_signing', 'pending'])
            ->with(['post', 'rentalRequest'])
            ->get();
        
        foreach ($contractsToCheck as $contract) {
            $isExpired = false;
            
            // Check if this is a test duration contract
            if ($contract->rentalRequest && 
                in_array($contract->rentalRequest->duration_type, ['test_10s', 'test_30s'])) {
                
                // For test durations, calculate actual expiration time
                $startDateTime = $contract->created_at ?? $contract->start_date;
                $secondsToAdd = 0;
                
                if ($contract->rentalRequest->duration_type === 'test_10s') {
                    $secondsToAdd = $contract->rentalRequest->duration_multiplier * 10;
                } elseif ($contract->rentalRequest->duration_type === 'test_30s') {
                    $secondsToAdd = $contract->rentalRequest->duration_multiplier * 30;
                }
                
                $expirationTime = $startDateTime->copy()->addSeconds($secondsToAdd);
                $isExpired = $now->greaterThanOrEqualTo($expirationTime);
                
            } else {
                // For regular date-based contracts, check if end_date has passed
                $isExpired = $now->format('Y-m-d') > $contract->end_date->format('Y-m-d');
            }
            
            if ($isExpired) {
                // Update contract status to expired
                $contract->update(['status' => 'expired']);
                
                // Make the apartment available again if it was marked as rented
                if ($contract->post && $contract->post->status === 'rented') {
                    $contract->post->update(['status' => 'active']);
                }
            }
        }
    }

    /**
     * Delete booking request
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $bookingRequest = RentalRequest::with('post')->findOrFail($id);

        // Check if user owns the request or the apartment
        $canDelete = $bookingRequest->user_id === $user->id || $bookingRequest->post->user_id === $user->id;

        if (!$canDelete) {
            return response()->json(['message' => 'You can only delete your own booking requests or requests for your apartments'], 403);
        }

        // Check if there's a contract associated
        $contract = \App\Models\Contract::where('rental_request_id', $bookingRequest->id)->first();
        
        // Allow deletion if:
        // 1. Request status is cancelled or rejected, OR
        // 2. Contract is cancelled (especially if cancelled by admin)
        $canDelete = in_array($bookingRequest->status, ['cancelled', 'rejected']);
        
        if ($contract) {
            // If contract is cancelled, allow deletion even if request status is contract_signed
            if ($contract->status === 'cancelled') {
                $canDelete = true;
            } elseif ($contract->status !== 'cancelled') {
                // If contract exists and is not cancelled, cannot delete
                return response()->json(['message' => 'Cannot delete request with active contract. Please cancel the contract first.'], 400);
            }
        }
        
        if (!$canDelete) {
            return response()->json(['message' => 'Can only delete cancelled or rejected requests, or requests with cancelled contracts'], 400);
        }

        // Instead of deleting, hide the request from the user who requested deletion
        // The request will still be visible to admin and the other party
        $bookingRequest->update(['hidden_by_user_id' => $user->id]);

        return response()->json([
            'message' => 'Booking request hidden successfully',
        ]);
    }
}
