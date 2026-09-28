<?php

namespace Modules\AutoService\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutoServiceCommandCentreService
{
    protected string $connection;
    protected ?int $businessId;
    protected ?int $locationId;

    public function __construct(?string $connection = null, ?int $businessId = null, ?int $locationId = null)
    {
        $this->connection = $connection ?: config('database.default');
        $this->businessId = $businessId;
        $this->locationId = $locationId;
    }

    public function summary(array $filters = []): array
    {
        $today = Carbon::today()->toDateString();
        $delayHours = (int)($filters['delay_hours'] ?? 24);
        $delayCutoff = Carbon::now()->subHours(max($delayHours, 1));

        return [
            'filters' => [
                'delay_hours' => $delayHours,
                'as_of' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            'tiles' => $this->workflowTiles($today),
            'kpis' => $this->kpis($today),
            'attention' => $this->attention($delayCutoff),
            'queues' => [
                'recent_jobs' => $this->recentJobs(),
                'delayed_jobs' => $this->delayedJobs($delayCutoff),
                'waiting_customer_approval' => $this->approvalQueue(),
                'waiting_parts' => $this->waitingPartsQueue(),
                'qc_queue' => $this->qcQueue(),
                'delivery_queue' => $this->deliveryQueue(),
            ],
            'workload' => [
                'technicians' => $this->technicianWorkload(),
                'bays' => $this->bayOccupancy(),
            ],
            'quick_links' => $this->quickLinks(),
        ];
    }

    protected function workflowTiles(string $today): array
    {
        return [
            'vehicles_waiting' => $this->countJobs(['received', 'reception', 'waiting', 'pending']),
            'under_inspection' => $this->countJobs(['inspection', 'inspect', 'under_inspection']),
            'waiting_approval' => $this->countJobs(['waiting_approval', 'approval', 'estimate_ready', 'awaiting_customer_approval']) + $this->pendingApprovalCount(),
            'waiting_parts' => $this->countJobs(['waiting_parts', 'parts_pending', 'awaiting_parts']),
            'under_repair' => $this->countJobs(['repair', 'in_progress', 'workshop', 'started', 'assigned']),
            'quality_control' => $this->countJobs(['qc', 'quality_check', 'ready_for_qc']) + $this->activeQcCount(),
            'ready_for_delivery' => $this->countJobs(['ready', 'ready_for_delivery', 'invoice_ready']),
            'delivered_today' => $this->deliveredToday($today),
        ];
    }

    protected function kpis(string $today): array
    {
        return [
            'jobs_today' => $this->tableExists('auto_service_jobs') ? $this->dateCount('auto_service_jobs', $this->firstExistingColumn('auto_service_jobs', ['job_date', 'created_at']), $today) : 0,
            'open_jobs' => $this->openJobsCount(),
            'delayed_jobs' => $this->delayedJobs(Carbon::now()->subHours(24))->count(),
            'bay_occupied' => $this->tableExists('auto_service_bay_allocations') ? (clone $this->scoped('auto_service_bay_allocations'))->whereNull('released_at')->count() : 0,
            'reminders_due' => $this->tableExists('auto_service_reminders') ? (clone $this->scoped('auto_service_reminders'))->where('status', 'pending')->whereDate('send_on', '<=', $today)->count() : 0,
            'revenue_today' => $this->revenueToday($today),
            'pending_invoices' => $this->pendingInvoiceCount(),
        ];
    }

    protected function attention(Carbon $delayCutoff): array
    {
        return [
            'delayed_jobs' => $this->delayedJobs($delayCutoff)->count(),
            'waiting_approval' => $this->pendingApprovalCount(),
            'waiting_parts' => $this->countJobs(['waiting_parts', 'parts_pending', 'awaiting_parts']),
            'qc_pending' => $this->activeQcCount(),
            'invoice_pending' => $this->pendingInvoiceCount(),
        ];
    }

    protected function scoped(string $table)
    {
        $q = DB::connection($this->connection)->table($table);

        if ($this->businessId && $this->tableExists($table) && Schema::connection($this->connection)->hasColumn($table, 'business_id')) {
            $q->where($table . '.business_id', $this->businessId);
        }

        if ($this->locationId && $this->tableExists($table)) {
            foreach (['location_id', 'business_location_id'] as $column) {
                if (Schema::connection($this->connection)->hasColumn($table, $column)) {
                    $q->where($table . '.' . $column, $this->locationId);
                    break;
                }
            }
        }

        return $q;
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::connection($this->connection)->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        if (!$this->tableExists($table)) {
            return null;
        }
        foreach ($columns as $column) {
            if (Schema::connection($this->connection)->hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    protected function dateCount(string $table, ?string $column, string $date): int
    {
        if (!$column) {
            return 0;
        }
        try {
            return (int)(clone $this->scoped($table))->whereDate($column, $date)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function countJobs(array $statuses, ?string $date = null): int
    {
        if (!$this->tableExists('auto_service_jobs')) {
            return 0;
        }

        try {
            $q = $this->scoped('auto_service_jobs');
            $q->where(function ($query) use ($statuses) {
                if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'status')) {
                    $query->whereIn('status', $statuses);
                }
                if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'workflow_stage')) {
                    $query->orWhereIn('workflow_stage', $statuses);
                }
            });
            if ($date && Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'updated_at')) {
                $q->whereDate('updated_at', $date);
            }
            return (int)$q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function openJobsCount(): int
    {
        if (!$this->tableExists('auto_service_jobs')) {
            return 0;
        }
        try {
            return (int)$this->scoped('auto_service_jobs')
                ->whereNotIn('status', ['delivered', 'cancelled', 'closed'])
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function pendingApprovalCount(): int
    {
        if (!$this->tableExists('auto_service_approval_requests')) {
            return 0;
        }
        try {
            return (int)$this->scoped('auto_service_approval_requests')->whereIn('status', ['pending', 'sent'])->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function activeQcCount(): int
    {
        if (!$this->tableExists('auto_service_quality_checks')) {
            return 0;
        }
        try {
            return (int)$this->scoped('auto_service_quality_checks')->whereIn('status', ['pending', 'in_progress'])->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function deliveredToday(string $today): int
    {
        if ($this->tableExists('auto_service_deliveries')) {
            $column = $this->firstExistingColumn('auto_service_deliveries', ['delivered_at', 'created_at']);
            return $this->dateCount('auto_service_deliveries', $column, $today);
        }
        return $this->countJobs(['delivered'], $today);
    }

    protected function pendingInvoiceCount(): int
    {
        if (!$this->tableExists('auto_service_invoices')) {
            return 0;
        }
        try {
            $q = $this->scoped('auto_service_invoices');
            if (Schema::connection($this->connection)->hasColumn('auto_service_invoices', 'payment_status')) {
                $q->whereIn('payment_status', ['pending', 'partial', 'due']);
            } elseif (Schema::connection($this->connection)->hasColumn('auto_service_invoices', 'status')) {
                $q->whereIn('status', ['draft', 'pending', 'unpaid']);
            }
            return (int)$q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function revenueToday(string $today): float
    {
        if (!$this->tableExists('auto_service_invoices')) {
            return 0.0;
        }
        $dateColumn = $this->firstExistingColumn('auto_service_invoices', ['invoice_date', 'created_at']);
        $amountColumn = $this->firstExistingColumn('auto_service_invoices', ['total_amount', 'grand_total', 'net_total']);
        if (!$dateColumn || !$amountColumn) {
            return 0.0;
        }
        try {
            return (float)(clone $this->scoped('auto_service_invoices'))->whereDate($dateColumn, $today)->sum($amountColumn);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    protected function recentJobs()
    {
        if (!$this->tableExists('auto_service_jobs')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_jobs')->orderByDesc('id')->limit(12)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function delayedJobs(Carbon $cutoff)
    {
        if (!$this->tableExists('auto_service_jobs')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_jobs')
                ->whereNotIn('status', ['delivered', 'cancelled', 'closed'])
                ->where(function ($query) use ($cutoff) {
                    if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'promised_at')) {
                        $query->where('promised_at', '<', Carbon::now());
                    }
                    if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'expected_completion_at')) {
                        $query->orWhere('expected_completion_at', '<', Carbon::now());
                    }
                    if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'updated_at')) {
                        $query->orWhere('updated_at', '<=', $cutoff);
                    }
                })
                ->orderBy('updated_at')
                ->limit(20)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function approvalQueue()
    {
        if (!$this->tableExists('auto_service_approval_requests')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_approval_requests')->whereIn('status', ['pending', 'sent'])->orderByDesc('id')->limit(12)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function waitingPartsQueue()
    {
        if (!$this->tableExists('auto_service_jobs')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_jobs')
                ->where(function ($query) {
                    $query->whereIn('status', ['waiting_parts', 'parts_pending', 'awaiting_parts']);
                    if (Schema::connection($this->connection)->hasColumn('auto_service_jobs', 'workflow_stage')) {
                        $query->orWhereIn('workflow_stage', ['waiting_parts', 'parts_pending', 'awaiting_parts']);
                    }
                })
                ->orderByDesc('id')
                ->limit(12)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function qcQueue()
    {
        if (!$this->tableExists('auto_service_quality_checks')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_quality_checks')->whereIn('status', ['pending', 'in_progress'])->orderByDesc('id')->limit(12)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function deliveryQueue()
    {
        if (!$this->tableExists('auto_service_deliveries')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_deliveries')->whereIn('status', ['pending', 'ready'])->orderByDesc('id')->limit(12)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function technicianWorkload()
    {
        if (!$this->tableExists('auto_service_job_mechanics')) {
            return collect();
        }
        try {
            $q = $this->scoped('auto_service_job_mechanics')
                ->select('mechanic_id', DB::raw('COUNT(*) as active_jobs'))
                ->whereIn('status', ['assigned', 'started', 'in_progress', 'pending'])
                ->groupBy('mechanic_id')
                ->orderByDesc('active_jobs')
                ->limit(10);

            return $q->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function bayOccupancy()
    {
        if (!$this->tableExists('auto_service_bay_allocations')) {
            return collect();
        }
        try {
            return $this->scoped('auto_service_bay_allocations')->whereNull('released_at')->orderByDesc('id')->limit(12)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function quickLinks(): array
    {
        return [
            ['title' => 'New Job Card', 'url' => url('/auto-service/jobs/create'), 'icon' => 'fa-wrench'],
            ['title' => 'Appointments', 'url' => url('/auto-service/appointments'), 'icon' => 'fa-calendar'],
            ['title' => 'Service Flow', 'url' => url('/auto-service/service-flow'), 'icon' => 'fa-random'],
            ['title' => 'Parts & Labour', 'url' => url('/auto-service/parts-labour'), 'icon' => 'fa-cubes'],
            ['title' => 'Billing & Delivery', 'url' => url('/auto-service/billing-delivery'), 'icon' => 'fa-credit-card'],
            ['title' => 'Customer Portal', 'url' => url('/auto-service/customer-portal/lookup'), 'icon' => 'fa-user'],
        ];
    }
}
