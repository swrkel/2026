<?php
namespace Modules\AirlineTicketingNew\Entities;

class KpiAlertRule extends BaseAirlineTicketingModel
{
    protected $table = 'atn_kpi_alert_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'threshold_value' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
