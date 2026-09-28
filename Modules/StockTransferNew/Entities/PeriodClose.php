<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PeriodClose extends Model
{
    protected $table = 'stn_period_closes';

    protected $fillable = [
        'business_id','location_id','store_id','period_year','period_month','status',
        'total_transfers','pending_transfers','in_transit_transfers','variance_transfers',
        'locked_by','locked_at','reopened_by','reopened_at','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PeriodCloseLine::class, 'period_close_id');
    }
}
