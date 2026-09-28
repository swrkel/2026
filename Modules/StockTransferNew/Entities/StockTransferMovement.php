<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferMovement extends Model
{
    protected $table = 'stnew_stock_movements';
    protected $guarded = ['id'];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }
}
