<?php

namespace Modules\SalesAgent\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Standalone SalesAgentCommission model.
 *
 * Stores commission entries from the "Add Commission" popup.
 */
class SalesAgentCommission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales_agent_commissions';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'commission_date' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:4',
    ];

    /**
     * Get the sales agent that owns this commission.
     */
    public function salesAgent()
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    /**
     * Get the business for this commission.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the user who created this commission.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Scope a query to filter by business.
     */
    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }
}
