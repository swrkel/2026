<?php
namespace Modules\TeaEstateManagement\Entities;
class PurchasePayment extends BaseTeaModel
{
    protected $table = 'tea_purchase_payments';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
