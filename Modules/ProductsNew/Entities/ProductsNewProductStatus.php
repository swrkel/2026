<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewProductStatus extends Model
{
    protected $table = 'products_new_product_statuses';
    protected $guarded = ['id'];
    protected $casts = ['is_default'=>'boolean','is_active'=>'boolean','allowed_next_statuses'=>'array'];
}
