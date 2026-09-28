<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId($request);
        $customerId = $this->customerId($request);
        $summary = $this->portalSummary($businessId, $customerId);

        return $this->success([
            'customer' => [
                'id' => $customerId,
                'name' => optional($this->customer($request))->name,
                'contact_id' => optional($this->customer($request))->contact_id,
            ],
            'summary' => $summary,
            'counts' => [
                'orders' => $this->countTable('customer_portal_orders', $businessId, $customerId),
                'deliveries' => $this->countTable('customer_portal_deliveries', $businessId, $customerId),
                'notifications' => $this->countTable('customer_portal_notifications', $businessId, $customerId),
            ],
        ]);
    }

    public function analytics(Request $request)
    {
        $businessId = $this->businessId($request);
        $customerId = $this->customerId($request);

        return $this->success([
            'summary' => $this->portalSummary($businessId, $customerId),
            'monthly_purchases' => $this->monthlyPurchases($businessId, $customerId),
            'monthly_payments' => $this->monthlyPayments($businessId, $customerId),
            'credit_utilization' => $this->creditUtilization($businessId, $customerId),
        ]);
    }

    public function creditSummary(Request $request)
    {
        return $this->success($this->portalSummary($this->businessId($request), $this->customerId($request)));
    }

    protected function countTable(string $table, int $businessId, int $customerId): int
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:countTable
        if (! Schema::hasTable($table)) {
            return 0;
        }

        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->where('business_id', $businessId);
        if (Schema::hasColumn($table, 'contact_id')) {
            $query->where('contact_id', $customerId);
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        return (int) $query->count();
    }

    protected function monthlyPurchases(int $businessId, int $customerId)
    {
        if (!Schema::hasTable('transactions')) {
            return [];
        }

        return DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereIn('type', ['sell', 'opening_balance'])
            ->whereNull('deleted_at')
            ->whereDate('transaction_date', '>=', now()->subMonths(12)->startOfMonth()->toDateString())
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(final_total) as amount, COUNT(*) as count")
            ->groupBy(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    protected function monthlyPayments(int $businessId, int $customerId)
    {
        if (!Schema::hasTable('transaction_payments')) {
            return [];
        }

        return DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('payment_for', $customerId)
            ->whereNull('deleted_at')
            ->whereDate('paid_on', '>=', now()->subMonths(12)->startOfMonth()->toDateString())
            ->selectRaw("DATE_FORMAT(paid_on, '%Y-%m') as month, SUM(amount) as amount, COUNT(*) as count")
            ->groupBy(DB::raw("DATE_FORMAT(paid_on, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    protected function creditUtilization(int $businessId, int $customerId): array
    {
        $summary = $this->portalSummary($businessId, $customerId);
        $limit = (float) $summary['credit_limit'];
        $outstanding = (float) $summary['outstanding'];
        $percentage = $limit > 0 ? round(($outstanding / $limit) * 100, 2) : 0;

        return [
            'used' => $outstanding,
            'limit' => $limit,
            'available' => (float) $summary['available_credit'],
            'percentage' => $percentage,
        ];
    }
}
