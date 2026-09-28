<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTransferTemplate extends Model { protected $table='stnew_transfer_templates'; protected $guarded=['id']; protected $casts=['lines_json'=>'array','is_active'=>'boolean']; }
