<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
class AutoServicePackageLine extends Model
{
    protected $table='auto_service_package_lines';
    protected $guarded=[];
    protected $casts=['is_optional'=>'boolean','is_stock_item'=>'boolean'];
}
