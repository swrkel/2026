<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewAvailabilitySnapshot extends Model
{
    protected $table = 'products_new_availability_snapshots';
    protected $guarded = ['id'];
    protected $casts = ['snapshot_at'=>'datetime'];
}
