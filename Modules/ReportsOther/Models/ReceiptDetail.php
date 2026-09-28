<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptDetail extends Model
{
    public $timestamps = false;
    protected $table = 'reo_receipt_details';

    protected $fillable = [
        'receipt_id', 'item_type', 'item_id', 'source_detail', 'amount', 'sort_order', 'created_at',
    ];

    protected $casts = ['amount' => 'decimal:8', 'created_at' => 'datetime'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
