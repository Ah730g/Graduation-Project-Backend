<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ExpireRentals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rentals:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire rentals that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        // Find all active contracts that have passed their end date
        // Include all statuses that indicate an active rental (excluding draft, expired, and cancelled)
        $expiredContracts = Contract::with('post')
            ->whereIn('status', ['active', 'signed', 'pending_signing', 'pending'])
            ->where('end_date', '<', $today)
            ->get();
        
        $expiredCount = 0;
        $apartmentsMadeAvailable = 0;
        
        foreach ($expiredContracts as $contract) {
            // Update contract status to expired
            $contract->update(['status' => 'expired']);
            
            // Make the apartment available again if it was marked as rented
            if ($contract->post) {
                if ($contract->post->status === 'rented') {
                    $contract->post->update(['status' => 'active']);
                    $apartmentsMadeAvailable++;
                }
                
                // Also check if there are any related rental requests that should be updated
                // If the contract is expired, related rental requests should also be marked as expired/cancelled
                if ($contract->rental_request_id) {
                    $rentalRequest = \App\Models\RentalRequest::find($contract->rental_request_id);
                    if ($rentalRequest && in_array($rentalRequest->status, [
                        'approved', 
                        'awaiting_payment', 
                        'payment_received', 
                        'payment_confirmed', 
                        'contract_signing',
                        'contract_signed'
                    ])) {
                        // Don't automatically cancel - let admin handle if needed
                        // The apartment availability is what matters
                    }
                }
            }
            
            $expiredCount++;
        }
        
        $this->info("Expired {$expiredCount} rental contract(s).");
        $this->info("Made {$apartmentsMadeAvailable} apartment(s) available again.");
        
        return 0;
    }
}
