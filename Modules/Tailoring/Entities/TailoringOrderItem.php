<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringOrderItem extends Model
{
    protected $fillable = ['business_id','tailoring_order_id','tailoring_garment_id','garment_name','qty','unit_price','discount_amount','tax_amount','total_amount','measurements','style_details','special_instructions'];
    protected $casts = ['measurements'=>'array','style_details'=>'array'];
}
