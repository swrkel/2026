<?php

namespace Modules\BeautySaloons\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BeautyDashboardMetricService
{
    public function owner(array $filters = []): array
    {
        $date = $this->dateRange($filters);

        return [
            'revenue_today' => $this->salesTotal(Carbon::today(), Carbon::today(), $filters),
            'revenue_month' => $this->salesTotal(now()->startOfMonth(), now()->endOfMonth(), $filters),
            'revenue_year' => $this->salesTotal(now()->startOfYear(), now()->endOfYear(), $filters),
            'appointments_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters),
            'completed_appointments_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters, 'completed'),
            'new_customers_month' => $this->customerCount(now()->startOfMonth(), now()->endOfMonth(), $filters),
            'top_staff' => $this->topStaff($date['start'], $date['end'], $filters),
            'top_services' => $this->topServices($date['start'], $date['end'], $filters),
            'payment_mix' => $this->paymentMix($date['start'], $date['end'], $filters),
            'revenue_trend' => $this->dailyRevenueTrend($date['start'], $date['end'], $filters),
        ];
    }

    public function branchManager(array $filters = []): array
    {
        $date = $this->dateRange($filters);

        return [
            'appointments_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters),
            'walkins_today' => $this->safeCount('beauty_queue_entries', $filters, Carbon::today(), Carbon::today()),
            'no_shows_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters, 'no_show'),
            'daily_collection' => $this->salesTotal(Carbon::today(), Carbon::today(), $filters),
            'staff_utilization' => $this->staffUtilization($date['start'], $date['end'], $filters),
            'resource_utilization' => $this->resourceUtilization($date['start'], $date['end'], $filters),
            'hourly_appointments' => $this->hourlyAppointments(Carbon::today(), $filters),
        ];
    }

    public function reception(array $filters = []): array
    {
        return [
            'waiting_queue' => $this->safeCount('beauty_queue_entries', $filters, Carbon::today(), Carbon::today(), 'waiting'),
            'checked_in' => $this->safeCount('beauty_queue_entries', $filters, Carbon::today(), Carbon::today(), 'checked_in'),
            'upcoming_appointments' => $this->upcomingAppointments($filters),
            'staff_available' => $this->staffAvailable($filters),
            'rooms_available' => $this->roomsAvailable($filters),
        ];
    }

    public function staff(array $filters = []): array
    {
        $userId = $filters['staff_id'] ?? auth()->id();
        $filters['staff_id'] = $userId;

        return [
            'today_schedule' => $this->staffSchedule($userId),
            'completed_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters, 'completed'),
            'pending_today' => $this->appointmentCount(Carbon::today(), Carbon::today(), $filters, 'scheduled'),
            'commission_today' => $this->commissionTotal(Carbon::today(), Carbon::today(), $filters),
            'commission_month' => $this->commissionTotal(now()->startOfMonth(), now()->endOfMonth(), $filters),
        ];
    }

    public function finance(array $filters = []): array
    {
        $date = $this->dateRange($filters);

        return [
            'collection_total' => $this->salesTotal($date['start'], $date['end'], $filters),
            'cash_total' => $this->paymentTotal($date['start'], $date['end'], $filters, 'cash'),
            'card_total' => $this->paymentTotal($date['start'], $date['end'], $filters, 'card'),
            'wallet_total' => $this->paymentTotal($date['start'], $date['end'], $filters, 'wallet'),
            'voucher_total' => $this->paymentTotal($date['start'], $date['end'], $filters, 'voucher'),
            'discount_total' => $this->sumColumn('beauty_sales', 'discount_amount', $date['start'], $date['end'], $filters),
            'tax_total' => $this->sumColumn('beauty_sales', 'tax_amount', $date['start'], $date['end'], $filters),
            'refund_total' => $this->sumColumn('beauty_refunds', 'amount', $date['start'], $date['end'], $filters),
            'payment_mix' => $this->paymentMix($date['start'], $date['end'], $filters),
        ];
    }

