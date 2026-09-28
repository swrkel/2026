<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewExpiryAlert extends Model
{
    protected $table = 'products_new_expiry_alerts';
    protected $guarded = ['id'];
    protected $casts = ['alert_date'=>'date','expiry_at'=>'date','is_resolved'=>'boolean','resolved_at'=>'datetime'];
}
