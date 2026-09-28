<?php
namespace Modules\ProductsNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductsNewBarcodeQueue extends Model
{
    protected $table = 'products_new_barcode_queue';
    protected $guarded = ['id'];
}
