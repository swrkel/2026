<?php
namespace Modules\AirlineTicketingNew\Entities;

class ModuleUpgrade extends BaseAirlineTicketingModel
{
    protected $table = 'atn_module_upgrades';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'steps_json' => 'array',
    ];
}
