<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;

class PumpOperatorMeterSale extends Model
{
     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(
            PumpOperatorMeterSaleDetail::class,
            'sale_id'
        );
    }
}
