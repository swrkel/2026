<?php
namespace Modules\Ran\Entities;
class Sale extends RanModel {
    protected $table = 'ran_sales';
    protected $casts = ['invoice_date'=>'date','subtotal'=>'decimal:4','making_charge_total'=>'decimal:4','discount_amount'=>'decimal:4','tax_amount'=>'decimal:4','rounding_amount'=>'decimal:4','total_amount'=>'decimal:4','paid_amount'=>'decimal:4','balance_amount'=>'decimal:4','posted_at'=>'datetime','cancelled_at'=>'datetime'];
    public function lines(){return $this->hasMany(SaleLine::class);}
    public function payments(){return $this->hasMany(Payment::class);}
    public function returns(){return $this->hasMany(SaleReturn::class);}
    public function customer(){return $this->belongsTo(\Modules\Customers\Entities\Customer::class,'customer_contact_id');}
}
