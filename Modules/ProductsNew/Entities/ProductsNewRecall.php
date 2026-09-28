<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewRecall extends Model
{
    protected $table = 'products_new_recalls';
    protected $guarded = ['id'];
    protected $casts = ['started_at'=>'datetime','closed_at'=>'datetime'];
}
