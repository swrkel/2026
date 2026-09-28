<?php
namespace Modules\AirlineTicketingNew\Entities;

class WorkflowDefinition extends BaseAirlineTicketingModel
{
    protected $table = 'atn_workflow_definitions';
    protected $guarded = ['id'];
    protected $casts = [
        'conditions_json' => 'array',
        'is_active' => 'boolean',
    ];
}
