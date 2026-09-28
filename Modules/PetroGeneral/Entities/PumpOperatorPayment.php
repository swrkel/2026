<?php

namespace Modules\PetroGeneral\Entities;

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
        return $this->belongsTo('Modules\PetroGeneral\Entities\PumpOperator', 'pump_operators_id');
    }

    public static function getPaymentTypesArray(){
        return [
            'cash' => __('petrogeneral::lang.cash'),
            'cheque' => __('petrogeneral::lang.cheque'),
            'card' => __('petrogeneral::lang.card'),
            'credit' => __('petrogeneral::lang.credit'),
            'multiple_credit' => __('petrogeneral::lang.multiple_credit'),
            'shortage' => __('petrogeneral::lang.shortage'),
            'excess' => __('petrogeneral::lang.excess'),
        ];
    }
}
