<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
class LeadsNewActivity extends Model { protected $table='leads_new_activities'; protected $guarded=[]; protected $casts=['activity_date'=>'datetime','meta'=>'array']; public function lead(){ return $this->belongsTo(LeadsNewLead::class,'lead_id'); } }
