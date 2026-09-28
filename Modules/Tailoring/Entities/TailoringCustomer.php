<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringCustomer extends Model
{
    protected $table = 'tailoring_customers';
    protected $guarded = ['id'];

    public function measurements()
    {
        return $this->hasMany(TailoringMeasurement::class, 'tailoring_customer_id');
    }
}
