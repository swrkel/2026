<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class MinStockRule extends Model
{
    protected $table = 'stn_min_stock_rules';
    protected $guarded = ['id'];
}
