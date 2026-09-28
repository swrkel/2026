<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewSettingOption extends Model
{
    use SoftDeletes;

    protected $table = 'mn_setting_options';
    protected $guarded = ['id'];
    protected $casts = [
        'business_id' => 'integer',
        'amount' => 'decimal:4',
        'created_by' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
