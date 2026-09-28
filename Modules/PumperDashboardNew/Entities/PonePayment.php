<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PonePayment extends PoneBaseModel
{
    use SoftDeletes;

    protected $table = 'pone_payments';
    protected $casts = [
        'gross_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'cheque_date' => 'date',
        'transaction_at' => 'datetime',
        'edited_at' => 'datetime',
        'voided_at' => 'datetime',
        'last_printed_at' => 'datetime',
    ];

    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function creditSale() { return $this->hasOne(PoneCreditSale::class, 'payment_id'); }
    public function cashDenominations() { return $this->hasMany(PonePaymentCashDenomination::class, 'payment_id')->orderByDesc('denomination'); }
    public function cardLines() { return $this->hasMany(PonePaymentCardLine::class, 'payment_id'); }
    public function editHistories() { return $this->hasMany(PonePaymentEditHistory::class, 'payment_id')->orderByDesc('version_no'); }
    public function parentPayment() { return $this->belongsTo(self::class, 'parent_payment_id'); }
}
