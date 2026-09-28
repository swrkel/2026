<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class NotificationLog extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;
    protected $table = 'atn_notification_logs';
    protected $fillable = ['business_id','business_location_id','store_id','event_code','reference_type','reference_id','channel','recipient','subject','message','status','provider_reference','sent_at','error_message','created_by','updated_by'];
    protected $casts = ['sent_at'=>'datetime'];
}
