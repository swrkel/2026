<?php

namespace Modules\Customers\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

/**
 * CUS_SEP_007
 * Customers-owned dashboard data service.
 *
 * Keeps dashboard calculations short and isolated from Contact controllers.
 */
class CustomerDashboardService
{
    protected $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function dashboardData(int $businessId): array
    {
        $summary = $this->ledgerService->summary($businessId);

        return [
            'summary' => array_merge($summary, $this->customerCounts($businessId), $this->monthlyTotals($businessId)),
            'aging' => $this->ledgerService->agingSummary($businessId),
            'recent_customers' => $this->recentCustomers($businessId),
            'recent_transactions' => $this->recentTransactions($businessId),
            'status_summary' => $this->statusSummary($businessId),
            'credit_summary' => $this->creditSummary($businessId),
        ];
    }

    public function recentCustomers(int $businessId, int $limit = 10)
    {
        $query = $this->customerBaseQuery($businessId);

        return $query
            ->select($this->customerSelectColumns())
            ->orderByDesc($this->contactsHasColumn('created_at') ? 'created_at' : 'id')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function recentTransactions(int $businessId, int $limit = 10)
    {
        if (! $this->canUseTransactions()) {
            return collect();
        }

        return DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.type', $this->customerTransactionTypes())
            ->whereNull('transactions.deleted_at')
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.type',
                'transactions.payment_status',
                'transactions.final_total',
                'contacts.name as customer_name',
            ])
            ->orderByDesc('transactions.transaction_date')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();
    }

    protected function customerCounts(int $businessId): array
    {
        $query = $this->customerBaseQuery($businessId);

        $activeCustomers = $this->contactsHasColumn('active')
            ? (clone $query)->where('active', 1)->count()
            : (clone $query)->count();

        $inactiveCustomers = $this->contactsHasColumn('active')
            ? (clone $query)->where('active', 0)->count()
            : 0;

        $creditCustomers = $this->contactsHasColumn('credit_limit')
            ? (clone $query)->whereNotNull('credit_limit')->where('credit_limit', '>', 0)->count()
            : 0;

        return [
            'total_customers' => (clone $query)->count(),
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'credit_customers' => $creditCustomers,
        ];
    }

    protected function monthlyTotals(int $businessId): array
    {
        // CUS_OPT_001: use full datetime boundaries instead of DATE(column)
        // so MySQL can use indexes on transaction_date and paid_on.
        $start = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $end = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();

        $data = [
            'monthly_sales' => 0.0,
            'monthly_collections' => 0.0,
            'last_payment_date' => null,
        ];

        if ($this->canUseTransactions()) {
            $data['monthly_sales'] = (float) DB::table('transactions')
                ->where('business_id', $businessId)
                ->whereIn('type', ['sell', 'direct_customer_loan'])
                ->whereNull('deleted_at')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('final_total');
        }

        if ($this->canUseTransactionPayments()) {
            $data['monthly_collections'] = (float) DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('paid_on', [$start, $end])
                ->sum('amount');

            $data['last_payment_date'] = DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->orderByDesc('paid_on')
                ->value('paid_on');
        }

        return $data;
    }

    protected function statusSummary(int $businessId): array
    {
        $query = $this->customerBaseQuery($businessId);

        return [
            'active' => $this->contactsHasColumn('active') ? (clone $query)->where('active', 1)->count() : (clone $query)->count(),
            'inactive' => $this->contactsHasColumn('active') ? (clone $query)->where('active', 0)->count() : 0,
        ];
    }

    protected function creditSummary(int $businessId): array
    {
        $query = $this->customerBaseQuery($businessId);

        return [
            'credit_limit_total' => $this->contactsHasColumn('credit_limit') ? (float) (clone $query)->sum('credit_limit') : 0.0,
            'credit_customer_count' => $this->contactsHasColumn('credit_limit') ? (clone $query)->whereNotNull('credit_limit')->where('credit_limit', '>', 0)->count() : 0,
        ];
    }


    protected function customerBaseQuery(int $businessId)
    {
        $query = Customer::forBusiness($businessId)->customersOnly();

        if ($this->contactsHasColumn('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    protected function customerSelectColumns(): array
    {
        $columns = ['id'];

        foreach (['contact_id', 'name', 'mobile', 'email', 'credit_limit', 'active', 'created_at'] as $column) {
            if ($this->contactsHasColumn($column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    protected function contactsHasColumn(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $columns = [];
            if (Schema::hasTable('contacts')) {
                foreach (Schema::getColumnListing('contacts') as $name) {
                    $columns[$name] = true;
                }
            }
        }

        return isset($columns[$column]);
    }

    protected function canUseTransactions(): bool
    {
        return Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'business_id')
            && Schema::hasColumn('transactions', 'transaction_date')
            && Schema::hasColumn('transactions', 'final_total')
            && Schema::hasColumn('transactions', 'type');
    }

    protected function canUseTransactionPayments(): bool
    {
        return Schema::hasTable('transaction_payments')
            && Schema::hasColumn('transaction_payments', 'business_id')
            && Schema::hasColumn('transaction_payments', 'paid_on')
            && Schema::hasColumn('transaction_payments', 'amount');
    }

    protected function customerTransactionTypes(): array
    {
        return [
            'sell',
            'opening_balance',
            'advance_payment',
            'sell_return',
            'settlement',
                'direct_customer_loan',
            'cheque_return',
            'ledger_discount',
            /*
             | IS2052: journal entries counted here too.
             |
             | The ledger screen now includes type = 'ledger' rows. If the
             | dashboard did not, the balance shown on the dashboard would
             | disagree with the ledger beneath it - which is worse than either
             | being wrong on its own.
             */
            'ledger',
        ];
    }
}
