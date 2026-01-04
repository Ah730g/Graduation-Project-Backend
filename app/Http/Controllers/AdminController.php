<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\RentalRequest;
use App\Models\Contract;
use App\Models\Review;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_apartments' => Post::count(),
            'total_rental_requests' => RentalRequest::count(),
            'active_contracts' => Contract::where('status', 'active')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all users with pagination and search
     */
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        
        $query = User::query();
        
        // If search term is provided, search in multiple fields
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                // Search in users table
                $q->where('users.name', 'LIKE', "%{$search}%")
                  ->orWhere('users.email', 'LIKE', "%{$search}%");
            })
            ->orWhereHas('identityVerifications', function($q) use ($search) {
                // Search in identity_verifications table
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('document_number', 'LIKE', "%{$search}%");
            });
        }
        
        $users = $query->select('id', 'name', 'email', 'role', 'status', 'avatar', 'created_at')
            ->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get user details with activities
     */
    public function getUserDetails($id)
    {
        $user = User::with([
            'post' => function ($query) {
                $query->select('id', 'user_id', 'Title', 'Address', 'Price', 'status', 'created_at');
            },
            'contracts' => function ($query) {
                $query->with(['post:id,Title,Address'])->select('id', 'user_id', 'post_id', 'start_date', 'end_date', 'status', 'created_at');
            },
            'rentalRequests' => function ($query) {
                $query->with(['post:id,Title,Address'])->select('id', 'user_id', 'post_id', 'status', 'requested_at', 'created_at');
            },
            'savedPost' => function ($query) {
                $query->with(['post:id,Title,Address'])->select('id', 'user_id', 'post_id', 'created_at');
            },
            'reviews' => function ($query) {
                $query->with(['post:id,Title'])->select('id', 'user_id', 'post_id', 'rating', 'comment', 'status', 'created_at');
            },
            'identityVerifications' => function ($query) {
                $query->select('id', 'user_id', 'document_type', 'document_front_url', 'document_back_url', 'status', 'full_name', 'document_number', 'date_of_birth', 'place_of_birth', 'nationality', 'issue_date', 'expiry_date', 'address', 'admin_notes', 'reviewed_at', 'created_at')
                      ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Get latest identity verification
        $latestIdentity = $user->identityVerifications->first();

        return response()->json([
            'user' => $user->makeHidden(['password', 'remember_token']),
            'identity' => $latestIdentity,
            'activities' => [
                'posts' => $user->post,
                'contracts' => $user->contracts,
                'rental_requests' => $user->rentalRequests,
                'saved_posts' => $user->savedPost,
                'reviews' => $user->reviews,
            ],
            'stats' => [
                'total_posts' => $user->post->count(),
                'total_contracts' => $user->contracts->count(),
                'total_rental_requests' => $user->rentalRequests->count(),
                'total_saved_posts' => $user->savedPost->count(),
                'total_reviews' => $user->reviews->count(),
            ]
        ]);
    }

    /**
     * Update user details
     */
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,user',
            'status' => 'sometimes|in:active,disabled',
            'avatar' => 'sometimes|nullable|string',
            'password' => 'sometimes|nullable|min:6',
        ]);

        $user = User::findOrFail($id);
        
        // Prevent changing admin role of other admins (optional security)
        if ($request->has('role') && $user->role === 'admin' && $request->role !== 'admin') {
            return response()->json(['message' => 'Cannot change admin role'], 403);
        }

        $user->fill($request->only(['name', 'email', 'role', 'status', 'avatar']));
        
        if ($request->has('password') && $request->password) {
            $user->password = bcrypt($request->password);
        }
        
        $user->save();

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Update user status (enable/disable)
     */
    public function updateUserStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,disabled',
        ]);

        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'User status updated successfully', 'user' => $user]);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting admin users
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete admin user'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get all posts with status
     */
    public function getPosts(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $posts = Post::with(['user:id,name,email', 'porperty'])
            ->select('id', 'user_id', 'Title', 'Address', 'Price', 'status', 'created_at')
            ->paginate($perPage);

        return response()->json($posts);
    }

    /**
     * Get post details
     */
    public function getPostDetails($id)
    {
        $post = Post::with(['user:id,name,email', 'porperty', 'postimage'])
            ->findOrFail($id);
        
        return response()->json($post);
    }

    /**
     * Update post details
     */
    public function updatePost(Request $request, $id)
    {
        $request->validate([
            'Title' => 'sometimes|string|max:255',
            'Price' => 'sometimes|numeric',
            'Address' => 'sometimes|string',
            'Description' => 'sometimes|string',
            'City' => 'sometimes|string',
            'Bedrooms' => 'sometimes|integer',
            'Bathrooms' => 'sometimes|integer',
            'status' => 'sometimes|in:active,pending,rented,blocked',
        ]);

        $post = Post::findOrFail($id);
        $post->fill($request->only([
            'Title', 'Price', 'Address', 'Description', 'City',
            'Bedrooms', 'Bathrooms', 'status'
        ]));
        $post->save();

        return response()->json(['message' => 'Post updated successfully', 'post' => $post]);
    }

    /**
     * Update post status (approve/reject/block)
     */
    public function updatePostStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,pending,rented,blocked',
        ]);

        $post = Post::findOrFail($id);
        $post->status = $request->status;
        $post->save();

        return response()->json(['message' => 'Post status updated successfully', 'post' => $post]);
    }

    /**
     * Delete a post
     */
    public function deletePost($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Get all rental requests
     */
    public function getRentalRequests(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $requests = RentalRequest::with(['user:id,name,email', 'post:id,Title,Address'])
            ->select('id', 'user_id', 'post_id', 'status', 'message', 'requested_at', 'created_at')
            ->paginate($perPage);

        return response()->json($requests);
    }

    /**
     * Update rental request status (Admin can cancel in all stages)
     */
    public function updateRentalRequestStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled',
        ]);

        $rentalRequest = RentalRequest::with(['post', 'contract'])->findOrFail($id);
        $oldStatus = $rentalRequest->status;
        $rentalRequest->status = $request->status;
        $rentalRequest->save();

        // If cancelling, also cancel associated contract and restore post
        if ($request->status === 'cancelled') {
            $contract = \App\Models\Contract::where('rental_request_id', $rentalRequest->id)->first();
            if ($contract) {
                $contract->update(['status' => 'cancelled']);
                // Restore the post to active status
                if ($contract->post && $contract->post->status === 'rented') {
                    $contract->post->update(['status' => 'active']);
                }
            }

            // Notify both parties
            \App\Models\Notification::create([
                'user_id' => $rentalRequest->post->user_id,
                'type' => 'booking_cancelled',
                'title' => 'Booking Request Cancelled by Administration',
                'message' => "Your booking request for {$rentalRequest->post->Title} has been cancelled by the administration.",
                'data' => [
                    'booking_request_id' => $rentalRequest->id,
                    'post_id' => $rentalRequest->post_id,
                    'title' => $rentalRequest->post->Title,
                ],
            ]);

            \App\Models\Notification::create([
                'user_id' => $rentalRequest->user_id,
                'type' => 'booking_cancelled',
                'title' => 'Booking Request Cancelled by Administration',
                'message' => "Your booking request for {$rentalRequest->post->Title} has been cancelled by the administration.",
                'data' => [
                    'booking_request_id' => $rentalRequest->id,
                    'post_id' => $rentalRequest->post_id,
                    'title' => $rentalRequest->post->Title,
                ],
            ]);
        }

        return response()->json(['message' => 'Rental request status updated successfully', 'request' => $rentalRequest]);
    }

    /**
     * Delete a rental request
     */
    public function deleteRentalRequest($id)
    {
        $rentalRequest = RentalRequest::with(['post', 'contract'])->findOrFail($id);
        
        // If there's an associated contract, cancel it first
        if ($rentalRequest->contract) {
            $contract = $rentalRequest->contract;
            if ($contract->post && $contract->status === 'active') {
                $contract->post->update(['status' => 'active']);
            }
            $contract->update(['status' => 'cancelled']);
        }
        
        // Notify both parties before deletion
        if ($rentalRequest->post) {
            \App\Models\Notification::create([
                'user_id' => $rentalRequest->post->user_id,
                'type' => 'booking_deleted_by_admin',
                'title' => 'Booking Request Deleted by Administration',
                'message' => "The booking request for {$rentalRequest->post->Title} has been deleted by the administration.",
                'data' => [
                    'booking_request_id' => $rentalRequest->id,
                    'post_id' => $rentalRequest->post_id,
                    'title' => $rentalRequest->post->Title,
                ],
            ]);
        }

        \App\Models\Notification::create([
            'user_id' => $rentalRequest->user_id,
            'type' => 'booking_deleted_by_admin',
            'title' => 'Booking Request Deleted by Administration',
            'message' => "Your booking request for {$rentalRequest->post->Title} has been deleted by the administration.",
            'data' => [
                'booking_request_id' => $rentalRequest->id,
                'post_id' => $rentalRequest->post_id,
                'title' => $rentalRequest->post->Title,
            ],
        ]);
        
        $rentalRequest->delete();

        return response()->json(['message' => 'Rental request deleted successfully']);
    }

    /**
     * Get all contracts
     */
    public function getContracts(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $contracts = Contract::with(['user:id,name,email', 'post:id,Title,Address'])
            ->where('status', '!=', 'draft')
            ->select('id', 'user_id', 'post_id', 'start_date', 'end_date', 'monthly_rent', 'status', 'created_at')
            ->paginate($perPage);

        return response()->json($contracts);
    }

    /**
     * Update contract status
     */
    public function updateContractStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,expired,cancelled',
        ]);

        $contract = Contract::with(['post', 'user', 'rentalRequest'])->findOrFail($id);
        $oldStatus = $contract->status;
        $contract->status = $request->status;
        
        // If cancelling the contract, mark as cancelled by admin and restore the post
        if ($request->status === 'cancelled') {
            $contract->cancelled_by_admin = true;
            
            // Restore the post to active status (make it available again)
            // Always set to active regardless of current status to ensure it appears in listings
            if ($contract->post) {
                $contract->post->update(['status' => 'active']);
            }
            
            // Update rental request status to cancelled if it exists
            if ($contract->rentalRequest) {
                $contract->rentalRequest->update(['status' => 'cancelled']);
            }
            
            // Notify both parties
            \App\Models\Notification::create([
                'user_id' => $contract->post->user_id,
                'type' => 'contract_cancelled_by_admin',
                'title' => 'Contract Cancelled by Administration',
                'message' => "The contract for {$contract->post->Title} has been cancelled by the administration.",
                'data' => [
                    'contract_id' => $contract->id,
                    'post_id' => $contract->post_id,
                    'title' => $contract->post->Title,
                ],
            ]);

            $renterId = $contract->user_id ?? ($contract->rentalRequest ? $contract->rentalRequest->user_id : null);
            if ($renterId) {
                \App\Models\Notification::create([
                    'user_id' => $renterId,
                    'type' => 'contract_cancelled_by_admin',
                    'title' => 'Contract Cancelled by Administration',
                    'message' => "The contract for {$contract->post->Title} has been cancelled by the administration.",
                    'data' => [
                        'contract_id' => $contract->id,
                        'post_id' => $contract->post_id,
                        'title' => $contract->post->Title,
                    ],
                ]);
            }
        }
        
        $contract->save();

        return response()->json(['message' => 'Contract status updated successfully', 'contract' => $contract]);
    }

    /**
     * Delete a contract
     */
    public function deleteContract($id)
    {
        $contract = Contract::with(['post', 'user', 'rentalRequest'])->findOrFail($id);
        
        // Restore the post to active status if contract is active
        if ($contract->post && $contract->status === 'active') {
            $contract->post->update(['status' => 'active']);
        }
        
        // Update rental request status to cancelled if it exists
        if ($contract->rentalRequest) {
            $contract->rentalRequest->update(['status' => 'cancelled']);
        }
        
        // Notify both parties before deletion
        if ($contract->post) {
            \App\Models\Notification::create([
                'user_id' => $contract->post->user_id,
                'type' => 'contract_deleted_by_admin',
                'title' => 'Contract Deleted by Administration',
                'message' => "The contract for {$contract->post->Title} has been deleted by the administration.",
                'data' => [
                    'contract_id' => $contract->id,
                    'post_id' => $contract->post_id,
                    'title' => $contract->post->Title,
                ],
            ]);
        }

        $renterId = $contract->user_id ?? ($contract->rentalRequest ? $contract->rentalRequest->user_id : null);
        if ($renterId) {
            \App\Models\Notification::create([
                'user_id' => $renterId,
                'type' => 'contract_deleted_by_admin',
                'title' => 'Contract Deleted by Administration',
                'message' => "The contract for {$contract->post->Title} has been deleted by the administration.",
                'data' => [
                    'contract_id' => $contract->id,
                    'post_id' => $contract->post_id,
                    'title' => $contract->post->Title,
                ],
            ]);
        }
        
        $contract->delete();

        return response()->json(['message' => 'Contract deleted successfully']);
    }

    /**
     * Get all reviews
     */
    public function getReviews(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $reviews = Review::with(['user:id,name,email', 'post:id,Title'])
            ->select('id', 'user_id', 'post_id', 'rating', 'comment', 'status', 'created_at')
            ->paginate($perPage);

        return response()->json($reviews);
    }

    /**
     * Delete a review
     */
    public function deleteReview($id)
    {
        $review = Review::findOrFail($id);
        $review->status = 'removed';
        $review->save();

        return response()->json(['message' => 'Review removed successfully']);
    }

    /**
     * Get system notifications
     */
    public function getNotifications()
    {
        // For now, return placeholder notifications
        // In a real system, this would come from a notifications table
        $notifications = [
            [
                'id' => 1,
                'type' => 'new_listing',
                'message' => 'New apartment listing pending approval',
                'created_at' => now()->subHours(2),
            ],
            [
                'id' => 2,
                'type' => 'rental_request',
                'message' => 'New rental request received',
                'created_at' => now()->subHours(5),
            ],
        ];

        return response()->json($notifications);
    }

    /**
     * Get platform settings
     */
    public function getSettings()
    {
        // For now, return placeholder settings
        // In a real system, this would come from a settings table
        $settings = [
            'terms_and_conditions' => 'Default terms and conditions...',
            'privacy_policy' => 'Default privacy policy...',
        ];

        return response()->json($settings);
    }

    /**
     * Update platform settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'terms_and_conditions' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
        ]);

        // For now, just return success
        // In a real system, this would save to a settings table
        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $request->only(['terms_and_conditions', 'privacy_policy']),
        ]);
    }
}
