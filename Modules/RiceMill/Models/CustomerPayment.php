<?php
namespace Modules\RiceMill\Models;

class CustomerPayment extends BaseRiceMillModel
{
    protected $table='rcm_customer_payments';
    protected $casts=[
        'payment_date'=>'datetime',
        'reversed_at'=>'datetime',
        'amount'=>'decimal:4',
        'allocated_amount'=>'decimal:4',
        'advance_amount'=>'decimal:4',
    ];

    public function allocations(){return $this->hasMany(CustomerPaymentAllocation::class,'payment_id');}
}
