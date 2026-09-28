<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneShift extends PoneBaseModel
{
    protected $table = 'pone_shifts';
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'closed_statement_printed_at' => 'datetime',
        'meter_sales_total' => 'decimal:4',
        'other_sales_total' => 'decimal:4',
        'payments_total' => 'decimal:4',
        'expected_total' => 'decimal:4',
        'declared_total' => 'decimal:4',
        'shortage_amount' => 'decimal:4',
        'excess_amount' => 'decimal:4',
    ];

    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
    public function assignments() { return $this->hasMany(PonePumpAssignment::class, 'shift_id'); }
    public function readings() { return $this->hasMany(PoneMeterReading::class, 'shift_id'); }
    public function payments() { return $this->hasMany(PonePayment::class, 'shift_id'); }
    public function otherSales() { return $this->hasMany(PoneOtherSale::class, 'shift_id'); }
    public function unloadStocks() { return $this->hasMany(PoneUnloadStock::class, 'shift_id'); }
    public function dayEntries() { return $this->hasMany(PoneDayEntry::class, 'shift_id'); }
    public function collections() { return $this->hasMany(PoneDailyCollection::class, 'shift_id'); }
    public function settlementReferences() { return $this->hasMany(PoneShiftSettlementReference::class, 'shift_id'); }
    public function shortageRecoveries() { return $this->hasMany(PoneShortageRecovery::class, 'shift_id'); }
    public function excessCommissions() { return $this->hasMany(PoneExcessCommission::class, 'shift_id'); }
    public function ledgerEntries() { return $this->hasMany(PoneOperatorLedgerEntry::class, 'shift_id'); }

    public function isOpen(): bool { return in_array($this->status, ['open', 'closing'], true); }
}