    private function dateRange(array $filters): array
    {
        return [
            'start' => isset($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth(),
            'end' => isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfDay(),
        ];
    }

    private function tenantFilter($query, array $filters)
    {
        if (auth()->check() && method_exists(auth()->user(), 'business_id')) {
            $query->where('business_id', auth()->user()->business_id);
        } elseif (session()->has('business.id')) {
            $query->where('business_id', session('business.id'));
        }
        if (!empty($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }
        return $query;
    }

    private function tableExists(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function salesTotal($start, $end, array $filters): float
    {
        return $this->sumColumn('beauty_sales', 'final_total', $start, $end, $filters);
    }

    private function appointmentCount($start, $end, array $filters, ?string $status = null): int
    {
        if (!$this->tableExists('beauty_appointments')) return 0;
        $q = DB::table('beauty_appointments')->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()]);
        if ($status) $q->where('status', $status);
        return (int) $this->tenantFilter($q, $filters)->count();
    }

    private function customerCount($start, $end, array $filters): int
    {
        if (!$this->tableExists('beauty_customers')) return 0;
        $q = DB::table('beauty_customers')->whereBetween('created_at', [$start, $end]);
        return (int) $this->tenantFilter($q, $filters)->count();
    }

    private function safeCount(string $table, array $filters, $start, $end, ?string $status = null): int
    {
        if (!$this->tableExists($table)) return 0;
        $dateColumn = DB::getSchemaBuilder()->hasColumn($table, 'transaction_date') ? 'transaction_date' : 'created_at';
        $q = DB::table($table)->whereBetween($dateColumn, [$start, $end]);
        if ($status && DB::getSchemaBuilder()->hasColumn($table, 'status')) $q->where('status', $status);
        return (int) $this->tenantFilter($q, $filters)->count();
    }

    private function sumColumn(string $table, string $column, $start, $end, array $filters): float
    {
        if (!$this->tableExists($table) || !DB::getSchemaBuilder()->hasColumn($table, $column)) return 0.0;
        $dateColumn = DB::getSchemaBuilder()->hasColumn($table, 'transaction_date') ? 'transaction_date' : 'created_at';
        $q = DB::table($table)->whereBetween($dateColumn, [$start, $end]);
        return (float) $this->tenantFilter($q, $filters)->sum($column);
    }

    private function paymentTotal($start, $end, array $filters, string $method): float
    {
        if (!$this->tableExists('beauty_sale_payments')) return 0.0;
        $q = DB::table('beauty_sale_payments')->where('method', $method)->whereBetween('paid_on', [$start, $end]);
        return (float) $this->tenantFilter($q, $filters)->sum('amount');
    }

    private function paymentMix($start, $end, array $filters): array
    {
        if (!$this->tableExists('beauty_sale_payments')) return [];
        $q = DB::table('beauty_sale_payments')->select('method', DB::raw('SUM(amount) as total'))->whereBetween('paid_on', [$start, $end])->groupBy('method');
        return $this->tenantFilter($q, $filters)->get()->map(fn($r) => ['method' => $r->method, 'total' => (float) $r->total])->toArray();
    }

    private function dailyRevenueTrend($start, $end, array $filters): array
    {
        if (!$this->tableExists('beauty_sales')) return [];
        $q = DB::table('beauty_sales')->select(DB::raw('DATE(transaction_date) as date'), DB::raw('SUM(final_total) as total'))->whereBetween('transaction_date', [$start, $end])->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('date');
        return $this->tenantFilter($q, $filters)->get()->map(fn($r) => ['date' => $r->date, 'total' => (float) $r->total])->toArray();
    }

    private function topStaff($start, $end, array $filters): array
    {
        if (!$this->tableExists('beauty_sales')) return [];
        $q = DB::table('beauty_sales')->select('staff_id', DB::raw('SUM(final_total) as total'))->whereBetween('transaction_date', [$start, $end])->groupBy('staff_id')->orderByDesc('total')->limit(10);
        return $this->tenantFilter($q, $filters)->get()->toArray();
    }

    private function topServices($start, $end, array $filters): array
    {
        if (!$this->tableExists('beauty_sale_lines')) return [];
        $q = DB::table('beauty_sale_lines')->select('item_name', DB::raw('SUM(line_total) as total'))->where('item_type', 'service')->whereBetween('created_at', [$start, $end])->groupBy('item_name')->orderByDesc('total')->limit(10);
        return $q->get()->toArray();
    }

    private function commissionTotal($start, $end, array $filters): float
    {
        return $this->sumColumn('beauty_staff_commissions', 'commission_amount', $start, $end, $filters);
    }

    private function staffUtilization($start, $end, array $filters): float { return 0.0; }
    private function resourceUtilization($start, $end, array $filters): float { return 0.0; }
    private function hourlyAppointments($date, array $filters): array { return []; }
    private function upcomingAppointments(array $filters): array { return []; }
    private function staffAvailable(array $filters): int { return $this->safeCount('beauty_staff', $filters, now()->startOfYear(), now()->endOfYear(), 'active'); }
    private function roomsAvailable(array $filters): int { return $this->safeCount('beauty_rooms', $filters, now()->startOfYear(), now()->endOfYear(), 'available'); }
    private function staffSchedule($staffId): array { return []; }
}
