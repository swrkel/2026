<?php

namespace Modules\PetroPD\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Services\PetroPdPaymentIntegrityReadinessService;
use Throwable;

class VerifyPetroPdPaymentIntegrityAllTenants extends Command
{
    protected $signature = 'petropd:verify-payment-integrity-all-tenants
        {--fail-on-warning : Return failure when any tenant has warnings}
        {--from= : Start from this tenant id (inclusive)}
        {--limit=0 : Maximum tenants to process; 0 means all}
        {--business_id= : Optional exact business id inside every tenant}
        {--pump_operator_id= : Optional exact Pump Operator id inside every tenant}
        {--shift_id= : Optional exact immutable Shift ID inside every tenant}
        {--settlement_id= : Optional settlement database id inside every tenant}
        {--settlement_no= : Optional visible settlement number inside every tenant}
        {--report-dir= : Absolute or storage/app-relative report directory}
        {--json : Print the final server summary as JSON}';

    protected $description = 'Verify PetroPD payment integrity across every central tenant using one server command.';

    public function handle(PetroPdPaymentIntegrityReadinessService $service): int
    {
        $from = trim((string) $this->option('from'));
        $limit = max(0, (int) $this->option('limit'));

        $query = Tenant::query()->orderBy('id');
        if ($from !== '') {
            $query->where('id', '>=', $from);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        // Load the central tenant list before tenancy is initialized. This avoids
        // an open central cursor being affected while the default connection is
        // switched repeatedly across tenant databases.
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants matched the requested range.');
            return self::SUCCESS;
        }

        $startedAt = now();
        $rows = [];
        $counts = [
            'ready' => 0,
            'warning' => 0,
            'failed' => 0,
            'error' => 0,
        ];

        $this->info('PetroPD payment-integrity verification');
        $this->line('Tenants queued: ' . $tenants->count());
        if ($from !== '') {
            $this->line('Starting from tenant: ' . $from);
        }

        foreach ($tenants as $index => $tenant) {
            $tenantId = (string) $tenant->getKey();
            $position = $index + 1;
            $this->line("[{$position}/{$tenants->count()}] {$tenantId}");

            try {
                if (function_exists('tenancy') && tenancy()->initialized) {
                    tenancy()->end();
                }

                tenancy()->initialize($tenant);

                $scope = [
                    'business_id' => $this->integerOption('business_id'),
                    'pump_operator_id' => $this->integerOption('pump_operator_id'),
                    'shift_id' => $this->integerOption('shift_id'),
                    'settlement_id' => $this->integerOption('settlement_id'),
                    'settlement_no' => $this->option('settlement_no') ?: null,
                ];

                $result = $service->verify($scope);
                $warningCount = (int) ($result['warning_count'] ?? 0);
                $blockingCount = (int) ($result['blocking_count'] ?? 0);

                if (! ($result['ready'] ?? false)) {
                    $status = 'FAILED';
                    $counts['failed']++;
                } elseif ($warningCount > 0) {
                    $status = 'WARNING';
                    $counts['warning']++;
                } else {
                    $status = 'READY';
                    $counts['ready']++;
                }

                $rows[] = [
                    'tenant_id' => $tenantId,
                    'database' => (string) DB::connection()->getDatabaseName(),
                    'status' => $status,
                    'blocking_count' => $blockingCount,
                    'warning_count' => $warningCount,
                    'message' => $this->resultMessage($result),
                    'checked_at' => now()->toDateTimeString(),
                    'checks' => $result['checks'] ?? [],
                ];

                $this->{$status === 'READY' ? 'info' : ($status === 'WARNING' ? 'warn' : 'error')}(
                    "  {$status}: blocking={$blockingCount}, warnings={$warningCount}"
                );
            } catch (Throwable $e) {
                $counts['error']++;
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'database' => null,
                    'status' => 'ERROR',
                    'blocking_count' => null,
                    'warning_count' => null,
                    'message' => $e->getMessage(),
                    'checked_at' => now()->toDateTimeString(),
                    'checks' => [],
                ];
                $this->error('  ERROR: ' . $e->getMessage());
            } finally {
                try {
                    if (function_exists('tenancy') && tenancy()->initialized) {
                        tenancy()->end();
                    }
                } catch (Throwable $endError) {
                    $this->warn('  Tenant context cleanup warning: ' . $endError->getMessage());
                }

                // Persist after every tenant so an interrupted 1,000-tenant run
                // still leaves a complete report up to the last processed tenant.
                // A report-path permission problem must not stop verification of
                // the remaining tenant databases.
                try {
                    $this->writeReports($startedAt, $rows, $counts, false);
                } catch (Throwable $reportError) {
                    $this->warn('  Progress report warning: ' . $reportError->getMessage());
                }
            }
        }

