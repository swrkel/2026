<?php
namespace Modules\AirlineTicketingNew\Entities;

class ReportTemplate extends BaseAirlineTicketingModel
{
    protected $table = 'atn_report_templates';
    protected $guarded = ['id'];
    protected $casts = [
        'data_source_json' => 'array',
        'filters_json' => 'array',
        'columns_json' => 'array',
        'sorting_json' => 'array',
        'is_active' => 'boolean',
    ];
}
