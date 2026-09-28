<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class AiReplenishmentReview extends Model
{
    protected $table = 'stn_ai_replenishment_reviews';

    protected $fillable = [
        'business_id','business_location_id','store_id','product_id','product_sku','product_name',
        'source_business_location_id','source_store_id','suggested_qty','approved_qty','confidence_score',
        'reason','risk_level','status','reviewed_by','reviewed_at','created_by','remarks'
    ];

    protected $casts = [
        'suggested_qty' => 'decimal:4',
        'approved_qty' => 'decimal:4',
        'confidence_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];
}
