<?php

namespace Modules\LeadsNew\Reports;

class LeadsNewReportRegistry
{
    public static function reports(): array
    {
        return [
            'lead_register',
            'followup_register',
            'opportunity_register',
            'conversion_register',
            'lost_leads',
            'sales_executive_performance',
            'territory_performance',
            'campaign_performance',
            'source_analysis',
            'lead_ageing',
            'pipeline_forecast',
            'executive_summary',
        ];
    }
}
