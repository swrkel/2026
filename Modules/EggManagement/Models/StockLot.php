<?php
namespace Modules\EggManagement\Models;

class StockLot extends EggModel
{
    protected $table = 'egg_stock_lots';
    protected $casts = ['collection_date'=>'date','best_before'=>'date'];
}
