<?php

namespace Modules\Membership\Entities;

use App\User;
use App\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPointSetting extends Model
{
    protected $fillable = [
        'business_id',
        'membership_business_type_id',
        'reward_point_percent',
        'min_bill_total_to_earn',
        'max_points_per_bill',
        'min_bill_total_to_redeem',
        'min_redeem_point',
        'max_redeem_point_per_bill',
        'expiry_period_months',
        'expiry_period_years',
        'created_by'
    ];

    protected $casts = [
        'reward_point_percent' => 'decimal:2',
        'min_bill_total_to_earn' => 'decimal:2',
        'min_bill_total_to_redeem' => 'decimal:2',
        'max_points_per_bill' => 'integer',
        'min_redeem_point' => 'integer',
        'max_redeem_point_per_bill' => 'integer',
        'expiry_period_months' => 'integer',
        'expiry_period_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(MembershipBusinessType::class, 'membership_business_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
