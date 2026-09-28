<?php
namespace Modules\AirlineTicketingNew\Entities;

class VisaStatusHistory extends BaseAirlineTicketingModel
{
    protected $table = 'atn_visa_status_history';
    protected $guarded = ['id'];
    protected $casts = ['changed_at' => 'datetime'];
}
