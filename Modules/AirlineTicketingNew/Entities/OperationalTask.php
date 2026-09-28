<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class OperationalTask extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;
    protected $table = 'atn_operational_tasks';
    protected $fillable = ['business_id','business_location_id','store_id','task_no','task_type','reference_type','reference_id','title','description','priority','due_at','assigned_to','status','completed_at','created_by','updated_by'];
    protected $casts = ['due_at'=>'datetime','completed_at'=>'datetime'];
}
