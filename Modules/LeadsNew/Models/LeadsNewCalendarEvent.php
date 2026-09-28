<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LeadsNewCalendarEvent extends Model { use SoftDeletes; protected $table='leads_new_calendar_events'; protected $guarded=['id']; protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime']; }
