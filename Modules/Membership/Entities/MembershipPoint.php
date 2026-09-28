<?php

namespace Modules\Membership\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Services\MembershipPointBalanceCalculator;

class MembershipPoint extends Model
{
    protected $table = 'membership_points';

    protected $fillable = [
        'form_number',
        'date',
        'member_id',
        'business_type_id',
        'business_id',
        'bill_number',
        'amount',
        'earned_points',
        'redeemed_points',
        'point_balance',
        'payment_details',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'business_name',
        'created_by',
    ];

    /**
     * Get point balance excluding this record
     *
     * @return float
     */
    public function getBalanceExcludingThis(): float
    {
        $calculator = new MembershipPointBalanceCalculator();
        return $calculator->calculateCurrentBalance(
            $this->member_id,
            $this->business_id,
            $this->id
        );
    }

    /**
     * Scope to exclude specific point record
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $pointId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcluding($query, $pointId)
    {
        return $query->where('id', '!=', $pointId);
    }

    /**
     * Scope for member's activities in chronological order
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $memberId
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMemberChronological($query, $memberId, $businessId)
    {
        return $query->where('member_id', $memberId)
                    ->where('business_id', $businessId)
                    ->orderBy('date', 'asc')
                    ->orderBy('form_number', 'asc');
    }
}
