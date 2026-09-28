<?php
namespace Modules\TeaEstateManagement\Entities;
class LeafPurchase extends BaseTeaModel
{
    protected $table = 'tea_leaf_purchases';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
