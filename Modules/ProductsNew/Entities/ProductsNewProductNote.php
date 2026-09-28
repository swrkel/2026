<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewProductNote extends Model
{
    protected $table = 'products_new_product_notes';
    protected $guarded = ['id'];
    protected $casts = ['is_pinned'=>'boolean','metadata'=>'array'];
}
