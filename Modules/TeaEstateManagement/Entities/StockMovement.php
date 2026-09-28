<?php
namespace Modules\TeaEstateManagement\Entities;
class StockMovement extends BaseTeaModel
{
    protected $table = 'tea_stock_movements';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
