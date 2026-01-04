<?php

namespace App\Services;

use Carbon\Carbon;

class RentalDurationService
{
    /**
     * Calculate end date based on duration type and multiplier
     * 
     * @param Carbon|string $startDate
     * @param string $durationType (day, week, month, test_10s, test_30s)
     * @param int $multiplier
     * @return Carbon
     */
    public static function calculateEndDate($startDate, string $durationType, int $multiplier): Carbon
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        
        return match($durationType) {
            'day' => $start->copy()->addDays($multiplier),
            'week' => $start->copy()->addWeeks($multiplier),
            'month' => $start->copy()->addMonths($multiplier),
            'test_10s' => $start->copy()->addSeconds(10 * $multiplier),
            'test_30s' => $start->copy()->addSeconds(30 * $multiplier),
            default => $start->copy()->addMonths($multiplier), // Default to months
        };
    }

    /**
     * Get the next available start date for an apartment
     * This returns the end date of the latest active/scheduled rental + 1 day
     * 
     * @param int $postId
     * @return Carbon
     */
    public static function getNextAvailableStartDate(int $postId): Carbon
    {
        $today = Carbon::today();
        
        // Find the latest end date from active or scheduled contracts
        $latestContract = \App\Models\Contract::where('post_id', $postId)
            ->whereIn('status', ['active', 'signed', 'pending_signing', 'pending'])
            ->where('end_date', '>=', $today)
            ->orderBy('end_date', 'desc')
            ->first();
        
        if ($latestContract && $latestContract->end_date) {
            // Start date is the day after the latest contract ends
            return Carbon::parse($latestContract->end_date)->addDay();
        }
        
        // No active rentals, can start today
        return $today;
    }

    /**
     * Check if a rental period would overlap with existing active/scheduled rentals
     * 
     * @param int $postId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @param int|null $excludeContractId Optional contract ID to exclude from check
     * @return bool
     */
    public static function hasOverlap(int $postId, $startDate, $endDate, ?int $excludeContractId = null): bool
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        
        $query = \App\Models\Contract::where('post_id', $postId)
            ->whereIn('status', ['active', 'signed', 'pending_signing', 'pending'])
            ->where(function($q) use ($start, $end) {
                // Check for overlap: (start <= existing_end) AND (end >= existing_start)
                $q->where(function($subQ) use ($start, $end) {
                    $subQ->where('start_date', '<=', $end->format('Y-m-d'))
                         ->where('end_date', '>=', $start->format('Y-m-d'));
                });
            });
        
        if ($excludeContractId) {
            $query->where('id', '!=', $excludeContractId);
        }
        
        return $query->exists();
    }
}

