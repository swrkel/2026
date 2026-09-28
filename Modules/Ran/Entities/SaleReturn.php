<?php
namespace Modules\Ran\Entities;
class SaleReturn extends RanModel {
    protected $table = 'ran_sale_returns';
    protected $casts = ['return_date'=>'date','total_amount'=>'decimal:4','posted_at'=>'datetime'];
    public function lines(){return $this->hasMany(SaleReturnLine::class);}
    public function sale(){return $this->belongsTo(Sale::class);}
    public function customer(){return $this->belongsTo(\Modules\Customers\Entities\Customer::class,'customer_contact_id');}
}
