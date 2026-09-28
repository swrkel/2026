<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewWarehouseTransferLine extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_warehouse_transfer_lines';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'transfer_id',
    'product_id',
    'variation_id',
    'qty',
    'unit_cost',
    'line_total',
    'remarks'
    ];
}
