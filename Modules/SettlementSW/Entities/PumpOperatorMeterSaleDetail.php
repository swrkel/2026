<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;

class PumpOperatorMeterSaleDetail extends Model
{
     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function pump()
    {
        return $this->belongsTo(Pump::class, 'pump_id');
    }

}
