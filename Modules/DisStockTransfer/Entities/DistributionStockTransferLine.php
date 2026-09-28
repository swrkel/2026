<?php

namespace Modules\DisStockTransfer\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionStockTransferLine extends Model
{
    protected $table = 'distribution_stock_transfer_lines';

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'variation_id',
        'qty',
        'unit_sale_price',
        'subtotal',
    ];

    /**
     * Relations
     */

    public function transfer()
    {
        return $this->belongsTo(DistributionStockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }
}
