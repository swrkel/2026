<?php

namespace Modules\Audit\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CentralReportService
{
    protected $scope;
    protected $dates;
    protected $registry;

    public function __construct(CentralScopeService $scope, DateRangeService $dates, ModuleRegistry $registry)
    {
        $this->scope = $scope;
        $this->dates = $dates;
        $this->registry = $registry;
    }

    public function data(array $filters, bool $currentOnly = false): array
    {
        [$rows, $meta] = $this->collectRows($filters, $currentOnly);

        $summary = [
            'total' => $rows->count(),
            'open' => $rows->whereIn('status', ['open', 'acknowledged', 'under_review', 'reopened'])->count(),
            'critical' => $rows->whereIn('status', ['open', 'acknowledged', 'under_review', 'reopened'])->where('severity', 'critical')->count(),
            'high' => $rows->whereIn('status', ['open', 'acknowledged', 'under_review', 'reopened'])->where('severity', 'high')->count(),
            'warning' => $rows->whereIn('status', ['open', 'acknowledged', 'under_review', 'reopened'])->where('severity', 'warning')->count(),
            'resolved' => $rows->where('status', 'resolved')->count(),
            'false_positive' => $rows->where('status', 'false_positive')->count(),
        ];

        $perPage = max(25, min(250, (int) ($filters['per_page'] ?? 100)));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($items, $rows->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return array_merge($meta, [
            'rows_collection' => $rows,
            'rows' => $paginator,
            'summary' => $summary,
            'modules' => $this->registry->modules(),
        ]);
    }

    public function exportRows(array $filters): array
    {
        [$rows, $meta] = $this->collectRows($filters, false);
        return [$rows, $meta];
    }

    public function dashboard(array $filters): array
    {
        $activeFilters = $filters;
        $activeFilters['status'] = '';
        [$rows, $meta] = $this->collectRows($activeFilters, true);

        $bySource = $rows->groupBy('source_key')->map(function ($items) {
            return [
                'total' => $items->count(),
                'critical' => $items->where('severity', 'critical')->count(),
                'high' => $items->where('severity', 'high')->count(),
                'warning' => $items->where('severity', 'warning')->count(),
            ];
        });

        $businessGroups = $this->scope->groupBusinessKeys($filters['business_keys'] ?? []);
        $locationGroups = $this->scope->groupLocationKeys($filters['location_keys'] ?? []);
        $explicitBusinesses = count((array) ($filters['business_keys'] ?? [])) > 0;
        $explicitLocations = count((array) ($filters['location_keys'] ?? [])) > 0;

        $sourceStatus = [];
        foreach ($meta['selected_sources'] as $source) {
            $status = [
                'source_key' => $source['key'],
                'source_label' => $source['label'],
                'source_type' => $source['type'],
                'database' => $source['database'] ?? null,
                'latest_run' => null,
                'latest_status' => null,
                'latest_findings' => 0,
                'businesses' => 0,
                'locations' => 0,
                'audit_ready' => false,
                'error' => null,
            ];
            try {
                $this->scope->withSource($source, function ($resolved) use (&$status, $source, $businessGroups, $locationGroups, $explicitBusinesses, $explicitLocations) {
                    $status['database'] = $this->scope->currentDatabaseName();
                    $status['audit_ready'] = $this->scope->hasAuditTables();

                    $selectedBusinessIds = $businessGroups[$source['key']] ?? [];
                    $selectedLocations = $locationGroups[$source['key']] ?? [];
                    $selectedLocationIds = array_values(array_unique(array_map(function ($row) {
                        return (string) $row['location_id'];
                    }, $selectedLocations)));
                    $selectedLocationBusinessIds = array_values(array_unique(array_filter(array_map(function ($row) {
                        return isset($row['business_id']) && $row['business_id'] !== null ? (string) $row['business_id'] : null;
                    }, $selectedLocations), function ($value) {
                        return $value !== null && $value !== '';
                    })));

                    if ($explicitLocations) {
                        $status['businesses'] = count($selectedLocationBusinessIds);
                    } elseif ($explicitBusinesses) {
                        $status['businesses'] = count($selectedBusinessIds);
                    } elseif (Schema::hasTable('business')) {
                        $status['businesses'] = DB::table('business')->count();
                    }

                    $locationTable = $this->scope->locationTable();
                    if ($explicitLocations) {
                        $status['locations'] = count($selectedLocationIds);
                    } elseif ($locationTable) {
                        $locationQuery = DB::table($locationTable);
                        if ($explicitBusinesses && Schema::hasColumn($locationTable, 'business_id')) {
                            $locationQuery->whereIn('business_id', $selectedBusinessIds ?: ['__none__']);
                        }
                        $status['locations'] = $locationQuery->count();
                    }
                    if ($status['audit_ready']) {
                        $run = DB::table('audit_runs')->orderByDesc('id')->first();
                        if ($run) {
                            $status['latest_run'] = $run->run_no;
                            $status['latest_status'] = $run->status;
                            $summary = $run->summary ? json_decode($run->summary, true) : [];
                            $status['latest_findings'] = (int) data_get($summary, 'findings', 0);
                        }
                    }
                });
            } catch (\Throwable $e) {
                $status['error'] = $this->safeMessage($e);
            }
            $counts = $bySource->get($source['key'], ['total' => 0, 'critical' => 0, 'high' => 0, 'warning' => 0]);
            $status = array_merge($status, $counts);
            $sourceStatus[] = $status;
        }

        return array_merge($meta, [
            'summary' => [
                'sources' => count($meta['selected_sources']),
                'businesses' => collect($sourceStatus)->sum('businesses'),
                'locations' => collect($sourceStatus)->sum('locations'),
                'current' => $rows->count(),
                'critical' => $rows->where('severity', 'critical')->count(),
                'high' => $rows->where('severity', 'high')->count(),
                'warning' => $rows->where('severity', 'warning')->count(),
                'unavailable' => collect($sourceStatus)->whereNotNull('error')->count(),
            ],
            'source_status' => $sourceStatus,
            'recent' => $rows->take(20),
        ]);
    }

    public function schedules(array $sourceKeys): array
    {
        $sources = $this->scope->selectedSources($sourceKeys);
        $rows = collect();
        $errors = [];
        foreach ($sources as $source) {
            try {
                $this->scope->withSource($source, function ($resolved) use (&$rows, $source) {
                    if (!Schema::hasTable('audit_schedules')) {
                        return;
                    }
                    $businesses = $this->scope->businessNameMap();
                    foreach (DB::table('audit_schedules')->orderByDesc('id')->get() as $schedule) {
                        $rows->push([
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'business_id' => $schedule->business_id,
                            'business_name' => $schedule->business_id ? ($businesses[(string) $schedule->business_id] ?? ('Business #' . $schedule->business_id)) : 'All Businesses',
                            'name' => $schedule->name,
                            'frequency' => $schedule->frequency,
                            'is_enabled' => (bool) $schedule->is_enabled,
                            'last_run_at' => $this->formatDate($schedule->last_run_at),
                            'created_at' => $this->formatDate($schedule->created_at),
                        ]);
                    }
                });
            } catch (\Throwable $e) {
                $errors[] = ['source_key' => $source['key'], 'source_label' => $source['label'], 'message' => $this->safeMessage($e)];
            }
        }
        return ['rows' => $rows, 'errors' => $errors, 'selected_sources' => $sources];
    }

    protected function collectRows(array $filters, bool $currentOnly): array
    {
        [$from, $to] = $this->dates->resolve(
            $filters['preset'] ?? null,
            $filters['from'] ?? null,
            $filters['to'] ?? null
        );

        $sourceKeys = $filters['source_keys'] ?? [];
        $businessKeys = $filters['business_keys'] ?? [];
        $locationKeys = $filters['location_keys'] ?? [];
        $businessGroups = $this->scope->groupBusinessKeys($businessKeys);
        $locationGroups = $this->scope->groupLocationKeys($locationKeys);
        $explicitBusinesses = count((array) $businessKeys) > 0;
        $explicitLocations = count((array) $locationKeys) > 0;
        $sources = $this->scope->selectedSources($sourceKeys);
        $rows = collect();
        $errors = [];
        $maxRows = max(1000, (int) config('audit.central.max_report_rows', 20000));
        $perSourceMax = max(500, (int) config('audit.central.max_report_rows_per_source', 5000));

        foreach ($sources as $source) {
            if ($explicitLocations && empty($locationGroups[$source['key']])) {
                continue;
            }
            if (!$explicitLocations && $explicitBusinesses && empty($businessGroups[$source['key']])) {
                continue;
            }

            try {
                $this->scope->withSource($source, function ($resolved) use (&$rows, &$errors, $source, $filters, $from, $to, $businessGroups, $locationGroups, $explicitBusinesses, $explicitLocations, $perSourceMax, $maxRows, $currentOnly) {
                    if (!$this->scope->hasAuditTables()) {
                        $errors[] = [
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'message' => 'Audit tables are not installed in this database.',
                        ];
                        return;
                    }

                    $q = DB::table('audit_findings')->whereBetween('last_seen_at', [$from, $to]);
                    if ($currentOnly) {
                        $q->whereNotIn('status', ['resolved', 'false_positive', 'ignored']);
                    }
                    foreach (['module', 'severity'] as $field) {
                        $value = isset($filters[$field]) ? trim((string) $filters[$field]) : '';
                        if ($value !== '') {
                            $q->where($field, $value);
                        }
                    }
                    if (!$currentOnly) {
                        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
                        if ($status !== '') {
                            $q->where('status', $status);
                        }
                    }

                    if ($explicitLocations) {
                        $locationIds = array_values(array_unique(array_map(function ($row) {
                            return $row['location_id'];
                        }, $locationGroups[$source['key']] ?? [])));
                        if (!$locationIds) {
                            return;
                        }
                        $q->whereIn('location_id', $locationIds);
                    } elseif ($explicitBusinesses) {
                        $ids = $businessGroups[$source['key']] ?? [];
                        if (!$ids) {
                            return;
                        }
                        $q->whereIn('business_id', $ids);
                    }

                    $search = trim((string) ($filters['search'] ?? ''));
                    if ($search !== '') {
                        $q->where(function ($query) use ($search) {
                            $like = '%' . $search . '%';
                            $query->where('finding_no', 'like', $like)
                                ->orWhere('module', 'like', $like)
                                ->orWhere('rule_code', 'like', $like)
                                ->orWhere('severity', 'like', $like)
                                ->orWhere('status', 'like', $like)
                                ->orWhere('title', 'like', $like)
                                ->orWhere('message', 'like', $like)
                                ->orWhere('source_table', 'like', $like)
                                ->orWhere('source_id', 'like', $like);
                        });
                    }

                    $businesses = $this->scope->businessNameMap();
                    $locations = $this->scope->locationNameMap();
                    $database = $this->scope->currentDatabaseName();
                    foreach ($q->orderByDesc('last_seen_at')->limit($perSourceMax)->get() as $row) {
                        if ($rows->count() >= $maxRows) {
                            break;
                        }
                        $businessId = $row->business_id === null ? null : (string) $row->business_id;
                        $locationId = $row->location_id === null ? null : (string) $row->location_id;
                        $rows->push([
                            'id' => (int) $row->id,
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'source_type' => $source['type'],
                            'tenant_id' => $source['tenant_id'],
                            'database' => $database,
                            'finding_no' => $row->finding_no,
                            'module' => $row->module,
                            'rule_code' => $row->rule_code,
                            'severity' => $row->severity,
                            'status' => $row->status,
                            'title' => $row->title,
                            'message' => $row->message,
                            'business_id' => $businessId,
                            'business_name' => $businessId === null ? '—' : ($businesses[$businessId] ?? ('Business #' . $businessId)),
                            'location_id' => $locationId,
                            'location_name' => $locationId === null ? '—' : ($locations[$locationId] ?? ('Location #' . $locationId)),
                            'last_seen_at' => $row->last_seen_at,
                            'last_seen_display' => $this->formatDate($row->last_seen_at),
                            'source_table' => $row->source_table,
                            'source_id' => $row->source_id,
                        ]);
                    }
                });
            } catch (\Throwable $e) {
                $errors[] = [
                    'source_key' => $source['key'],
                    'source_label' => $source['label'],
                    'message' => $this->safeMessage($e),
                ];
            }

            if ($rows->count() >= $maxRows) {
                break;
            }
        }

        $rows = $rows->sortByDesc(function ($row) {
            return (string) ($row['last_seen_at'] ?? '');
        })->values();

        return [$rows, [
            'from' => $from,
            'to' => $to,
            'selected_sources' => $sources,
            'source_errors' => $errors,
            'source_options' => $this->scope->sources(),
            // Keep the central page fast: tenant/business/location catalogues are
            // loaded only after one or more data sources are explicitly selected.
            'business_options' => !empty($filters['source_keys']) ? $this->scope->businessOptions($filters['source_keys']) : [],
            'location_options' => !empty($filters['source_keys']) ? $this->scope->locationOptions($filters['source_keys'], $filters['business_keys'] ?? []) : [],
        ]];
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '—';
        }
        try {
            return Carbon::parse($value)->format('d M Y H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function safeMessage(\Throwable $e): string
    {
        $message = trim((string) $e->getMessage());
        if (strlen($message) > 240) {
            $message = substr($message, 0, 237) . '...';
        }
        return $message ?: get_class($e);
    }
}
