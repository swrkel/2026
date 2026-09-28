<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerHealthScoreService
{
    public function score(int $businessId, int $customerId, $customer = null): array
    {
        $outstanding = $this->currentBalance($businessId, $customerId);
        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $creditUtilization = $creditLimit > 0 ? min(round((max($outstanding, 0) / $creditLimit) * 100, 2), 999) : 0;

        $scores = [
            'credit' => $this->creditScore($creditUtilization, $outstanding, $creditLimit),
            'payment' => $this->paymentScore($businessId, $customerId),
            'purchase' => $this->purchaseScore($businessId, $customerId),
            'delivery' => $this->deliveryScore($businessId, $customerId),
            'loyalty' => $this->loyaltyScore($businessId, $customerId),
            'growth' => $this->growthScore($businessId, $customerId),
        ];

        $overall = (int) round(array_sum($scores) / max(count($scores), 1));

        return [
            'overall' => $overall,
            'overall_label' => $this->label($overall),
            'overall_class' => $this->cssClass($overall),
            'scores' => $scores,
            'labels' => array_map([$this, 'label'], $scores),
            'classes' => array_map([$this, 'cssClass'], $scores),
            'metrics' => [
                'outstanding' => $outstanding,
                'credit_limit' => $creditLimit,
                'available_credit' => $creditLimit > 0 ? max($creditLimit - max($outstanding, 0), 0) : 0,
                'credit_utilization' => $creditUtilization,
                'this_month_purchases' => $this->purchaseTotal($businessId, $customerId, date('Y-m-01'), date('Y-m-t')),
                'last_month_purchases' => $this->purchaseTotal($businessId, $customerId, date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))),
                'this_month_payments' => $this->paymentTotal($businessId, $customerId, date('Y-m-01'), date('Y-m-t')),
                'open_orders' => $this->openOrders($businessId, $customerId),
            ],
            'insights' => $this->insights($scores, $creditUtilization, $outstanding, $creditLimit),
            'timeline' => $this->timeline($businessId, $customerId),
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

        return $this->invoiceTotal($businessId, $customerId) - $this->paymentTotal($businessId, $customerId, null, null);
    }

    protected function invoiceTotal(int $businessId, int $customerId): float
    {
        if (!Schema::hasTable('transactions')) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->whereIn('type', ['sell', 'property_sell', 'route_operation'])
            ->sum('final_total');
    }

    protected function paymentTotal(int $businessId, int $customerId, ?string $from, ?string $to): float
    {
        if (!Schema::hasTable('transaction_payments')) {
            return 0.0;
        }

        $query = DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereNull('transaction_payments.deleted_at')
            ->where(function ($q) use ($customerId) {
                $q->where('transaction_payments.payment_for', $customerId)
                  ->orWhere('transactions.contact_id', $customerId);
            });

        if ($from && $to) {
            $query->whereBetween('transaction_payments.paid_on', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }

        return (float) $query->sum('transaction_payments.amount');
    }

    protected function purchaseTotal(int $businessId, int $customerId, string $from, string $to): float
    {
        if (!Schema::hasTable('transactions')) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->whereIn('type', ['sell', 'property_sell', 'route_operation'])
            ->whereBetween('transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->sum('final_total');
    }

    protected function openOrders(int $businessId, int $customerId): int
    {
        if (!Schema::hasTable('transactions')) {
            return 0;
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at');

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereNotIn('status', ['final', 'completed', 'delivered', 'cancelled']);
        }

        return (int) $query->count();
    }

    protected function creditScore(float $utilization, float $outstanding, float $creditLimit): int
    {
        if ($creditLimit <= 0) {
            return $outstanding <= 0 ? 85 : 65;
        }

        if ($utilization <= 50) return 95;
        if ($utilization <= 70) return 85;
        if ($utilization <= 85) return 72;
        if ($utilization <= 100) return 55;
        return 35;
    }

    protected function paymentScore(int $businessId, int $customerId): int
    {
        $thisMonth = $this->paymentTotal($businessId, $customerId, date('Y-m-01'), date('Y-m-t'));
        $lastMonth = $this->paymentTotal($businessId, $customerId, date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month')));

        if ($thisMonth <= 0 && $lastMonth <= 0) return 60;
        if ($thisMonth >= $lastMonth) return 86;
        return max(55, (int) round(70 + (($thisMonth - $lastMonth) / max($lastMonth, 1)) * 20));
    }

    protected function purchaseScore(int $businessId, int $customerId): int
    {
        $thisMonth = $this->purchaseTotal($businessId, $customerId, date('Y-m-01'), date('Y-m-t'));
        $lastMonth = $this->purchaseTotal($businessId, $customerId, date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month')));

        if ($thisMonth <= 0 && $lastMonth <= 0) return 55;
        if ($thisMonth >= $lastMonth) return 88;
        return max(50, (int) round(70 + (($thisMonth - $lastMonth) / max($lastMonth, 1)) * 20));
    }

    protected function deliveryScore(int $businessId, int $customerId): int
    {
        $tables = ['customer_deliveries', 'dealer_deliveries'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $total = DB::table($table)->where('business_id', $businessId)->where('customer_id', $customerId)->count();
                if ($total <= 0) return 75;
                $delivered = DB::table($table)->where('business_id', $businessId)->where('customer_id', $customerId)->whereIn('status', ['delivered', 'completed'])->count();
                return (int) max(45, min(98, round(($delivered / max($total, 1)) * 100)));
            }
        }
        return 75;
    }

    protected function loyaltyScore(int $businessId, int $customerId): int
    {
        if (Schema::hasTable('customer_loyalty_points')) {
            $points = (float) DB::table('customer_loyalty_points')->where('business_id', $businessId)->where('customer_id', $customerId)->sum('points');
            return (int) max(60, min(98, 60 + ($points / 100)));
        }
        return 72;
    }

    protected function growthScore(int $businessId, int $customerId): int
    {
        $thisQuarter = $this->purchaseTotal($businessId, $customerId, date('Y-m-01', strtotime('-2 months')), date('Y-m-t'));
        $previousQuarter = $this->purchaseTotal($businessId, $customerId, date('Y-m-01', strtotime('-5 months')), date('Y-m-t', strtotime('-3 months')));
        if ($thisQuarter <= 0 && $previousQuarter <= 0) return 60;
        if ($thisQuarter >= $previousQuarter) return 86;
        return max(45, (int) round(70 + (($thisQuarter - $previousQuarter) / max($previousQuarter, 1)) * 20));
    }

    protected function label(int $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Average';
        if ($score >= 40) return 'Warning';
        return 'Critical';
    }

    protected function cssClass(int $score): string
    {
        if ($score >= 75) return 'dd-health-good';
        if ($score >= 60) return 'dd-health-average';
        if ($score >= 40) return 'dd-health-warning';
        return 'dd-health-critical';
    }

    protected function insights(array $scores, float $utilization, float $outstanding, float $creditLimit): array
    {
        $insights = [];
        if ($utilization >= 85) {
            $insights[] = 'Credit utilization is high. Consider making a payment or requesting a credit limit review.';
        }
        if (($scores['payment'] ?? 0) < 60) {
            $insights[] = 'Payment health is below target. Review outstanding invoices and payment timing.';
        }
        if (($scores['purchase'] ?? 0) < 60) {
            $insights[] = 'Purchase activity has reduced. Review recent orders and demand planning.';
        }
        if (($scores['growth'] ?? 0) >= 75) {
            $insights[] = 'Growth health is positive. Current purchase trend is stable or improving.';
        }
        if (empty($insights)) {
            $insights[] = 'Business health is stable. Continue monitoring credit, payment and purchase trends.';
        }
        return $insights;
    }

    protected function timeline(int $businessId, int $customerId): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $from = date('Y-m-01', strtotime($month . '-01'));
            $to = date('Y-m-t', strtotime($month . '-01'));
            $purchase = $this->purchaseTotal($businessId, $customerId, $from, $to);
            $payment = $this->paymentTotal($businessId, $customerId, $from, $to);
            $months[] = [
                'month' => $month,
                'purchase' => $purchase,
                'payment' => $payment,
                'score' => (int) min(95, max(45, 65 + (($purchase + $payment) > 0 ? 15 : 0))),
            ];
        }
        return $months;
    }
}
