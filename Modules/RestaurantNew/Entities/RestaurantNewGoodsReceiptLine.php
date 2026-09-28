<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewGoodsReceiptLine extends Model
{
    protected $table = 'restaurant_new_goods_receipt_lines';
    protected $guarded = ['id'];
}
