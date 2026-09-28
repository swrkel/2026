<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LeadsNewMessageTemplate extends Model { use SoftDeletes; protected $table='leads_new_message_templates'; protected $guarded=['id']; protected $casts=['is_default'=>'boolean','is_active'=>'boolean']; }
