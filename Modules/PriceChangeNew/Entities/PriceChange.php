<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceChange extends Model
{
    use SoftDeletes;

    protected $table = 'pcn_price_changes';
    protected $guarded = ['id'];
    protected $casts = [
        'effective_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PriceChangeLine::class, 'price_change_id')->orderBy('line_no');
    }

    public function scopes()
    {
        return $this->hasMany(PriceChangeScope::class, 'price_change_id');
    }

    public function scopePrices()
    {
        return $this->hasMany(PriceChangeScopePrice::class, 'price_change_id');
    }

    public function audits()
    {
        return $this->hasMany(PriceChangeAudit::class, 'price_change_id')->orderByDesc('id');
    }

    public function applications()
    {
        return $this->hasMany(PriceChangeApplication::class, 'price_change_id')->orderByDesc('id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where($this->getTable() . '.business_id', $businessId);
    }
}
