<?php
namespace Modules\TeaEstateManagement\Entities;
class SaleReceipt extends BaseTeaModel
{
    protected $table = 'tea_sale_receipts';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
