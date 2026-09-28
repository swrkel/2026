<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Modules\Membership\Entities\MembershipPoint;

class MembershipPointBalanceCalculator
{
    /**
     * Calculate current point balance for a member, optionally excluding a specific point record
     *
     * @param int $memberId
     * @param int $businessId
     * @param int|null $excludePointId
     * @return float
     * @throws \InvalidArgumentException
     */
    public function calculateCurrentBalance(int $memberId, int $businessId, ?int $excludePointId = null): float
    {
        if ($memberId <= 0) {
            throw new \InvalidArgumentException('Member ID must be a positive integer');
        }
        
        if ($businessId <= 0) {
            throw new \InvalidArgumentException('Business ID must be a positive integer');
        }

        try {
            $activities = $this->getMemberPointActivities($memberId, $businessId, $excludePointId);
            
            $totalEarned = $activities->sum('earned_points');
            $totalRedeemed = $activities->sum('redeemed_points');
            
            return (float) ($totalEarned - $totalRedeemed);
        } catch (\Exception $e) {
            \Log::error('Error calculating point balance', [
                'member_id' => $memberId,
                'business_id' => $businessId,
                'exclude_point_id' => $excludePointId,
                'error' => $e->getMessage()
            ]);
            
            // Return 0 as safe fallback
            return 0.0;
        }
    }

    /**
     * Calculate balance at a specific date
     *
     * @param int $memberId
     * @param int $businessId
     * @param string $date
     * @param int|null $excludePointId
     * @return float
     * @throws \InvalidArgumentException
     */
    public function calculateBalanceAtDate(int $memberId, int $businessId, string $date, ?int $excludePointId = null): float
    {
        if ($memberId <= 0) {
            throw new \InvalidArgumentException('Member ID must be a positive integer');
        }
        
        if ($businessId <= 0) {
            throw new \InvalidArgumentException('Business ID must be a positive integer');
        }
        
        if (empty($date)) {
            throw new \InvalidArgumentException('Date cannot be empty');
        }

        try {
            $activities = $this->getMemberPointActivities($memberId, $businessId, $excludePointId)
                ->where('date', '<=', $date);
            
            $totalEarned = $activities->sum('earned_points');
            $totalRedeemed = $activities->sum('redeemed_points');
            
            return (float) ($totalEarned - $totalRedeemed);
        } catch (\Exception $e) {
            \Log::error('Error calculating point balance at date', [
                'member_id' => $memberId,
                'business_id' => $businessId,
                'date' => $date,
                'exclude_point_id' => $excludePointId,
                'error' => $e->getMessage()
            ]);
            
            // Return 0 as safe fallback
            return 0.0;
        }
    }

    /**
     * Get all point activities for a member in chronological order
     *
     * @param int $memberId
     * @param int $businessId
     * @param int|null $excludePointId
     * @return Collection
     */
    private function getMemberPointActivities(int $memberId, int $businessId, ?int $excludePointId = null): Collection
    {
        $query = MembershipPoint::where('member_id', $memberId)
            ->where('business_id', $businessId)
            ->orderBy('date', 'asc')
            ->orderBy('form_number', 'asc');

        if ($excludePointId !== null) {
            $query->where('id', '!=', $excludePointId);
        }

        return $query->get();
    }
}