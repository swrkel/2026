<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewTimeline extends Model { protected $table='products_new_timeline'; protected $guarded=['id']; protected $casts=['payload'=>'array']; }
