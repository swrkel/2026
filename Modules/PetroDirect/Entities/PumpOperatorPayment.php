<?php

namespace Modules\PetroDirect\Entities;

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
        return $this->belongsTo('Modules\PetroDirect\Entities\PumpOperator', 'pump_operators_id');
    }

    public static function getPaymentTypesArray(){
        return [
            'cash' => __('petrodirect::lang.cash'),
            'cheque' => __('petrodirect::lang.cheque'),
            'card' => __('petrodirect::lang.card'),
            'credit' => __('petrodirect::lang.credit'),
            'multiple_credit' => __('petrodirect::lang.multiple_credit'),
            'shortage' => __('petrodirect::lang.shortage'),
            'excess' => __('petrodirect::lang.excess'),
        ];
    }
}
