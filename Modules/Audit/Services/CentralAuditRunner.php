<?php

namespace Modules\Audit\Services;

class CentralAuditRunner
{
    protected $scope;
    protected $engine;
    protected $registry;

    public function __construct(CentralScopeService $scope, AuditEngine $engine, ModuleRegistry $registry)
    {
        $this->scope = $scope;
        $this->engine = $engine;
        $this->registry = $registry;
    }

    public function run(array $input, $userId = null): array
    {
        $sources = $this->scope->selectedSources($input['source_keys'] ?? []);
        $businessGroups = $this->scope->groupBusinessKeys($input['business_keys'] ?? []);
        $locationGroups = $this->scope->groupLocationKeys($input['location_keys'] ?? []);
        $explicitBusinesses = count((array) ($input['business_keys'] ?? [])) > 0;
        $explicitLocations = count((array) ($input['location_keys'] ?? [])) > 0;

        $allowedModules = $this->registry->modules();
        $requestedModules = (array) ($input['modules'] ?? []);
        $modules = array_values(array_intersect($requestedModules, $allowedModules));
        if (!$modules) {
            throw new \InvalidArgumentException('Select at least one valid Audit area.');
        }

        $globalModules = array_values(array_intersect($modules, ['System']));
        $scopedModules = array_values(array_diff($modules, $globalModules));
        $jobs = [];

        foreach ($sources as $source) {
            $sourceHasSelectedLocation = !empty($locationGroups[$source['key']] ?? []);
            $sourceHasSelectedBusiness = !empty($businessGroups[$source['key']] ?? []);
            $sourceIsInExplicitScope = !$explicitBusinesses && !$explicitLocations
                ? true
                : ($explicitLocations ? $sourceHasSelectedLocation : $sourceHasSelectedBusiness);

            // When the user selected businesses/locations from several data sources,
            // do not run even the database-wide System rules on a source that has no
            // selected scope item. This keeps "selected businesses only" literal.
            if (($explicitBusinesses || $explicitLocations) && !$sourceIsInExplicitScope) {
                continue;
            }

            if (($explicitBusinesses || $explicitLocations) && $globalModules) {
                // Route/schema checks are database-wide. Run them once per selected
                // database so a business/location selection never duplicates the same
                // global finding under several businesses.
                $jobs[] = [
                    'source' => $source,
                    'business_id' => null,
                    'location_id' => null,
                    'modules' => $globalModules,
                    'scope_label' => 'Database-wide system checks',
                ];
            }

            $modulesForScope = ($explicitBusinesses || $explicitLocations) ? $scopedModules : $modules;
            if (!$modulesForScope) {
                continue;
            }

            if ($explicitLocations) {
                foreach ($locationGroups[$source['key']] ?? [] as $location) {
                    $jobs[] = [
                        'source' => $source,
                        'business_id' => $location['business_id'],
                        'location_id' => $location['location_id'],
                        'modules' => $modulesForScope,
                        'scope_label' => 'Location #' . $location['location_id'],
                    ];
                }
                continue;
            }

            if ($explicitBusinesses) {
                foreach ($businessGroups[$source['key']] ?? [] as $businessId) {
                    $jobs[] = [
                        'source' => $source,
                        'business_id' => $businessId,
                        'location_id' => null,
                        'modules' => $modulesForScope,
                        'scope_label' => 'Business #' . $businessId,
                    ];
                }
                continue;
            }

            // All scope: one run per database. Rules that can identify business/location
            // attach row-level scope to findings, so this remains fast and avoids duplicate
            // global findings for every business/location.
            $jobs[] = [
                'source' => $source,
                'business_id' => null,
                'location_id' => null,
                'modules' => $modulesForScope,
                'scope_label' => 'All businesses / locations',
            ];
        }

        $maxJobs = max(1, (int) config('audit.central.max_run_jobs', 250));
        if (count($jobs) > $maxJobs) {
            throw new \RuntimeException('Selected scope creates ' . count($jobs) . ' audit jobs. The safe limit is ' . $maxJobs . '. Narrow the tenant/business/location selection.');
        }

        $results = [];
        foreach ($jobs as $job) {
            $source = $job['source'];
            try {
                $this->scope->withSource($source, function ($resolved) use (&$results, $job, $userId, $source) {
                    if (!$this->scope->hasAuditTables()) {
                        $results[] = [
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'business_id' => $job['business_id'],
                            'location_id' => $job['location_id'],
                            'scope_label' => $job['scope_label'],
                            'status' => 'skipped',
                            'run_no' => null,
                            'findings' => 0,
                            'message' => 'Audit tables are not installed in this database.',
                        ];
                        return;
                    }

                    $tenantKey = $source['type'] === 'tenant'
                        ? (string) $source['tenant_id']
                        : 'central:' . ($this->scope->currentDatabaseName() ?: 'database');

                    $context = new AuditContext(
                        $tenantKey,
                        $job['business_id'] ?: null,
                        $job['location_id'] ?: null,
                        $userId,
                        request('from'),
                        request('to'),
                        [
                            'central_orchestrated' => true,
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'database' => $this->scope->currentDatabaseName(),
                            'scope_label' => $job['scope_label'],
                        ]
                    );

                    $run = $this->engine->run($context, $job['modules']);
                    $results[] = [
                        'source_key' => $source['key'],
                        'source_label' => $source['label'],
                        'business_id' => $job['business_id'],
                        'location_id' => $job['location_id'],
                        'scope_label' => $job['scope_label'],
                        'status' => $run->status,
                        'run_no' => $run->run_no,
                        'findings' => (int) data_get($run->summary, 'findings', 0),
                        'message' => null,
                    ];
                });
            } catch (\Throwable $e) {
                $results[] = [
                    'source_key' => $source['key'],
                    'source_label' => $source['label'],
                    'business_id' => $job['business_id'],
                    'location_id' => $job['location_id'],
                    'scope_label' => $job['scope_label'],
                    'status' => 'failed_safely',
                    'run_no' => null,
                    'findings' => 0,
                    'message' => $this->safeMessage($e),
                ];
            }
        }

        return [
            'jobs' => count($jobs),
            'completed' => collect($results)->where('status', 'completed')->count(),
            'skipped' => collect($results)->where('status', 'skipped')->count(),
            'failed' => collect($results)->where('status', 'failed_safely')->count(),
            'findings' => collect($results)->sum('findings'),
            'results' => $results,
        ];
    }

    protected function safeMessage(\Throwable $e): string
    {
        $message = trim((string) $e->getMessage());
        if (strlen($message) > 300) {
            $message = substr($message, 0, 297) . '...';
        }
        return $message ?: get_class($e);
    }
}
