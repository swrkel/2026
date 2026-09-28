<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Settlement extends Model
{
    protected $fillable = [];


    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];


     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'work_shift' => 'array'
    ];

    /**
     * Get the meter_sales that belongs to the settlement.
     */
    public function meter_sales()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\MeterSale', 'settlement_no', 'id')
            ->petroDirectOwned();
    }

    public function meter_sales_pd()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\PumpOperatorMeterSale', 'settlement_no', 'settlement_no');
    }
    /**
     * Get the meter_sales that belongs to the settlement.
     */
    public function other_sales()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\OtherSale', 'settlement_no', 'id');
    }
    /**
     * Get the other_income that belongs to the settlement.
     */
    public function other_incomes()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\OtherIncome', 'settlement_no', 'id');
    }
    /**
     * Get the customer_payments that belongs to the settlement.
     */
    public function customer_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\CustomerPayment', 'settlement_no', 'id');
    }
    public function customer_loans()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementCustomerLoan', 'settlement_no', 'id');
    }
    /**
     * Get the card payments that belongs to the settlement.
     */
    public function card_payments()
    {
        return $this->hasMany(
            \Modules\PetroDirect\Entities\SettlementCardPayment::class,
            'settlement_no',
            'id'
        )->petroDirectOwned();
    }
    /**
     * Get the cash payments that belongs to the settlement.
     */
    public function cash_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementCashPayment', 'settlement_no', 'id');
    }

    private function getCurrentBusinessId()
    {
        $business_id = $this->business_id;
        if (empty($business_id) && request()->hasSession()) {
            $business_id = request()->session()->get('business.id');
        }
        if (empty($business_id) && auth()->user()) {
            $business_id = auth()->user()->business_id;
        }
        return $business_id;
    }

    public function loan_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementLoanPayment', 'settlement_no', 'id');
    }

    public function pos_payments()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany('\Modules\PetroDirect\Entities\SettlementPosPayment', 'settlement_no', 'settlement_no');

        if (! empty($business_id)) {
            $relation->where('settlement_pos_payments.business_id', $business_id);
        }

        return $relation;
    }

    public function drawings_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementDrawingPayment', 'settlement_no', 'id');
    }

     public function cash_deposits()
    {
        // Cash deposits: regular Settlement uses id (integer), Settlement SW uses settlement_no (string)
        // Note: For Settlement SW, cash deposits are stored with settlement_no as string (e.g., "SET-SW1")
        // The relationship matches by id, but fallback queries in controllers handle both formats
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementCashDeposit', 'settlement_no', 'id');
    }
    /**
     * Get the cheques payments that belongs to the settlement.
     */
    public function cheque_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementChequePayment', 'settlement_no', 'id');
    }
    /**
     * Get the credit sale payments that belongs to the settlement.
     */
    public function credit_sale_payments()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany(
            \Modules\PetroDirect\Entities\SettlementCreditSalePayment::class,
            'settlement_no',
            'settlement_no'
        )->with('product');

        if (! empty($business_id)) {
            $relation->where('settlement_credit_sale_payments.business_id', $business_id);
        }

        return $relation->petroDirectOwned();
    }

    /**
     * Get the excess payments that belongs to the settlement.
     */
    public function excess_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementExcessPayment', 'settlement_no', 'id');
    }
    /**
     * Get the expense payments that belongs to the settlement.
     */
    public function expense_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementExpensePayment', 'settlement_no', 'id');
    }
    /**
     * Get the shortage payments that belongs to the settlement.
     */
    public function shortage_payments()
    {
        return $this->hasMany('\Modules\PetroDirect\Entities\SettlementShortagePayment', 'settlement_no', 'id');
    }

    public function daily_collections()
    {
        return $this->hasMany(DailyCollection::class, 'settlement_id');
    }

    public function daily_cards()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany(DailyCard::class, 'settlement_no', 'settlement_no');

        if (! empty($business_id)) {
            $relation->where('daily_cards.business_id', $business_id);
        }

        return $relation;
    }

    public function daily_vouchers()
    {
        $business_id = $this->getCurrentBusinessId();
        $relation = $this->hasMany(DailyVoucher::class, 'settlement_no', 'settlement_no');

        if (! empty($business_id)) {
            $relation->where('daily_vouchers.business_id', $business_id);
        }

        return $relation;
    }

}
