<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
class LeadsNewApiToken extends Model { protected $table='leads_new_api_tokens'; protected $guarded=['id']; protected $casts=['abilities'=>'array','last_used_at'=>'datetime','expires_at'=>'datetime','is_active'=>'boolean']; }
