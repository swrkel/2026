<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentRecommendationVersion extends Model
{
    protected $table = 'stn_replenishment_recommendation_versions';

    protected $fillable = [
        'business_id','location_id','store_id','product_id','recommendation_source',
        'version_no','recommended_qty','approved_qty','confidence_score','risk_level',
        'status','version_reason','created_by','approved_by','approved_at','converted_transfer_id',
        'converted_at','locked_at','meta_json'
    ];

    protected $casts = [
        'recommended_qty' => 'decimal:4',
        'approved_qty' => 'decimal:4',
        'confidence_score' => 'decimal:2',
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
        'locked_at' => 'datetime',
        'meta_json' => 'array',
    ];
}
