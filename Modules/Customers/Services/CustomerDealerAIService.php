<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

class CustomerDealerAIService
{
    protected CustomerLedgerService $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function dashboard(int $businessId, Customer $customer): array
    {
        $summary = $this->ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $orders = method_exists($this->ledgerService, 'portalOrders')
            ? $this->ledgerService->portalOrders($businessId, (int) $customer->id, 20)
            : collect();
        $outstanding = method_exists($this->ledgerService, 'portalOutstandingInvoices')
            ? $this->ledgerService->portalOutstandingInvoices($businessId, (int) $customer->id, 20)
            : collect();
        $notifications = method_exists($this->ledgerService, 'portalNotifications')
            ? $this->ledgerService->portalNotifications($businessId, (int) $customer->id, 20)
            : collect();

        $creditLimit = (float) ($summary['credit_limit'] ?? 0);
        $outstandingAmount = (float) ($summary['outstanding_amount'] ?? $summary['current_balance'] ?? 0);
        $utilization = $creditLimit > 0 ? round(($outstandingAmount / $creditLimit) * 100, 2) : 0;

        return [
            'summary' => $summary,
            'credit_utilization' => $utilization,
            'open_orders' => $orders->filter(function ($row) {
                return !in_array(strtolower((string) ($row->status ?? '')), ['delivered', 'completed', 'cancelled']);
            })->count(),
            'overdue_invoices' => $outstanding->filter(function ($row) {
                return (int) ($row->days_outstanding ?? 0) > 30;
            })->count(),
            'unread_notifications' => $notifications->filter(function ($row) {
                return strtolower((string) ($row->status ?? '')) === 'unread';
            })->count(),
            'recommendations' => $this->recommendations($businessId, $customer),
        ];
    }

