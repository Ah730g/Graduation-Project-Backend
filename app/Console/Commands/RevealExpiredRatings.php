<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Models\Contract;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RevealExpiredRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratings:reveal-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reveal ratings that have passed the 14-day window after stay completion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        // Find all hidden reviews
        $hiddenReviews = Review::where('status', 'hidden')
            ->with('contract')
            ->get();
        
        $revealedCount = 0;
        
        foreach ($hiddenReviews as $review) {
            if (!$review->contract) {
                continue;
            }
            
            $contract = $review->contract;
            $stayEndDate = Carbon::parse($contract->end_date);
            $daysSinceStayEnd = $now->diffInDays($stayEndDate);
            $hasBeen14Days = $daysSinceStayEnd >= 14;
            
            // Get all hidden reviews for this contract
            $contractHiddenReviews = Review::where('contract_id', $contract->id)
                ->where('status', 'hidden')
                ->get();
            
            $reviewsCount = $contractHiddenReviews->count();
            
            // Case A: Both parties have submitted - reveal both
            if ($reviewsCount === 2) {
                Review::where('contract_id', $contract->id)
                    ->where('status', 'hidden')
                    ->update([
                        'status' => 'revealed',
                        'revealed_at' => $now,
                    ]);
                
                $revealedCount += $reviewsCount;
                continue;
            }
            
            // Case B: 14 days have passed since stay ended - reveal any submitted ratings
            if ($hasBeen14Days && $reviewsCount > 0) {
                Review::where('contract_id', $contract->id)
                    ->where('status', 'hidden')
                    ->update([
                        'status' => 'revealed',
                        'revealed_at' => $now,
                    ]);
                
                $revealedCount += $reviewsCount;
            }
        }
        
        $this->info("Revealed {$revealedCount} rating(s) that passed the 14-day window.");
        
        return 0;
    }
}
