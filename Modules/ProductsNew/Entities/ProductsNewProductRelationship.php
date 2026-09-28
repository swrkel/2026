<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewProductRelationship extends Model
{
    protected $table = 'products_new_product_relationships';
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean','metadata'=>'array'];
}
