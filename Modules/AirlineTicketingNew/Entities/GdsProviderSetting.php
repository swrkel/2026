<?php
namespace Modules\AirlineTicketingNew\Entities;

class GdsProviderSetting extends BaseAirlineTicketingModel
{
    protected $table = 'atn_gds_provider_settings';
    protected $guarded = ['id'];
    protected $casts = [
        'credentials_json' => 'encrypted:array',
        'options_json' => 'array',
        'is_active' => 'boolean',
    ];
}
