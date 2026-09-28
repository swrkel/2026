<?php
namespace Modules\AirlineTicketingNew\Entities;

class ScheduledReport extends BaseAirlineTicketingModel
{
    protected $table = 'atn_scheduled_reports';
    protected $guarded = ['id'];
    protected $casts = [
        'filters_json' => 'array',
        'recipients_json' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
