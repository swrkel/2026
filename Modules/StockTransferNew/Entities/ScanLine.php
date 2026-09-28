<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ScanLine extends Model
{
    protected $table = 'stock_transfer_new_scan_lines';
    protected $guarded = ['id'];
}
