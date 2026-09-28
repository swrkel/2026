<?php

namespace Modules\EnterpriseFramework\Services\Widget;

class WidgetLibraryService
{
    public function available(): array
    {
        return ['kpi_card', 'summary_tile', 'line_chart', 'bar_chart', 'pie_chart', 'comparison_table', 'alert_card', 'top_n_list'];
    }
}
