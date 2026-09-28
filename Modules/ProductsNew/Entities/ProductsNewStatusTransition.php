<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewStatusTransition extends Model
{
    protected $table = 'products_new_status_transitions';
    protected $guarded = ['id'];
    protected $casts = ['metadata'=>'array'];
}
