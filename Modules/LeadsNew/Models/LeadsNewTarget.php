<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
class LeadsNewTarget extends Model { protected $table='leads_new_targets'; protected $guarded=[]; protected $casts=['period_start'=>'date','period_end'=>'date']; }
