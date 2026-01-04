<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IdentityVerificationController;
use App\Http\Controllers\ImageKitController;
use App\Http\Controllers\postController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SavedPostController;
use App\Http\Controllers\userController;
use App\Http\Resources\UserResource;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/signup",[AuthController::class, "signup"]);
Route::post("/login",[AuthController::class, "login"]);
Route::get("/user-posts/{id}",[userController::class,"getUserPosts"]);
Route::get("/property",[PropertyController::class,"index"]);
Route::get("/is-post-saved",[SavedPostController::class,"isPostSaved"]);
// Public reputation endpoint (no auth required)
Route::get("/users/{userId}/reputation",[App\Http\Controllers\ReviewController::class,"getReputation"]);

Route::middleware("auth:sanctum")->group(function () {

        Route::post("/logout",[AuthController::class, "Logout"]);
        Route::get("/user",function(Request $request){
            return new UserResource($request->user());
        });
        Route::apiResource("/users",userController::class);
        Route::apiResource("/post",postController::class);
        Route::get('/imagekit/auth', [ImageKitController::class, 'auth']);
        Route::post('/identity-verification', [IdentityVerificationController::class, 'store']);
        Route::get('/identity-verification', [IdentityVerificationController::class, 'show']);
        Route::post("/saved-posts",[SavedPostController::class,"store"]);
        Route::get("/saved-posts/{id}",[SavedPostController::class,"index"]);

        Route::delete("/saved-posts",[SavedPostController::class,"destroy"]);
        
        // Booking Requests
        Route::post("/booking-requests",[App\Http\Controllers\BookingController::class,"store"]);
        Route::get("/booking-requests/my-requests",[App\Http\Controllers\BookingController::class,"myRequests"]);
        Route::get("/booking-requests/received",[App\Http\Controllers\BookingController::class,"receivedRequests"]);
        Route::post("/booking-requests/{id}/approve",[App\Http\Controllers\BookingController::class,"approve"]);
        Route::post("/booking-requests/{id}/reject",[App\Http\Controllers\BookingController::class,"reject"]);
        Route::post("/booking-requests/{id}/cancel",[App\Http\Controllers\BookingController::class,"cancel"]);
        Route::delete("/booking-requests/{id}",[App\Http\Controllers\BookingController::class,"destroy"]);
        
        // Payments
        Route::post("/payments",[App\Http\Controllers\PaymentController::class,"store"]);
        Route::post("/payments/{id}/confirm",[App\Http\Controllers\PaymentController::class,"confirm"]);
        
        // Contracts
        Route::get("/contracts",[App\Http\Controllers\ContractController::class,"index"]);
        Route::get("/contracts/{id}",[App\Http\Controllers\ContractController::class,"show"]);
        Route::get("/contracts/{id}/pdf",[App\Http\Controllers\ContractController::class,"downloadPdf"]);
        Route::put("/contracts/{id}",[App\Http\Controllers\ContractController::class,"update"]);
        Route::post("/contracts/{id}/sign",[App\Http\Controllers\ContractController::class,"sign"]);
        Route::post("/contracts/{id}/confirm-payment",[App\Http\Controllers\ContractController::class,"confirmPayment"]);
        Route::delete("/contracts/{id}",[App\Http\Controllers\ContractController::class,"destroy"]);
        
        // Notifications
        Route::get("/notifications",[App\Http\Controllers\NotificationController::class,"index"]);
        Route::get("/notifications/unread-count",[App\Http\Controllers\NotificationController::class,"unreadCount"]);
        Route::post("/notifications/{id}/read",[App\Http\Controllers\NotificationController::class,"markAsRead"]);
        Route::post("/notifications/read-all",[App\Http\Controllers\NotificationController::class,"markAllAsRead"]);
        Route::delete("/notifications/{id}",[App\Http\Controllers\NotificationController::class,"destroy"]);
        Route::delete("/notifications",[App\Http\Controllers\NotificationController::class,"deleteAll"]);
        
        // Reviews/Ratings (two-sided rating system)
        Route::get("/reviews/eligible-contracts",[App\Http\Controllers\ReviewController::class,"getEligibleContracts"]);
        Route::post("/reviews",[App\Http\Controllers\ReviewController::class,"store"]);
        Route::get("/reviews/contract/{contractId}",[App\Http\Controllers\ReviewController::class,"getContractReviews"]);
        Route::get("/reviews/user/{userId}",[App\Http\Controllers\ReviewController::class,"index"]);
        Route::get("/reviews",[App\Http\Controllers\ReviewController::class,"index"]);
        Route::put("/reviews/{id}",[App\Http\Controllers\ReviewController::class,"update"]);
        Route::delete("/reviews/{id}",[App\Http\Controllers\ReviewController::class,"destroy"]);

}
);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::patch('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/posts', [AdminController::class, 'getPosts']);
    Route::get('/posts/{id}', [AdminController::class, 'getPostDetails']);
    Route::put('/posts/{id}', [AdminController::class, 'updatePost']);
    Route::patch('/posts/{id}/status', [AdminController::class, 'updatePostStatus']);
    Route::delete('/posts/{id}', [AdminController::class, 'deletePost']);
    Route::get('/rental-requests', [AdminController::class, 'getRentalRequests']);
    Route::patch('/rental-requests/{id}/status', [AdminController::class, 'updateRentalRequestStatus']);
    Route::delete('/rental-requests/{id}', [AdminController::class, 'deleteRentalRequest']);
    Route::get('/contracts', [AdminController::class, 'getContracts']);
    Route::patch('/contracts/{id}/status', [AdminController::class, 'updateContractStatus']);
    Route::delete('/contracts/{id}', [AdminController::class, 'deleteContract']);
    Route::get('/reviews', [AdminController::class, 'getReviews']);
    Route::delete('/reviews/{id}', [AdminController::class, 'deleteReview']);
    Route::get('/notifications', [AdminController::class, 'getNotifications']);
    Route::get('/settings', [AdminController::class, 'getSettings']);
    Route::put('/settings', [AdminController::class, 'updateSettings']);
    // Identity Verification Admin Routes
    Route::get('/identity-verifications', [IdentityVerificationController::class, 'getAll']);
    Route::get('/identity-verifications/pending', [IdentityVerificationController::class, 'getPending']);
    Route::get('/identity-verifications/{id}', [IdentityVerificationController::class, 'getDetails']);
    Route::post('/identity-verifications/{id}/approve', [IdentityVerificationController::class, 'approve']);
    Route::post('/identity-verifications/{id}/reject', [IdentityVerificationController::class, 'reject']);
    Route::post('/identity-verifications/{id}/reject-after-approval', [IdentityVerificationController::class, 'rejectAfterApproval']);
    Route::delete('/identity-verifications/{id}', [IdentityVerificationController::class, 'destroy']);
});

