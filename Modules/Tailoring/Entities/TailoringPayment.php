<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringPayment extends Model
{
    protected $fillable = ['business_id','tailoring_order_id','payment_date','payment_method','reference_no','amount','note','created_by'];
    protected $casts = ['payment_date'=>'date'];
}