        $paths = $this->writeReports($startedAt, $rows, $counts, true);
        $summary = [
            'started_at' => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'processed' => count($rows),
            'counts' => $counts,
            'reports' => $paths,
        ];

        $this->newLine();
        $this->table(
            ['READY', 'WARNING', 'FAILED', 'ERROR', 'PROCESSED'],
            [[
                $counts['ready'],
                $counts['warning'],
                $counts['failed'],
                $counts['error'],
                count($rows),
            ]]
        );
        $this->line('CSV report: ' . $paths['csv']);
        $this->line('JSON report: ' . $paths['json']);

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $hasFailure = $counts['failed'] > 0 || $counts['error'] > 0;
        $hasWarningFailure = (bool) $this->option('fail-on-warning') && $counts['warning'] > 0;

        return ($hasFailure || $hasWarningFailure) ? self::FAILURE : self::SUCCESS;
    }

    private function resultMessage(array $result): string
    {
        $failed = collect($result['checks'] ?? [])
            ->filter(fn (array $check) => ($check['status'] ?? '') === 'FAIL')
            ->pluck('message')
            ->filter()
            ->take(5)
            ->implode(' | ');

        if ($failed !== '') {
            return $failed;
        }

        $warnings = collect($result['checks'] ?? [])
            ->filter(fn (array $check) => ($check['status'] ?? '') === 'WARN')
            ->pluck('message')
            ->filter()
            ->take(5)
            ->implode(' | ');

        return $warnings !== '' ? $warnings : 'No blocking PetroPD payment-integrity issue found.';
    }

    private function writeReports($startedAt, array $rows, array $counts, bool $final): array
    {
        $baseDir = trim((string) $this->option('report-dir'));
        if ($baseDir === '') {
            $baseDir = storage_path('app/petropd-integrity');
        } elseif (! str_starts_with($baseDir, DIRECTORY_SEPARATOR)) {
            $baseDir = storage_path('app/' . trim($baseDir, '/\\'));
        }

        if (! is_dir($baseDir) && ! @mkdir($baseDir, 0775, true) && ! is_dir($baseDir)) {
            throw new \RuntimeException('Unable to create report directory: ' . $baseDir);
        }

        $stamp = $startedAt->format('Ymd_His');
        $suffix = $final ? '' : '_progress';
        $csvPath = $baseDir . '/petropd_payment_integrity_all_tenants_' . $stamp . $suffix . '.csv';
        $jsonPath = $baseDir . '/petropd_payment_integrity_all_tenants_' . $stamp . $suffix . '.json';

        $handle = fopen($csvPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to write CSV report: ' . $csvPath);
        }
        fputcsv($handle, ['tenant_id', 'database', 'status', 'blocking_count', 'warning_count', 'message', 'checked_at']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['tenant_id'],
                $row['database'],
                $row['status'],
                $row['blocking_count'],
                $row['warning_count'],
                $row['message'],
                $row['checked_at'],
            ]);
        }
        fclose($handle);

        file_put_contents($jsonPath, json_encode([
            'generated_at' => now()->toDateTimeString(),
            'counts' => $counts,
            'tenants' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return ['csv' => $csvPath, 'json' => $jsonPath];
    }

    private function integerOption(string $name): ?int
    {
        $value = $this->option($name);
        return $value === null || $value === '' ? null : (int) $value;
    }
}
