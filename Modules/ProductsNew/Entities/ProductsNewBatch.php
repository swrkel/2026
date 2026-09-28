<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewBatch extends Model
{
    protected $table = 'products_new_batches';
    protected $guarded = ['id'];
    protected $casts = ['manufactured_at'=>'date','expiry_at'=>'date','is_active'=>'boolean'];
}
