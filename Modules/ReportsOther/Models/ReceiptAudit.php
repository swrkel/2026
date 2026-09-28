<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptAudit extends Model
{
    public $timestamps = false;
    protected $table = 'reo_receipt_audits';

    protected $fillable = [
        'receipt_id', 'field_name', 'field_label', 'old_value', 'new_value',
        'edited_by', 'edited_by_name', 'edited_at',
    ];

    protected $casts = ['edited_at' => 'datetime'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
