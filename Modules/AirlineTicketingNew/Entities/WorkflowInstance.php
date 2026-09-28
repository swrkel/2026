<?php
namespace Modules\AirlineTicketingNew\Entities;

class WorkflowInstance extends BaseAirlineTicketingModel
{
    protected $table = 'atn_workflow_instances';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
