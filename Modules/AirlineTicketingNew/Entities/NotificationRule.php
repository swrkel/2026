<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class NotificationRule extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;
    protected $table = 'atn_notification_rules';
    protected $fillable = ['business_id','business_location_id','store_id','event_code','channel','recipient_type','recipient_value','template_subject','template_body','lead_minutes','is_active','created_by','updated_by'];
    protected $casts = ['lead_minutes'=>'integer','is_active'=>'boolean'];
}
