<?php

namespace Modules\PetroPD\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Services\PetroPdPaymentIntegrityReadinessService;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class VerifyPetroPdPaymentIntegrity extends Command
{
    protected $signature = 'petropd:verify-payment-integrity
        {--tenant= : Tenant id/subdomain}
        {--all-tenants : Verify every tenant intentionally}
        {--business_id= : Optional exact business id}
        {--pump_operator_id= : Optional exact Pump Operator id}
        {--shift_id= : Optional exact immutable Shift ID}
        {--settlement_id= : Optional settlement database id}
        {--settlement_no= : Optional visible settlement number}
        {--json : Output machine-readable JSON}
        {--fail-on-warning : Return failure when warnings exist}';

    protected $description = 'Run non-destructive PetroPD payment-integrity production readiness checks.';

    public function handle(PetroPdPaymentIntegrityReadinessService $service): int
    {
        if ($this->option('all-tenants')) {
            if (! $this->confirm('Verify PetroPD payment integrity for ALL tenants?', false)) {
                $this->warn('Cancelled.');
                return self::SUCCESS;
            }

            $failed = 0;
            Tenant::query()->orderBy('id')->chunk(20, function ($tenants) use ($service, &$failed) {
                foreach ($tenants as $tenant) {
                    try {
                        $result = $this->runForTenant($tenant, $service);
                        if ($result !== self::SUCCESS) {
                            $failed++;
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        $this->error("Tenant {$tenant->id}: {$e->getMessage()}");
                    }
                }
            });

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        if ($tenantId = $this->option('tenant')) {
            $tenant = Tenant::where('id', $tenantId)->first();
            if (! $tenant) {
                $this->error('Tenant not found: ' . $tenantId);
                return self::FAILURE;
            }

            return $this->runForTenant($tenant, $service);
        }

        $this->warn('No tenant option supplied. Verifying the CURRENT database connection only.');
        return $this->runCurrent($service, null);
    }

    private function runForTenant(Tenant $tenant, PetroPdPaymentIntegrityReadinessService $service): int
    {
        if (! $this->option('json')) {
            $this->newLine();
            $this->info('Tenant: ' . $tenant->id);
        }

        Tenancy::initialize($tenant);
        try {
            return $this->runCurrent($service, (string) $tenant->id);
        } finally {
            Tenancy::end();
        }
    }

    private function runCurrent(PetroPdPaymentIntegrityReadinessService $service, ?string $tenantReference): int
    {
        $scope = [
            'business_id' => $this->integerOption('business_id'),
            'pump_operator_id' => $this->integerOption('pump_operator_id'),
            'shift_id' => $this->integerOption('shift_id'),
            'settlement_id' => $this->integerOption('settlement_id'),
            'settlement_no' => $this->option('settlement_no') ?: null,
        ];

        $result = $service->verify($scope);
        $payload = [
            'tenant' => $tenantReference,
            'database' => DB::connection()->getDatabaseName(),
            'scope' => array_filter($scope, fn ($value) => $value !== null && $value !== ''),
            'ready' => $result['ready'],
            'blocking_count' => $result['blocking_count'],
            'warning_count' => $result['warning_count'],
            'checks' => $result['checks'],
            'scope_snapshot' => $result['scope_snapshot'],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line('Database: ' . $payload['database']);
            $this->table(
                ['Status', 'Check', 'Result'],
                collect($result['checks'])->map(fn (array $check) => [
                    $check['status'],
                    $check['key'],
                    $check['message'],
                ])->all()
            );

            if ($result['ready']) {
                $this->info('READY: no blocking PetroPD payment-integrity issue was found.');
            } else {
                $this->error('NOT READY: resolve all FAIL checks before finalizing affected settlements.');
            }

            if ($result['warning_count'] > 0) {
                $this->warn($result['warning_count'] . ' warning(s) require review.');
            }

            if (! empty($result['scope_snapshot']['totals'])) {
                $this->newLine();
                $this->info('Exact Shift snapshot totals');
                $this->table(
                    ['Payment Type', 'Amount'],
                    collect($result['scope_snapshot']['totals'])
                        ->map(fn ($amount, $type) => [$type, number_format((float) $amount, 4, '.', ',')])
                        ->values()
                        ->all()
                );
                $this->line('Snapshot fingerprint: ' . ($result['scope_snapshot']['fingerprint'] ?? '-'));
            }
        }

        $failOnWarning = (bool) $this->option('fail-on-warning');
        if (! $result['ready'] || ($failOnWarning && $result['warning_count'] > 0)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function integerOption(string $name): ?int
    {
        $value = $this->option($name);
        return $value === null || $value === '' ? null : (int) $value;
    }
}
