<?php

namespace Modules\EnterpriseFramework\Services\Dashboard;

class DashboardEngine
{
    public function cards(array $items): array
    {
        return array_map(fn ($item) => array_merge([
            'title' => 'KPI',
            'value' => 0,
            'format' => 'number',
            'trend' => null,
            'route' => null,
        ], $item), $items);
    }
}
