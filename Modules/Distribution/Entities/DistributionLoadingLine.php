<?php
namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionProduct as Product;
use Illuminate\Database\Eloquent\Model;

class DistributionLoadingLine extends Model
{
    protected $table = 'distribution_loading_lines';

    protected $fillable = [
        'loading_id','product_id','unit_id','requested_qty','issued_qty','sale_price','line_total'
    ];

    public function loading()
    {
        return $this->belongsTo(DistributionLoading::class, 'loading_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Unit::class, 'unit_id');
    }
}
