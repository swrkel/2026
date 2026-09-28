<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewWarehouseStock extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_warehouse_stocks';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'warehouse_id',
    'product_id',
    'variation_id',
    'qty_available',
    'qty_reserved',
    'qty_loaded',
    'qty_returned',
    'last_movement_at'
    ];
}
