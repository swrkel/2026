<?php
namespace Modules\AirlineTicketingNew\Entities;

class ApiCredential extends BaseAirlineTicketingModel
{
    protected $table = 'atn_api_credentials';
    protected $guarded = ['id'];
    protected $casts = [
        'credentials_json' => 'encrypted:array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