    public function answer(int $businessId, Customer $customer, string $question): array
    {
        $question = trim($question);
        $q = strtolower($question);
        $summary = $this->ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $outstanding = (float) ($summary['outstanding_amount'] ?? $summary['current_balance'] ?? 0);
        $creditLimit = (float) ($summary['credit_limit'] ?? 0);
        $availableCredit = (float) ($summary['available_credit'] ?? 0);

        if ($question === '') {
            return [
                'title' => 'Ask a question',
                'answer' => 'Please enter a question about your statement, outstanding balance, orders, deliveries, payments, or rewards.',
                'cards' => [],
            ];
        }

        if ($this->containsAny($q, ['outstanding', 'balance', 'due'])) {
            return [
                'title' => 'Outstanding Balance',
                'answer' => 'Your current outstanding balance is ' . $this->money($outstanding) . '.',
                'cards' => [
                    ['label' => 'Outstanding', 'value' => $this->money($outstanding)],
                    ['label' => 'Credit Limit', 'value' => $this->money($creditLimit)],
                    ['label' => 'Available Credit', 'value' => $this->money($availableCredit)],
                ],
            ];
        }

        if ($this->containsAny($q, ['credit', 'limit', 'available'])) {
            $utilization = $creditLimit > 0 ? round(($outstanding / $creditLimit) * 100, 2) : 0;
            return [
                'title' => 'Credit Summary',
                'answer' => 'Your available credit is ' . $this->money($availableCredit) . '. Current utilization is ' . number_format($utilization, 2) . '%.',
                'cards' => [
                    ['label' => 'Credit Limit', 'value' => $this->money($creditLimit)],
                    ['label' => 'Used Credit', 'value' => $this->money($outstanding)],
                    ['label' => 'Available Credit', 'value' => $this->money($availableCredit)],
                ],
            ];
        }

        if ($this->containsAny($q, ['invoice', 'overdue'])) {
            $rows = $this->ledgerService->portalOutstandingInvoices($businessId, (int) $customer->id, 100);
            $overdue = $rows->filter(function ($row) {
                return (int) ($row->days_outstanding ?? 0) > 30;
            });

            return [
                'title' => 'Outstanding Invoices',
                'answer' => 'You have ' . $rows->count() . ' outstanding invoice(s). ' . $overdue->count() . ' invoice(s) are older than 30 days.',
                'cards' => [
                    ['label' => 'Outstanding Invoices', 'value' => (string) $rows->count()],
                    ['label' => 'Over 30 Days', 'value' => (string) $overdue->count()],
                    ['label' => 'Total Outstanding', 'value' => $this->money($rows->sum('balance'))],
                ],
            ];
        }

        if ($this->containsAny($q, ['payment', 'paid', 'last payment'])) {
            $rows = $this->ledgerService->portalPayments($businessId, (int) $customer->id, 25);
            $last = $rows->first();
            return [
                'title' => 'Payment Summary',
                'answer' => $last
                    ? 'Your latest payment was ' . $this->money((float) $last->amount) . ' on ' . $this->date($last->paid_on) . '.'
                    : 'No payment details were found for your account.',
                'cards' => [
                    ['label' => 'Payment Count', 'value' => (string) $rows->count()],
                    ['label' => 'Recent Payments Total', 'value' => $this->money($rows->sum('amount'))],
                    ['label' => 'Last Payment Date', 'value' => $last ? $this->date($last->paid_on) : '-'],
                ],
            ];
        }

        if ($this->containsAny($q, ['order', 'pending order'])) {
            $rows = $this->ledgerService->portalOrders($businessId, (int) $customer->id, 100);
            $open = $rows->filter(function ($row) {
                return !in_array(strtolower((string) ($row->status ?? '')), ['delivered', 'completed', 'cancelled']);
            });
            return [
                'title' => 'Order Summary',
                'answer' => 'You have ' . $open->count() . ' open order(s) out of ' . $rows->count() . ' recent order(s).',
                'cards' => [
                    ['label' => 'Open Orders', 'value' => (string) $open->count()],
                    ['label' => 'Recent Orders', 'value' => (string) $rows->count()],
                    ['label' => 'Recent Order Value', 'value' => $this->money($rows->sum('final_total'))],
                ],
            ];
        }

        if ($this->containsAny($q, ['delivery', 'deliveries', 'dispatch', 'vehicle'])) {
            $delivery = $this->deliverySummary($businessId, (int) $customer->id);
            return [
                'title' => 'Delivery Summary',
                'answer' => 'You have ' . $delivery['in_transit'] . ' delivery item(s) in transit and ' . $delivery['delivered_month'] . ' delivered this month.',
                'cards' => [
                    ['label' => 'Pending', 'value' => (string) $delivery['pending']],
                    ['label' => 'In Transit', 'value' => (string) $delivery['in_transit']],
                    ['label' => 'Delivered This Month', 'value' => (string) $delivery['delivered_month']],
                ],
            ];
        }

        if ($this->containsAny($q, ['reward', 'loyalty', 'points', 'campaign', 'incentive'])) {
            $points = $this->rewardSummary($businessId, (int) $customer->id);
            return [
                'title' => 'Rewards & Incentives',
                'answer' => 'Your available reward points are ' . number_format($points['available_points'], 2) . '. You have ' . $points['active_campaigns'] . ' active campaign(s).',
                'cards' => [
                    ['label' => 'Available Points', 'value' => number_format($points['available_points'], 2)],
                    ['label' => 'Active Campaigns', 'value' => (string) $points['active_campaigns']],
                    ['label' => 'Program Level', 'value' => $points['program_level']],
                ],
            ];
        }

        return [
            'title' => 'Dealer Assistant',
            'answer' => 'I can help with outstanding balance, credit limit, invoices, payments, orders, deliveries, rewards, campaigns, and statements. Please ask one of those questions.',
            'cards' => [
                ['label' => 'Outstanding', 'value' => $this->money($outstanding)],
                ['label' => 'Available Credit', 'value' => $this->money($availableCredit)],
                ['label' => 'Last Payment', 'value' => !empty($summary['last_payment_date']) ? $this->date($summary['last_payment_date']) : '-'],
            ],
        ];
    }

