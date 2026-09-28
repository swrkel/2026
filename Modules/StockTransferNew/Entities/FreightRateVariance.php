<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class FreightRateVariance extends Model
{
    protected $table = 'stn_freight_rate_variances';

    protected $fillable = [
        'business_id', 'transfer_id', 'freight_invoice_id', 'rate_card_id',
        'expected_amount', 'actual_amount', 'variance_amount', 'variance_percent',
        'status', 'review_note', 'reviewed_by', 'reviewed_at', 'created_by'
    ];

    protected $casts = [
        'expected_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'variance_amount' => 'decimal:4',
        'variance_percent' => 'decimal:4',
        'reviewed_at' => 'datetime',
    ];
}
