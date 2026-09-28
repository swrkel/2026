<?php
namespace Modules\AirlineTicketingNew\Entities;

class PortalUser extends BaseAirlineTicketingModel
{
    protected $table = 'atn_portal_users';
    protected $guarded = ['id'];
    protected $hidden = ['password','remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
