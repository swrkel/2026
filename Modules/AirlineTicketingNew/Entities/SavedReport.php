<?php
namespace Modules\AirlineTicketingNew\Entities;

class SavedReport extends BaseAirlineTicketingModel
{
    protected $table = 'atn_saved_reports';
    protected $guarded = ['id'];
    protected $casts = [
        'filters_json' => 'array',
        'columns_json' => 'array',
        'is_shared' => 'boolean',
    ];
}
