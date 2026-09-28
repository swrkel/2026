<?php
namespace Modules\AirlineTicketingNew\Services\Analytics;

use Modules\AirlineTicketingNew\Entities\KpiAlertRule;
use Modules\AirlineTicketingNew\Entities\OperationalException;

class KpiAlertService
{
    public function evaluate(int $businessId, array $metrics): int
    {
        $count = 0;

        KpiAlertRule::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get()
            ->each(function (KpiAlertRule $rule) use ($metrics, &$count): void {
                $actual = (float) ($metrics[$rule->metric_code] ?? 0);
                $threshold = (float) $rule->threshold_value;

                $triggered = match ($rule->operator) {
                    'gt' => $actual > $threshold,
                    'gte' => $actual >= $threshold,
                    'lt' => $actual < $threshold,
                    'lte' => $actual <= $threshold,
                    'eq' => abs($actual - $threshold) < 0.0001,
                    default => false,
                };

                if ($triggered) {
                    OperationalException::query()->create([
                        'business_id' => $rule->business_id,
                        'exception_type' => 'kpi_alert',
                        'severity' => $rule->severity,
                        'description' => $rule->name . ': ' . $actual,
                        'context_json' => [
                            'metric_code' => $rule->metric_code,
                            'actual' => $actual,
                            'threshold' => $threshold,
                        ],
                        'status' => 'open',
                        'detected_at' => now(),
                    ]);
                    $count++;
                }
            });

        return $count;
    }
}
