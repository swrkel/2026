<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewSplitBill extends Model
{
    protected $table = 'rn_split_bills';
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(RestaurantNewSaleOrder::class, 'order_id');
    }

    public function lines()
    {
        return $this->hasMany(RestaurantNewSplitBillLine::class, 'split_bill_id');
    }
}
