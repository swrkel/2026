<?php
namespace Modules\TeaEstateManagement\Entities;
class InventoryLot extends BaseTeaModel
{
    protected $table = 'tea_inventory_lots';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
