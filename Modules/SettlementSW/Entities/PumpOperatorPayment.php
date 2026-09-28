<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;

class PumpOperatorPayment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pump_operator_payments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function pump_operator()
    {
        return $this->belongsTo('Modules\SettlementSW\Entities\PumpOperator', 'pump_operators_id');
    }

    public static function getPaymentTypesArray(){
        return [
            'cash' => __('settlementsw::lang.cash'),
            'cheque' => __('settlementsw::lang.cheque'),
            'card' => __('settlementsw::lang.card'),
            'credit' => __('settlementsw::lang.credit'),
            'multiple_credit' => __('settlementsw::lang.multiple_credit'),
            'shortage' => __('settlementsw::lang.shortage'),
            'excess' => __('settlementsw::lang.excess'),
        ];
    }
}
