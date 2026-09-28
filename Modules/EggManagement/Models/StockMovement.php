<?php
namespace Modules\EggManagement\Models;

class StockMovement extends EggModel
{
    protected $table = 'egg_stock_movements';
    protected $casts = ['movement_date'=>'datetime'];
}
