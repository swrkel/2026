<?php
namespace Modules\RiceMill\Models;

class CustomerPaymentAllocation extends BaseRiceMillModel
{
    protected $table='rcm_customer_payment_allocations';
    protected $casts=['amount'=>'decimal:4'];

    public function payment(){return $this->belongsTo(CustomerPayment::class,'payment_id');}
    public function dispatch(){return $this->belongsTo(Dispatch::class,'dispatch_id');}
}
