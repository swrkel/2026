<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewBatchMovement extends Model
{
    protected $table = 'products_new_batch_movements';
    protected $guarded = ['id'];
    protected $casts = ['transaction_date'=>'datetime','qty_in'=>'decimal:4','qty_out'=>'decimal:4','balance_after'=>'decimal:4'];
}
