<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettlementCreditSalePayment extends Model
{
    protected $table="settlement_credit_sale_payments";
	
	 protected $fillable = [
		'settlement_no', 'business_id', 'customer_id', 'product_id',
		'order_number', 'order_date', 'customer_reference', 'transaction_id'
    ];
    
	
	public function settlement()
    {
        return $this->belongsTo(\App\Settlement::class, 'settlement_no');
        
    }
	
	public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }
}
