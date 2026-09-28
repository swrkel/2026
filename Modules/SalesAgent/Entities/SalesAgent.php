<?php

namespace Modules\SalesAgent\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Standalone SalesAgent model.
 *
 * This model belongs to the SalesAgent module and uses the existing
 * distribution_sales_agents table.
 */
class SalesAgent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'distribution_sales_agents';

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
        'joined_date' => 'date',
        'salary' => 'decimal:4',
        'commission' => 'decimal:4',
        'commission_history' => 'array',
    ];

    /**
     * Get the business that owns the sales agent.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the location for the sales agent.
     */
    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Get the user linked to this sales agent.
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    /**
     * Get the user who created this sales agent.
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

    /**
     * Scope a query to filter by location.
     */
    public function scopeForLocation($query, $location_id)
    {
        return $query->where('location_id', $location_id);
    }
}
