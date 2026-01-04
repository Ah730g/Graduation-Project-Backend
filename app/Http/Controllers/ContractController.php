<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Post;
use App\Models\Notification;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    /**
     * Get user's contracts
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contracts = Contract::with(['post.postimage', 'post.user', 'payment', 'rentalRequest'])
            ->where('status', '!=', 'draft')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('post', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($contracts);
    }

    /**
     * Get contract details
     */
    public function show($id)
    {
        $contract = Contract::with([
            'post.postimage', 
            'post.user',
            'post.user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            },
            'payment', 
            'user',
            'user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            },
            'rentalRequest',
            'rentalRequest.user',
            'rentalRequest.user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            }
        ])->findOrFail($id);

        // Make identity fields visible for owner
        if ($contract->post && $contract->post->user) {
            // Reload identity verifications if not loaded
            if (!$contract->post->user->relationLoaded('identityVerifications')) {
                $contract->post->user->load(['identityVerifications' => function($query) {
                    $query->where('status', 'approved')->latest()->limit(1);
                }]);
            }
            
            if ($contract->post->user->identityVerifications && $contract->post->user->identityVerifications->isNotEmpty()) {
                $ownerIdentity = $contract->post->user->identityVerifications->first();
                $ownerIdentity->makeVisible([
                    'full_name',
                    'document_number',
                    'date_of_birth',
                    'place_of_birth',
                    'nationality',
                    'issue_date',
                    'expiry_date',
                    'address',
                    'document_type',
                    'document_front_url',
                    'document_back_url',
                ]);
                // Force refresh the relationship
                $contract->post->user->setRelation('identityVerifications', collect([$ownerIdentity]));
            }
        }

        // Make identity fields visible for renter (check both contract.user and rentalRequest.user)
        $renterUser = null;
        if ($contract->rentalRequest && $contract->rentalRequest->user) {
            $renterUser = $contract->rentalRequest->user;
            // Reload identity verifications if not loaded
            if (!$renterUser->relationLoaded('identityVerifications')) {
                $renterUser->load(['identityVerifications' => function($query) {
                    $query->where('status', 'approved')->latest()->limit(1);
                }]);
            }
        } elseif ($contract->user) {
            $renterUser = $contract->user;
            // Reload identity verifications if not loaded
            if (!$renterUser->relationLoaded('identityVerifications')) {
                $renterUser->load(['identityVerifications' => function($query) {
                    $query->where('status', 'approved')->latest()->limit(1);
                }]);
            }
        }
            
        if ($renterUser && $renterUser->identityVerifications && $renterUser->identityVerifications->isNotEmpty()) {
            $renterIdentity = $renterUser->identityVerifications->first();
            $renterIdentity->makeVisible([
                'full_name',
                'document_number',
                'date_of_birth',
                'place_of_birth',
                'nationality',
                'issue_date',
                'expiry_date',
                'address',
                'document_type',
                'document_front_url',
                'document_back_url',
            ]);
            // Force refresh the relationship
            $renterUser->setRelation('identityVerifications', collect([$renterIdentity]));
        }
        
        // Also make identity fields visible for contract.user if it exists separately and different from renter
        if ($contract->user && $contract->user->id !== ($renterUser ? $renterUser->id : null)) {
            // Reload identity verifications if not loaded
            if (!$contract->user->relationLoaded('identityVerifications')) {
                $contract->user->load(['identityVerifications' => function($query) {
                    $query->where('status', 'approved')->latest()->limit(1);
                }]);
            }
            
            if ($contract->user->identityVerifications && $contract->user->identityVerifications->isNotEmpty()) {
                $userIdentity = $contract->user->identityVerifications->first();
                $userIdentity->makeVisible([
                    'full_name',
                    'document_number',
                    'date_of_birth',
                    'place_of_birth',
                    'nationality',
                    'issue_date',
                    'expiry_date',
                    'address',
                    'document_type',
                    'document_front_url',
                    'document_back_url',
                ]);
                // Force refresh the relationship
                $contract->user->setRelation('identityVerifications', collect([$userIdentity]));
            }
        }

        // Convert to array to ensure all visible fields are included
        $contractData = $contract->toArray();
        
        // Manually add identity verification data if not already visible
        if ($contract->post && $contract->post->user && $contract->post->user->identityVerifications && $contract->post->user->identityVerifications->isNotEmpty()) {
            $ownerIdentity = $contract->post->user->identityVerifications->first();
            $contractData['post']['user']['identity_verifications'] = [[
                'full_name' => $ownerIdentity->full_name,
                'document_number' => $ownerIdentity->document_number,
                'date_of_birth' => $ownerIdentity->date_of_birth,
                'place_of_birth' => $ownerIdentity->place_of_birth,
                'nationality' => $ownerIdentity->nationality,
                'issue_date' => $ownerIdentity->issue_date,
                'expiry_date' => $ownerIdentity->expiry_date,
                'address' => $ownerIdentity->address,
                'document_type' => $ownerIdentity->document_type,
                'document_front_url' => $ownerIdentity->document_front_url,
                'document_back_url' => $ownerIdentity->document_back_url,
            ]];
        }
        
        // Add renter identity verification data
        $renterUser = null;
        if ($contract->rentalRequest && $contract->rentalRequest->user) {
            $renterUser = $contract->rentalRequest->user;
        } elseif ($contract->user) {
            $renterUser = $contract->user;
        }
        
        if ($renterUser && $renterUser->identityVerifications && $renterUser->identityVerifications->isNotEmpty()) {
            $renterIdentity = $renterUser->identityVerifications->first();
            if ($contract->rentalRequest) {
                $contractData['rental_request']['user']['identity_verifications'] = [[
                    'full_name' => $renterIdentity->full_name,
                    'document_number' => $renterIdentity->document_number,
                    'date_of_birth' => $renterIdentity->date_of_birth,
                    'place_of_birth' => $renterIdentity->place_of_birth,
                    'nationality' => $renterIdentity->nationality,
                    'issue_date' => $renterIdentity->issue_date,
                    'expiry_date' => $renterIdentity->expiry_date,
                    'address' => $renterIdentity->address,
                    'document_type' => $renterIdentity->document_type,
                    'document_front_url' => $renterIdentity->document_front_url,
                    'document_back_url' => $renterIdentity->document_back_url,
                ]];
            }
            if ($contract->user) {
                $contractData['user']['identity_verifications'] = [[
                    'full_name' => $renterIdentity->full_name,
                    'document_number' => $renterIdentity->document_number,
                    'date_of_birth' => $renterIdentity->date_of_birth,
                    'place_of_birth' => $renterIdentity->place_of_birth,
                    'nationality' => $renterIdentity->nationality,
                    'issue_date' => $renterIdentity->issue_date,
                    'expiry_date' => $renterIdentity->expiry_date,
                    'address' => $renterIdentity->address,
                    'document_type' => $renterIdentity->document_type,
                    'document_front_url' => $renterIdentity->document_front_url,
                    'document_back_url' => $renterIdentity->document_back_url,
                ]];
            }
        }
        
        return response()->json($contractData);
    }

    /**
     * Update contract (for draft editing)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with(['post', 'payment'])->findOrFail($id);

        // Check if user is owner or renter
        $isOwner = $contract->post->user_id === $user->id;
        $isRenter = $contract->user_id === $user->id;

        if (!$isOwner && !$isRenter) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only allow editing if contract is draft or pending (before payment confirmation)
        if (!in_array($contract->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Only draft or pending contracts can be edited'], 400);
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'monthly_rent' => 'sometimes|numeric|min:0',
            'terms' => 'sometimes|string',
        ]);

        $contract->update($request->only([
            'start_date',
            'end_date',
            'monthly_rent',
            'terms',
        ]));

        // Check if at least 4 fields are filled for draft
        $filledFields = 0;
        $fields = ['start_date', 'end_date', 'monthly_rent', 'terms'];
        foreach ($fields as $field) {
            if ($contract->$field !== null && $contract->$field !== '') {
                $filledFields++;
            }
        }

        if ($filledFields >= 4) {
            $contract->update(['status' => 'pending']);
        }

        return response()->json([
            'message' => 'Contract updated successfully',
            'contract' => $contract->fresh(),
        ]);
    }

    /**
     * Sign contract
     */
    public function sign(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with(['post', 'payment', 'rentalRequest'])->findOrFail($id);

        $isOwner = $contract->post->user_id === $user->id;
        $isRenter = $contract->user_id === $user->id || 
                   ($contract->rentalRequest && $contract->rentalRequest->user_id === $user->id);

        if (!$isOwner && !$isRenter) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only sign if contract is in pending_signing status (after payment confirmation)
        if ($contract->status !== 'pending_signing') {
            return response()->json(['message' => 'Contract is not ready for signing. Please wait for payment confirmation.'], 400);
        }

        // Check if payment is confirmed by owner
        if (!$contract->payment_confirmed_by_owner) {
            return response()->json(['message' => 'Payment must be confirmed by owner before signing'], 400);
        }

        $request->validate([
            'signature' => 'required|string',
            'signature_type' => 'required|in:typed,otp',
        ]);

        $updateData = [];
        if ($isOwner) {
            $updateData['owner_signature'] = $request->signature;
            $updateData['owner_signed_at'] = now();
        } else {
            $updateData['renter_signature'] = $request->signature;
            $updateData['renter_signed_at'] = now();
        }

        $contract->update($updateData);

        // Check if both parties have signed
        if ($contract->owner_signature && $contract->renter_signature) {
            // After both parties sign, mark contract as signed first, then active
            $contract->update(['status' => 'signed']);
            
            // Update rental request status to contract_signed (تم توقيع العقد)
            if ($contract->rentalRequest) {
                $contract->rentalRequest->update(['status' => 'contract_signed']);
            }
            
            // If payment is confirmed, mark as active immediately
            if ($contract->payment_confirmed_by_owner) {
                $contract->update(['status' => 'active']);
            }

            // Get renter ID (check both contract.user_id and rentalRequest.user_id)
            $renterId = $contract->user_id ?? ($contract->rentalRequest ? $contract->rentalRequest->user_id : null);
            
            // Notify both parties that contract is completed
            Notification::create([
                'user_id' => $contract->post->user_id,
                'type' => 'contract_completed',
                'title' => 'Contract Completed',
                'message' => "The contract for {$contract->post->Title} has been fully signed by both parties and is now active.",
                'data' => ['contract_id' => $contract->id, 'post_id' => $contract->post_id],
            ]);

            if ($renterId) {
                Notification::create([
                    'user_id' => $renterId,
                    'type' => 'contract_completed',
                    'title' => 'Contract Completed',
                    'message' => "The contract for {$contract->post->Title} has been fully signed by both parties and is now active.",
                    'data' => ['contract_id' => $contract->id, 'post_id' => $contract->post_id],
                ]);
            }
        } else {
            // Notify the other party
            $renterId = $contract->user_id ?? ($contract->rentalRequest ? $contract->rentalRequest->user_id : null);
            $otherUserId = $isOwner ? $renterId : $contract->post->user_id;
            
            if ($otherUserId) {
                Notification::create([
                    'user_id' => $otherUserId,
                    'type' => 'contract_partially_signed',
                    'title' => 'Contract Partially Signed',
                    'message' => "The contract for {$contract->post->Title} has been signed. Please sign to complete.",
                    'data' => [
                        'contract_id' => $contract->id, 
                        'post_id' => $contract->post_id,
                        'title' => $contract->post->Title,
                    ],
                ]);
            }
        }

        return response()->json([
            'message' => 'Contract signed successfully',
            'contract' => $contract->fresh(),
        ]);
    }

    /**
     * Confirm payment receipt (by owner)
     */
    public function confirmPayment(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with(['post', 'payment', 'rentalRequest'])->findOrFail($id);

        // Only owner can confirm payment
        if ($contract->post->user_id !== $user->id) {
            return response()->json(['message' => 'Only apartment owner can confirm payment'], 403);
        }

        if ($contract->payment_confirmed_by_owner) {
            return response()->json(['message' => 'Payment already confirmed'], 400);
        }

        // Can only confirm payment if contract is in pending status (after payment is paid)
        if ($contract->status !== 'pending') {
            return response()->json(['message' => 'Contract is not in pending status'], 400);
        }

        // Check if payment exists and is paid
        $contract->load('payment');
        if (!$contract->payment || $contract->payment->status !== 'paid') {
            return response()->json(['message' => 'Payment must be completed before confirming receipt'], 400);
        }

        $contract->update([
            'payment_confirmed_by_owner' => true,
            'payment_confirmed_at' => now(),
            'status' => 'pending_signing', // Move to signing stage after payment confirmation
        ]);

        // Update rental request status to payment_confirmed (تأكيد الاستلام)
        if ($contract->rentalRequest) {
            $contract->rentalRequest->update(['status' => 'payment_confirmed']);
        }

        // Remove apartment from public listings (hide the ad)
        $contract->post->update(['status' => 'rented']);

        // Get renter ID (check both contract.user_id and rentalRequest.user_id)
        $renterId = $contract->user_id ?? ($contract->rentalRequest ? $contract->rentalRequest->user_id : null);

        // Notify renter that payment is confirmed and they can now sign the contract
        if ($renterId) {
            Notification::create([
                'user_id' => $renterId,
                'type' => 'payment_confirmed_by_owner',
                'title' => 'Payment Confirmed by Owner',
                'message' => "The owner has confirmed receipt of payment for {$contract->post->Title}. Please review and sign the contract.",
                'data' => ['contract_id' => $contract->id, 'post_id' => $contract->post_id],
            ]);
        }

        // Notify owner that they can now sign the contract
        Notification::create([
            'user_id' => $contract->post->user_id,
            'type' => 'ready_for_signing',
            'title' => 'Contract Ready for Signing',
            'message' => "Payment has been confirmed. Please review and sign the contract for {$contract->post->Title}.",
            'data' => ['contract_id' => $contract->id, 'post_id' => $contract->post_id],
        ]);

        return response()->json([
            'message' => 'Payment confirmed and apartment marked as rented',
            'contract' => $contract->fresh(),
        ]);
    }

    /**
     * Delete contract (by owner or renter after cancellation)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with(['post', 'rentalRequest'])->findOrFail($id);

        // Check if user is owner or renter
        $isOwner = $contract->post->user_id === $user->id;
        $isRenter = $contract->user_id === $user->id || 
                   ($contract->rentalRequest && $contract->rentalRequest->user_id === $user->id);

        if (!$isOwner && !$isRenter) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only delete cancelled contracts
        if ($contract->status !== 'cancelled') {
            return response()->json(['message' => 'Can only delete cancelled contracts'], 400);
        }

        // Delete the contract
        $contract->delete();

        return response()->json([
            'message' => 'Contract deleted successfully',
        ]);
    }

    /**
     * Download contract as PDF
     */
    public function downloadPdf(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $contract = Contract::with([
            'post.postimage', 
            'post.user',
            'post.user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            },
            'payment', 
            'user',
            'user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            },
            'rentalRequest',
            'rentalRequest.user',
            'rentalRequest.user.identityVerifications' => function($query) {
                $query->where('status', 'approved')->latest()->limit(1);
            }
        ])->findOrFail($id);

        // Check if user is owner or renter
        $isOwner = $contract->post->user_id === $user->id;
        $isRenter = $contract->user_id === $user->id || 
                   ($contract->rentalRequest && $contract->rentalRequest->user_id === $user->id);

        if (!$isOwner && !$isRenter && !$user->isAdmin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Make identity fields visible
        if ($contract->post && $contract->post->user && $contract->post->user->identityVerifications && $contract->post->user->identityVerifications->isNotEmpty()) {
            $ownerIdentity = $contract->post->user->identityVerifications->first();
            $ownerIdentity->makeVisible([
                'full_name', 'document_number', 'date_of_birth', 'place_of_birth', 'nationality', 
                'issue_date', 'expiry_date', 'address', 'document_type'
            ]);
        }

        $renterUser = null;
        if ($contract->rentalRequest && $contract->rentalRequest->user) {
            $renterUser = $contract->rentalRequest->user;
        } elseif ($contract->user) {
            $renterUser = $contract->user;
        }

        if ($renterUser && $renterUser->identityVerifications && $renterUser->identityVerifications->isNotEmpty()) {
            $renterIdentity = $renterUser->identityVerifications->first();
            $renterIdentity->makeVisible([
                'full_name', 'document_number', 'date_of_birth', 'place_of_birth', 'nationality', 
                'issue_date', 'expiry_date', 'address', 'document_type'
            ]);
        }

        // Prepare data for PDF
        $ownerIdentity = $contract->post->user->identityVerifications->first() ?? null;
        $renterIdentity = $renterUser ? ($renterUser->identityVerifications->first() ?? null) : null;

        $pdf = Pdf::loadView('contracts.pdf', [
            'contract' => $contract,
            'ownerIdentity' => $ownerIdentity,
            'renterIdentity' => $renterIdentity,
        ]);

        $fileName = 'contract_' . $contract->id . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }
}
