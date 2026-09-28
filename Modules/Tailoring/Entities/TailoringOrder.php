<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringOrder extends Model
{
    protected $table = 'tailoring_orders';
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(TailoringCustomer::class, 'tailoring_customer_id');
    }
}
