<?php
namespace Modules\AirlineTicketingNew\Entities;

class InsuranceProvider extends BaseAirlineTicketingModel
{
    protected $table = 'atn_insurance_providers';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];
}
