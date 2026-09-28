<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceLabourItem extends Model { use SoftDeletes; protected $table='auto_service_labour_items'; protected $guarded=[]; }
