<?php
namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionProduct as Product;
use Illuminate\Database\Eloquent\Model;

class DistributionInvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_id',
        'qty',
        'unit_price',
        'amount',
        'discount',
        'final_amount',
        'is_free',
        'is_free_bottles',
        'is_free_auto'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
