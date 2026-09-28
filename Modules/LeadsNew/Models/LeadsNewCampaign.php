<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
class LeadsNewCampaign extends Model { protected $table='leads_new_campaigns'; protected $guarded=[]; public function leads(){ return $this->hasMany(LeadsNewLead::class,'campaign_id'); } }
