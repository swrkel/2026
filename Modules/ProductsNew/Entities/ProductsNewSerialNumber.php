<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewSerialNumber extends Model
{
    protected $table = 'products_new_serial_numbers';
    protected $guarded = ['id'];
    protected $casts = ['purchase_date'=>'date','sold_date'=>'date','warranty_start_date'=>'date','warranty_end_date'=>'date','last_service_date'=>'date','next_service_date'=>'date','is_active'=>'boolean'];
}
