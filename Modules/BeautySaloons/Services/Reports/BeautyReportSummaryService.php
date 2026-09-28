<?php

namespace Modules\BeautySaloons\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BeautyReportSummaryService
{
    public function dashboard(array $filters): array
    {
        return [
            'appointments' => $this->count('bs_appointments', $filters, 'appointment_date'),
            'customers' => $this->count('bs_customers', $filters, 'created_at'),
            'sales_total' => $this->sum('bs_sales', 'total_amount', $filters, 'sale_date'),
            'retail_total' => $this->sum('beauty_salon_retail_sales', 'net_total', $filters, 'sale_date'),
            'payments_total' => $this->sum('bs_payments', 'amount', $filters, 'paid_on'),
            'voucher_outstanding' => $this->sum('bs_gift_vouchers', 'balance_amount', $filters, 'created_at'),
            'loyalty_points' => $this->sum('bs_loyalty_transactions', 'points', $filters, 'created_at'),
            'stock_movements' => $this->count('beauty_salon_stock_movements', $filters, 'transaction_date'),
        ];
    }

    public function rows(string $table, array $filters, string $dateColumn = 'created_at', int $limit = 500)
    {
        if (!Schema::hasTable($table)) {
            return collect();
        }
        $query = DB::table($table)->latest($dateColumn);
        if (Schema::hasColumn($table, $dateColumn)) {
            $this->applyDate($query, $filters, $dateColumn);
        }
        if (!empty($filters['business_location_id']) && Schema::hasColumn($table, 'business_location_id')) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (!empty($filters['staff_id']) && Schema::hasColumn($table, 'staff_id')) {
            $query->where('staff_id', $filters['staff_id']);
        }
        if (!empty($filters['customer_id']) && Schema::hasColumn($table, 'customer_id')) {
            $query->where('customer_id', $filters['customer_id']);
        }
        return $query->limit($limit)->get();
    }

    public function sum(string $table, string $column, array $filters, string $dateColumn = 'created_at'): float
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0.0;
        }
        $query = DB::table($table);
        if (Schema::hasColumn($table, $dateColumn)) {
            $this->applyDate($query, $filters, $dateColumn);
        }
        return (float) $query->sum($column);
    }

    public function count(string $table, array $filters, string $dateColumn = 'created_at'): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }
        $query = DB::table($table);
        if (Schema::hasColumn($table, $dateColumn)) {
            $this->applyDate($query, $filters, $dateColumn);
        }
        return (int) $query->count();
    }

    protected function applyDate($query, array $filters, string $column)
    {
        if (!empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }
        return $query;
    }
}
