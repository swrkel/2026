<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewMedia extends Model
{
    protected $table = 'products_new_media';
    protected $guarded = ['id'];
    protected $casts = ['is_primary'=>'boolean','metadata'=>'array'];
}
