<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PDSettlement extends Model
{
    protected $table = 'settlements';
    protected $guarded = ['id'];

    protected $casts = [
        'work_shift' => 'array'
    ];

    private function getCurrentBusinessId()
    {
        $business_id = $this->business_id;
        if (empty($business_id) && request()->hasSession()) {
            $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        }
        if (empty($business_id) && auth()->check()) {
            $business_id = auth()->user()->business_id;
        }
        return $business_id;
    }

    public function meter_sales() { return $this->hasMany(PDMeterSale::class, 'settlement_no', 'id'); }
    public function other_sales() { return $this->hasMany('Modules\\Petro\\Entities\\OtherSale', 'settlement_no', 'id'); }
    public function other_incomes() { return $this->hasMany('Modules\\Petro\\Entities\\OtherIncome', 'settlement_no', 'id'); }
    public function customer_payments() { return $this->hasMany('Modules\\Petro\\Entities\\CustomerPayment', 'settlement_no', 'id'); }
    public function cash_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementCashPayment', 'settlement_no', 'id'); }
    public function cash_deposits() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementCashDeposit', 'settlement_no', 'id'); }
    public function card_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementCardPayment', 'settlement_no', 'id'); }
    public function cheque_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementChequePayment', 'settlement_no', 'id'); }
    public function expense_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementExpensePayment', 'settlement_no', 'id'); }
    public function excess_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementExcessPayment', 'settlement_no', 'id'); }
    public function shortage_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementShortagePayment', 'settlement_no', 'id'); }
    public function loan_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementLoanPayment', 'settlement_no', 'id'); }
    public function drawings_payments() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementDrawingPayment', 'settlement_no', 'id'); }
    public function customer_loans() { return $this->hasMany('Modules\\Petro\\Entities\\SettlementCustomerLoan', 'settlement_no', 'id'); }

    public function credit_sale_payments()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany('Modules\\Petro\\Entities\\SettlementCreditSalePayment', 'settlement_no', 'settlement_no')->with('product');
        if (!empty($business_id)) {
            $relation->where('settlement_credit_sale_payments.business_id', $business_id);
        }
        return $relation;
    }

    public function pos_payments()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany('Modules\\Petro\\Entities\\SettlementPosPayment', 'settlement_no', 'settlement_no');
        if (!empty($business_id)) {
            $relation->where('settlement_pos_payments.business_id', $business_id);
        }
        return $relation;
    }
}
