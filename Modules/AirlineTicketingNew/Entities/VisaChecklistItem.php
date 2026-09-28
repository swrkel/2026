<?php

namespace Modules\AirlineTicketingNew\Entities;

class VisaChecklistItem extends BaseAirlineTicketingModel
{
    protected $table = 'atn_visa_checklist_items';

    protected $fillable = [
        'business_id','business_location_id','store_id','visa_application_id','item_name',
        'is_required','is_received','received_at','notes','created_by','updated_by'
    ];

    protected $casts = [
        'is_required' => 'boolean','is_received' => 'boolean','received_at' => 'datetime',
    ];
}
