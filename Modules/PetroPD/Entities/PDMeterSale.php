<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PDMeterSale extends Model
{
    protected $table = 'meter_sales';
    protected $guarded = ['id'];

    public function settlements()
    {
        return $this->belongsTo(PDSettlement::class, 'settlement_no', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }

    public function pump()
    {
        return $this->belongsTo(PDPump::class, 'pump_id', 'id');
    }
}
