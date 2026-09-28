<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPortalAnalyticsController extends CustomerPortalController
{
    public function index(Request $request)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $customerId = (int) $customer->id;

        $summary = $this->summaryCards($businessId, $customerId, $customer);
        $purchaseTrend = $this->monthlyPurchaseTrend($businessId, $customerId);
        $paymentTrend = $this->monthlyPaymentTrend($businessId, $customerId);
        $topProducts = $this->topProducts($businessId, $customerId);
        $orderStatus = $this->orderStatusSummary($businessId, $customerId);
        $performance = $this->performanceSnapshot($businessId, $customerId, $summary, $purchaseTrend, $paymentTrend);

        return view('customers::portal.analytics', compact(
            'customer',
            'summary',
            'purchaseTrend',
            'paymentTrend',
            'topProducts',
            'orderStatus',
            'performance'
        ));
    }

    protected function summaryCards(int $businessId, int $customerId, $customer): array
    {
        $outstanding = $this->currentBalance($businessId, $customerId);
        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $availableCredit = $creditLimit > 0 ? max($creditLimit - max($outstanding, 0), 0) : 0;

        return [
            'current_outstanding' => $outstanding,
            'credit_limit' => $creditLimit,
            'available_credit' => $availableCredit,
            'credit_utilization' => $creditLimit > 0 ? min(round((max($outstanding, 0) / $creditLimit) * 100, 2), 999) : 0,
            'this_month_purchases' => $this->thisMonthPurchases($businessId, $customerId),
            'this_month_payments' => $this->thisMonthPayments($businessId, $customerId),
            'open_orders' => $this->openOrders($businessId, $customerId),
        ];
    }

    protected function currentBalance(int $businessId, int $customerId): float
    {
        if (Schema::hasTable('contact_ledgers')) {
            $balance = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
                ->value('balance');

            if ($balance !== null) {
                return (float) $balance;
            }
        }

        return $this->invoiceTotal($businessId, $customerId) - $this->paymentTotal($businessId, $customerId);
    }

    protected function thisMonthPurchases(int $businessId, int $customerId): float
    {
        if (!Schema::hasTable('transactions')) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->whereIn('type', $this->invoiceTypes())
            ->whereBetween('transaction_date', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
            ->sum('final_total');
    }

    protected function thisMonthPayments(int $businessId, int $customerId): float
    {
        if (!Schema::hasTable('transaction_payments')) {
            return 0.0;
        }

        return (float) DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereNull('transaction_payments.deleted_at')
            ->whereBetween('transaction_payments.paid_on', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
            ->where(function ($query) use ($customerId) {
                $query->where('transaction_payments.payment_for', $customerId)
                    ->orWhere('transactions.contact_id', $customerId);
            })
            ->sum('transaction_payments.amount');
    }

    protected function openOrders(int $businessId, int $customerId): int
    {
        if (!Schema::hasTable('transactions')) {
            return 0;
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereIn('type', $this->orderTypes());
                if (Schema::hasColumn('transactions', 'sub_type')) {
                    $q->orWhereIn('sub_type', $this->orderTypes());
                }
            });

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereNotIn('status', ['delivered', 'completed', 'cancelled', 'final']);
        }

        return (int) $query->count();
    }

    protected function monthlyPurchaseTrend(int $businessId, int $customerId): array
    {
        $months = $this->lastTwelveMonths();
        $data = [];
        foreach ($months as $month) {
            $data[$month] = ['month' => $month, 'amount' => 0.0, 'quantity' => 0.0];
        }

        if (!Schema::hasTable('transactions')) {
            return array_values($data);
        }

        $rows = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->whereIn('type', $this->invoiceTypes())
            ->whereDate('transaction_date', '>=', date('Y-m-01', strtotime('-11 months')))
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as ym, SUM(final_total) as amount")
            ->groupBy('ym')
            ->get();

        foreach ($rows as $row) {
            if (isset($data[$row->ym])) {
                $data[$row->ym]['amount'] = (float) $row->amount;
            }
        }

        if (Schema::hasTable('transaction_sell_lines') && Schema::hasColumn('transaction_sell_lines', 'quantity')) {
            $qtyRows = DB::table('transaction_sell_lines')
                ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->where('transactions.business_id', $businessId)
                ->where('transactions.contact_id', $customerId)
                ->whereNull('transactions.deleted_at')
                ->whereIn('transactions.type', $this->invoiceTypes())
                ->whereDate('transactions.transaction_date', '>=', date('Y-m-01', strtotime('-11 months')))
                ->selectRaw("DATE_FORMAT(transactions.transaction_date, '%Y-%m') as ym, SUM(transaction_sell_lines.quantity) as quantity")
                ->groupBy('ym')
                ->get();

            foreach ($qtyRows as $row) {
                if (isset($data[$row->ym])) {
                    $data[$row->ym]['quantity'] = (float) $row->quantity;
                }
            }
        }

        return array_values($data);
    }

    protected function monthlyPaymentTrend(int $businessId, int $customerId): array
    {
        $months = $this->lastTwelveMonths();
        $data = [];
        foreach ($months as $month) {
            $data[$month] = ['month' => $month, 'amount' => 0.0];
        }

        if (!Schema::hasTable('transaction_payments')) {
            return array_values($data);
        }

        $rows = DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereNull('transaction_payments.deleted_at')
            ->whereDate('transaction_payments.paid_on', '>=', date('Y-m-01', strtotime('-11 months')))
            ->where(function ($query) use ($customerId) {
                $query->where('transaction_payments.payment_for', $customerId)
                    ->orWhere('transactions.contact_id', $customerId);
            })
            ->selectRaw("DATE_FORMAT(transaction_payments.paid_on, '%Y-%m') as ym, SUM(transaction_payments.amount) as amount")
            ->groupBy('ym')
            ->get();

        foreach ($rows as $row) {
            if (isset($data[$row->ym])) {
                $data[$row->ym]['amount'] = (float) $row->amount;
            }
        }

        return array_values($data);
    }

    protected function topProducts(int $businessId, int $customerId): array
    {
        if (!Schema::hasTable('transaction_sell_lines') || !Schema::hasTable('transactions')) {
            return [];
        }

        $amountExpression = '0';
        if (Schema::hasColumn('transaction_sell_lines', 'unit_price_inc_tax')) {
            $amountExpression = 'SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax)';
        } elseif (Schema::hasColumn('transaction_sell_lines', 'unit_price')) {
            $amountExpression = 'SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price)';
        } elseif (Schema::hasColumn('transaction_sell_lines', 'line_total')) {
            $amountExpression = 'SUM(transaction_sell_lines.line_total)';
        }

        $query = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $businessId)
            ->where('transactions.contact_id', $customerId)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.type', $this->invoiceTypes())
            ->whereDate('transactions.transaction_date', '>=', date('Y-m-01', strtotime('-12 months')))
            ->selectRaw('transaction_sell_lines.product_id, SUM(transaction_sell_lines.quantity) as quantity, ' . $amountExpression . ' as amount')
            ->groupBy('transaction_sell_lines.product_id')
            ->orderByDesc('amount')
            ->limit(10);

        if (Schema::hasTable('products')) {
            $query->leftJoin('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->addSelect(DB::raw('COALESCE(products.name, CONCAT("Product #", transaction_sell_lines.product_id)) as product_name'));
        } else {
            $query->addSelect(DB::raw('CONCAT("Product #", transaction_sell_lines.product_id) as product_name'));
        }

        return $query->get()->map(function ($row) {
            return [
                'product' => $row->product_name,
                'quantity' => (float) $row->quantity,
                'amount' => (float) $row->amount,
            ];
        })->toArray();
    }

    protected function orderStatusSummary(int $businessId, int $customerId): array
    {
        $statuses = ['draft', 'submitted', 'approved', 'processing', 'dispatched', 'delivered', 'cancelled'];
        $data = [];
        foreach ($statuses as $status) {
            $data[$status] = ['status' => ucwords($status), 'count' => 0];
        }

        if (!Schema::hasTable('transactions')) {
            return array_values($data);
        }

        $rows = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereIn('type', $this->orderTypes());
                if (Schema::hasColumn('transactions', 'sub_type')) {
                    $q->orWhereIn('sub_type', $this->orderTypes());
                }
            })
            ->selectRaw('LOWER(COALESCE(status, "submitted")) as order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->get();

        foreach ($rows as $row) {
            $key = strtolower($row->order_status ?: 'submitted');
            if (!isset($data[$key])) {
                $data[$key] = ['status' => ucwords(str_replace('_', ' ', $key)), 'count' => 0];
            }
            $data[$key]['count'] += (int) $row->total;
        }

        return array_values($data);
    }

    protected function performanceSnapshot(int $businessId, int $customerId, array $summary, array $purchaseTrend, array $paymentTrend): array
    {
        $totalPurchases = $this->invoiceTotal($businessId, $customerId);
        $totalPayments = $this->paymentTotal($businessId, $customerId);
        $monthsWithPurchases = max(count(array_filter($purchaseTrend, function ($row) { return (float) $row['amount'] > 0; })), 1);
        $monthsWithPayments = max(count(array_filter($paymentTrend, function ($row) { return (float) $row['amount'] > 0; })), 1);

        return [
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'average_monthly_purchase' => $totalPurchases / $monthsWithPurchases,
            'average_monthly_payment' => $totalPayments / $monthsWithPayments,
            'credit_utilization' => $summary['credit_utilization'] ?? 0,
        ];
    }

    protected function invoiceTotal(int $businessId, int $customerId): float
    {
        if (!Schema::hasTable('transactions')) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereIn('type', $this->invoiceTypes())
            ->whereNull('deleted_at')
            ->sum('final_total');
    }

    protected function paymentTotal(int $businessId, int $customerId): float
    {
        if (!Schema::hasTable('transaction_payments')) {
            return 0.0;
        }

        return (float) DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereNull('transaction_payments.deleted_at')
            ->where(function ($query) use ($customerId) {
                $query->where('transaction_payments.payment_for', $customerId)
                    ->orWhere('transactions.contact_id', $customerId);
            })
            ->sum('transaction_payments.amount');
    }

    protected function lastTwelveMonths(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = date('Y-m', strtotime('-' . $i . ' months'));
        }
        return $months;
    }

    protected function invoiceTypes(): array
    {
        return ['sell', 'opening_balance', 'direct_customer_loan', 'security_deposit'];
    }

    protected function orderTypes(): array
    {
        return ['sell_order', 'sales_order', 'order', 'customer_order'];
    }
}
