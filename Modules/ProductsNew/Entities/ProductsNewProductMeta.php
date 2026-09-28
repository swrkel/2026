<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewProductMeta extends Model { protected $table='products_new_product_meta'; protected $guarded=['id']; protected $casts=['gallery'=>'array','attachments'=>'array','health_payload'=>'array','settings'=>'array']; }
