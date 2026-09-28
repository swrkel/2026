<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewBarcodeTemplate extends Model
{
    protected $table = 'products_new_barcode_templates';
    protected $guarded = ['id'];
    protected $casts = ['settings'=>'array','is_default'=>'boolean','is_active'=>'boolean'];
}
