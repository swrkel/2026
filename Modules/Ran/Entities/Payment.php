<?php
namespace Modules\Ran\Entities;
class Payment extends RanModel {
    protected $table = 'ran_payments';
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:4'];
    public function sale(){return $this->belongsTo(Sale::class);}
    public function customer(){return $this->belongsTo(\Modules\Customers\Entities\Customer::class,'customer_contact_id');}
}
