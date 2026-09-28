<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlement extends PdnewBaseModel
{
    protected $table = 'pdnew_settlements';
    protected $casts = [
        'settlement_date' => 'date',
        'source_closed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'finalized_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meter_sales_total' => 'decimal:4',
        'other_sales_total' => 'decimal:4',
        'source_payments_total' => 'decimal:4',
        'manual_payments_total' => 'decimal:4',
        'source_declared_total' => 'decimal:4',
        'source_shortage_total' => 'decimal:4',
        'source_excess_total' => 'decimal:4',
        'manual_shortage_total' => 'decimal:4',
        'manual_excess_total' => 'decimal:4',
        'shortage_recovery_total' => 'decimal:4',
        'excess_commission_total' => 'decimal:4',
        'adjustments_total' => 'decimal:4',
        'expected_adjustments_total' => 'decimal:4',
        'received_adjustments_total' => 'decimal:4',
        'expected_total' => 'decimal:4',
        'received_total' => 'decimal:4',
        'operational_variance_amount' => 'decimal:4',
        'variance_amount' => 'decimal:4',
    ];

    public function sourceImport()
    {
        return $this->belongsTo(PdnewSourceImport::class, 'source_import_id');
    }

    public function sources()
    {
        return $this->hasMany(PdnewSettlementSource::class, 'settlement_id');
    }

    public function pumps()
    {
        return $this->hasMany(PdnewSettlementPump::class, 'settlement_id');
    }

    public function meterSales()
    {
        return $this->hasMany(PdnewSettlementMeterSale::class, 'settlement_id');
    }

    public function payments()
    {
        return $this->hasMany(PdnewSettlementPayment::class, 'settlement_id');
    }

    public function creditSales()
    {
        return $this->hasMany(PdnewSettlementCreditSale::class, 'settlement_id');
    }

    public function otherSales()
    {
        return $this->hasMany(PdnewSettlementOtherSale::class, 'settlement_id');
    }

    public function unloadStocks()
    {
        return $this->hasMany(PdnewSettlementUnloadStock::class, 'settlement_id');
    }

    public function dayEntries()
    {
        return $this->hasMany(PdnewSettlementDayEntry::class, 'settlement_id');
    }

    public function collections()
    {
        return $this->hasMany(PdnewSettlementCollection::class, 'settlement_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(PdnewSettlementLedgerEntry::class, 'settlement_id');
    }


    public function recoveries()
    {
        return $this->hasMany(PdnewSettlementRecovery::class, 'settlement_id');
    }

    public function commissions()
    {
        return $this->hasMany(PdnewSettlementCommission::class, 'settlement_id');
    }

    public function adjustments()
    {
        return $this->hasMany(PdnewSettlementAdjustment::class, 'settlement_id');
    }

    public function approvals()
    {
        return $this->hasMany(PdnewSettlementApproval::class, 'settlement_id');
    }

    public function history()
    {
        return $this->hasMany(PdnewSettlementStatusHistory::class, 'settlement_id');
    }

    public function issues()
    {
        return $this->hasMany(PdnewReconciliationIssue::class, 'settlement_id');
    }
    public function documents()
    {
        return $this->hasMany(PdnewDocument::class, 'settlement_id');
    }

    public function printLogs()
    {
        return $this->hasMany(PdnewPrintLog::class, 'document_id')
            ->where('document_type', 'settlement');
    }
}
