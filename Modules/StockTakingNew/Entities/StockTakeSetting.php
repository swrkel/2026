<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeSetting extends Model
{
    protected $table='stk_settings'; protected $guarded=[]; protected $casts=['value_json'=>'array','is_encrypted'=>'boolean'];
}
