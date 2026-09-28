<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LeadsNewWorkflow extends Model { use SoftDeletes; protected $table='leads_new_workflows'; protected $guarded=['id']; protected $casts=['conditions'=>'array','actions'=>'array','is_active'=>'boolean']; }
