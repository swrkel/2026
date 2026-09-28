<?php
namespace Modules\Ran\Entities;
class SupplierPayment extends RanModel {
    protected $table = 'ran_supplier_payments';
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:4'];
    public function purchase(){return $this->belongsTo(Purchase::class);}
    public function supplier(){return $this->belongsTo(\Modules\Suppliers\Entities\Supplier::class,'supplier_contact_id');}
}
