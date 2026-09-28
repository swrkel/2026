<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
class LeadsNewSlaRule extends Model { protected $table='leads_new_sla_rules'; protected $guarded=[]; protected $casts=['rules'=>'array']; }
