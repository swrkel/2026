<?php
namespace Modules\Ran\Entities;
class Purchase extends RanModel {
    protected $table = 'ran_purchases';
    protected $casts = ['purchase_date'=>'date','subtotal'=>'decimal:4','discount_amount'=>'decimal:4','tax_amount'=>'decimal:4','total_amount'=>'decimal:4','paid_amount'=>'decimal:4','balance_amount'=>'decimal:4','posted_at'=>'datetime'];
    public function lines(){return $this->hasMany(PurchaseLine::class);}
    public function payments(){return $this->hasMany(SupplierPayment::class);}
    public function supplier(){return $this->belongsTo(\Modules\Suppliers\Entities\Supplier::class,'supplier_contact_id');}
}
