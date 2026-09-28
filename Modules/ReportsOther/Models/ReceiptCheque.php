<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptCheque extends Model
{
    public $timestamps = false;
    protected $table = 'reo_receipt_cheques';

    protected $fillable = [
        'receipt_id', 'external_payment_id', 'cheque_number', 'bank_name', 'cheque_date', 'created_at',
    ];

    protected $casts = ['cheque_date' => 'date', 'created_at' => 'datetime'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
