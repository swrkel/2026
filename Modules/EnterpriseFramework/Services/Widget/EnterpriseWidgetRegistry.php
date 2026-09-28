<?php

namespace Modules\EnterpriseFramework\Services\Widget;

class EnterpriseWidgetRegistry
{
    protected array $widgets = [];

    public function register(string $key, array $definition): void
    {
        $this->widgets[$key] = array_merge(['key' => $key, 'enabled' => true], $definition);
    }

    public function all(): array
    {
        return $this->widgets ?: $this->defaults();
    }

    public function defaults(): array
    {
        return [
            'kpi_card' => ['label' => 'KPI Card', 'category' => 'summary'],
            'trend_chart' => ['label' => 'Trend Chart', 'category' => 'chart'],
            'comparison_table' => ['label' => 'Comparison Table', 'category' => 'table'],
            'alert_card' => ['label' => 'Alert Card', 'category' => 'notification'],
        ];
    }
}
