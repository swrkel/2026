<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $table = 'reo_receipts';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'scope_key', 'receipt_date',
        'receipt_no', 'source_id', 'source_name', 'membership_no',
        'membership_is_manual', 'total_amount', 'amount_in_words',
        'entered_by', 'entered_by_name',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'membership_is_manual' => 'boolean',
        'total_amount' => 'decimal:8',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReceiptDetail::class, 'receipt_id')->orderBy('sort_order')->orderBy('id');
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(ReceiptCheque::class, 'receipt_id')->orderBy('cheque_date')->orderBy('id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ReceiptAudit::class, 'receipt_id')->orderByDesc('edited_at')->orderByDesc('id');
    }
}