    public function recommendations(int $businessId, Customer $customer): array
    {
        $summary = $this->ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $creditLimit = (float) ($summary['credit_limit'] ?? 0);
        $outstanding = (float) ($summary['outstanding_amount'] ?? 0);
        $available = (float) ($summary['available_credit'] ?? 0);
        $utilization = $creditLimit > 0 ? ($outstanding / $creditLimit) * 100 : 0;

        $items = [];

        if ($creditLimit > 0 && $utilization >= 85) {
            $items[] = [
                'type' => 'Credit Alert',
                'message' => 'Credit usage is high at ' . number_format($utilization, 2) . '%. Please review payments before placing larger orders.',
                'level' => 'danger',
            ];
        } elseif ($creditLimit > 0 && $utilization >= 60) {
            $items[] = [
                'type' => 'Credit Notice',
                'message' => 'Credit usage is at ' . number_format($utilization, 2) . '%. Available credit is ' . $this->money($available) . '.',
                'level' => 'warning',
            ];
        } else {
            $items[] = [
                'type' => 'Credit Status',
                'message' => 'Credit position looks healthy. Available credit is ' . $this->money($available) . '.',
                'level' => 'success',
            ];
        }

        $overdue = $this->ledgerService->portalOutstandingInvoices($businessId, (int) $customer->id, 100)
            ->filter(function ($row) {
                return (int) ($row->days_outstanding ?? 0) > 30;
            });

        if ($overdue->count() > 0) {
            $items[] = [
                'type' => 'Overdue Invoice',
                'message' => $overdue->count() . ' invoice(s) are older than 30 days. Consider prioritising these payments.',
                'level' => 'danger',
            ];
        }

        $orders = $this->ledgerService->portalOrders($businessId, (int) $customer->id, 50);
        $openOrders = $orders->filter(function ($row) {
            return !in_array(strtolower((string) ($row->status ?? '')), ['delivered', 'completed', 'cancelled']);
        })->count();

        if ($openOrders > 0) {
            $items[] = [
                'type' => 'Order Follow-up',
                'message' => 'You have ' . $openOrders . ' open order(s). Check delivery status before creating duplicate orders.',
                'level' => 'info',
            ];
        }

        if (count($items) < 4) {
            $items[] = [
                'type' => 'Statement Review',
                'message' => 'Review your statement regularly to keep credit, payments, and invoices aligned.',
                'level' => 'info',
            ];
        }

        return $items;
    }

    protected function deliverySummary(int $businessId, int $customerId): array
    {
        $result = ['pending' => 0, 'in_transit' => 0, 'delivered_month' => 0];

        foreach (['customer_portal_deliveries', 'dealer_deliveries', 'distribution_deliveries'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $customerId);
            } elseif (Schema::hasColumn($table, 'customer_id')) {
                $query->where('customer_id', $customerId);
            }

            if (Schema::hasColumn($table, 'status')) {
                $result['pending'] = (clone $query)->whereIn('status', ['pending', 'approved', 'loaded'])->count();
                $result['in_transit'] = (clone $query)->whereIn('status', ['dispatched', 'in_transit', 'arrived'])->count();
                $deliveredQuery = (clone $query)->whereIn('status', ['delivered', 'completed']);
                if (Schema::hasColumn($table, 'delivered_at')) {
                    $deliveredQuery->whereMonth('delivered_at', date('m'))->whereYear('delivered_at', date('Y'));
                }
                $result['delivered_month'] = $deliveredQuery->count();
            }

            return $result;
        }

        return $result;
    }

    protected function rewardSummary(int $businessId, int $customerId): array
    {
        $points = 0.0;
        foreach (['customer_reward_points', 'dealer_reward_points'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $customerId);
            } elseif (Schema::hasColumn($table, 'customer_id')) {
                $query->where('customer_id', $customerId);
            }
            if (Schema::hasColumn($table, 'points_balance')) {
                $points = (float) $query->sum('points_balance');
            } elseif (Schema::hasColumn($table, 'points')) {
                $points = (float) $query->sum('points');
            }
            break;
        }

        $activeCampaigns = 0;
        foreach (['customer_campaigns', 'dealer_campaigns'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn($table, 'status')) {
                $query->whereIn('status', ['active', 'published']);
            }
            $activeCampaigns = (int) $query->count();
            break;
        }

        return [
            'available_points' => $points,
            'active_campaigns' => $activeCampaigns,
            'program_level' => $points >= 10000 ? 'Gold' : ($points >= 5000 ? 'Silver' : 'Standard'),
        ];
    }

    protected function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (strpos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function money(float $value): string
    {
        return number_format($value, 2);
    }

    protected function date($value): string
    {
        return !empty($value) ? date('Y-m-d', strtotime($value)) : '-';
    }
}
