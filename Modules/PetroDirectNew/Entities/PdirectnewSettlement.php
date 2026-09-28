<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewSettlement extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_settlements';
    protected $casts = ['transaction_date' => 'date', 'finalized_at' => 'datetime'];

    public function meterSales() { return $this->hasMany(PdirectnewSettlementMeterSale::class, 'settlement_id'); }
    public function payments() { return $this->hasMany(PdirectnewSettlementPayment::class, 'settlement_id'); }
    public function otherSales() { return $this->hasMany(PdirectnewSettlementOtherSale::class, 'settlement_id'); }
    public function otherIncome() { return $this->hasMany(PdirectnewSettlementOtherIncome::class, 'settlement_id'); }
    public function customerPayments() { return $this->hasMany(PdirectnewSettlementCustomerPayment::class, 'settlement_id'); }
    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function shift() { return $this->belongsTo(PdirectnewShift::class, 'shift_id'); }
}
