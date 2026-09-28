<?php

namespace Modules\Graphs\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GraphCustomerPaymentAnalyticsService
{
    public function paymentMethodSplit(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        string $period = 'daily',
        ?int $locationId = null
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);
        $period = $this->period($period);
        $buckets = $this->periodBuckets($start, $end, $period);

        $totals = $this->emptyPaymentTotals();
        $bucketValues = [];
        foreach ($buckets as $key => $label) {
            $bucketValues[$key] = array_merge(['period' => $label], $this->emptyPaymentTotals());
        }

        foreach ($this->salePaymentRows($businessId, $start, $end, $locationId) as $row) {
            $date = $this->parseDate($row->report_date ?? null);
            if (! $date) {
                continue;
            }
            [$bucketKey] = $this->bucketForDate($date, $period);
            if (! isset($bucketValues[$bucketKey])) {
                continue;
            }

            $method = $this->classifyPaymentMethod((string) ($row->method ?? ''));
            if ($method === null) {
                continue;
            }
            $amount = max((float) ($row->amount ?? 0), 0);
            $totals[$method] += $amount;
            $bucketValues[$bucketKey][$method] += $amount;
        }

        foreach ($this->creditSaleRows($businessId, $start, $end, $locationId) as $row) {
            $date = $this->parseDate($row->report_date ?? null);
            if (! $date) {
                continue;
            }
            [$bucketKey] = $this->bucketForDate($date, $period);
            if (! isset($bucketValues[$bucketKey])) {
                continue;
            }
            $amount = max((float) ($row->amount ?? 0), 0);
            $totals['credit'] += $amount;
            $bucketValues[$bucketKey]['credit'] += $amount;
        }

        $grandTotal = array_sum($totals);
        $paymentMethods = [
            ['key' => 'cash', 'label' => 'Cash'],
            ['key' => 'card', 'label' => 'Cards'],
            ['key' => 'credit', 'label' => 'Credit Sales'],
            ['key' => 'online_transfer', 'label' => 'Online Transfers'],
        ];
        foreach ($paymentMethods as &$method) {
            $method['amount'] = round((float) $totals[$method['key']], 4);
            $method['percentage'] = $grandTotal > 0
                ? round(((float) $totals[$method['key']] / $grandTotal) * 100, 2)
                : 0.0;
        }
        unset($method);

        $rows = [];
        foreach ($bucketValues as $bucket) {
            $rowTotal = (float) $bucket['cash'] + (float) $bucket['card'] + (float) $bucket['credit'] + (float) $bucket['online_transfer'];
            $rows[] = [
                'period' => $bucket['period'],
                'cash' => round((float) $bucket['cash'], 4),
                'card' => round((float) $bucket['card'], 4),
                'credit' => round((float) $bucket['credit'], 4),
                'online_transfer' => round((float) $bucket['online_transfer'], 4),
                'total' => round($rowTotal, 4),
            ];
        }

        return [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'methods' => $paymentMethods,
            'rows' => $rows,
            'summary' => [
                'cash' => round((float) $totals['cash'], 4),
                'card' => round((float) $totals['card'], 4),
                'credit' => round((float) $totals['credit'], 4),
                'online_transfer' => round((float) $totals['online_transfer'], 4),
                'total' => round((float) $grandTotal, 4),
            ],
        ];
    }

    public function topCreditCustomers(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        string $period = 'daily',
        ?int $locationId = null,
        int $limit = 10
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);
        $period = $this->period($period);
        $limit = max(1, min(25, $limit));

        if (! Schema::hasTable('transactions') || ! Schema::hasTable('contacts')) {
            return $this->emptyCreditResponse($start, $end, $period);
        }

        $query = DB::table('transactions as t')
            ->join('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end]);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if ($locationId && Schema::hasColumn('transactions', 'location_id')) {
            $query->where('t.location_id', $locationId);
        }
        $this->applyCreditSaleScope($query);

        $hasFleet = Schema::hasTable('fleets') && Schema::hasColumn('transactions', 'fleet_id');
        if ($hasFleet) {
            $query->leftJoin('fleets as f', 'f.id', '=', 't.fleet_id');
        }

        $select = [
            't.id',
            't.contact_id',
            't.transaction_date',
            't.final_total',
            'c.name as customer_name',
        ];
        if (Schema::hasColumn('contacts', 'supplier_business_name')) {
            $select[] = 'c.supplier_business_name';
        }
        if (Schema::hasColumn('contacts', 'contact_id')) {
            $select[] = 'c.contact_id as customer_code';
        }
        if (Schema::hasColumn('contacts', 'mobile')) {
            $select[] = 'c.mobile';
        }
        if ($hasFleet) {
            $select[] = 't.fleet_id';
            $select[] = 'f.vehicle_number';
        }

        $sales = $query->select($select)->orderBy('t.transaction_date')->orderBy('t.id')->get();
        $customers = [];
        $periodTotals = [];
        $allFleets = [];
        $totalAmount = 0.0;
        $transactionCount = 0;

        foreach ($sales as $sale) {
            $date = $this->parseDate($sale->transaction_date ?? null);
            if (! $date) {
                continue;
            }
            [$periodKey, $periodLabel] = $this->bucketForDate($date, $period);
            $customerId = (int) ($sale->contact_id ?? 0);
            if ($customerId <= 0) {
                continue;
            }
            $amount = max((float) ($sale->final_total ?? 0), 0);
            $displayName = trim((string) ($sale->customer_name ?? ''));
            if (isset($sale->supplier_business_name) && trim((string) $sale->supplier_business_name) !== '') {
                $displayName = trim((string) $sale->supplier_business_name);
            }
            if ($displayName === '') {
                $displayName = 'Customer #' . $customerId;
            }

            if (! isset($customers[$customerId])) {
                $customers[$customerId] = [
                    'customer_id' => $customerId,
                    'customer' => $displayName,
                    'customer_code' => (string) ($sale->customer_code ?? ''),
                    'mobile' => (string) ($sale->mobile ?? ''),
                    'amount' => 0.0,
                    'transactions' => 0,
                    'fleets' => [],
                    'periods' => [],
                ];
            }
            $customers[$customerId]['amount'] += $amount;
            $customers[$customerId]['transactions']++;
            $customers[$customerId]['periods'][$periodKey] = ($customers[$customerId]['periods'][$periodKey] ?? 0) + $amount;

            if ($hasFleet && ! empty($sale->fleet_id)) {
                $fleetKey = (int) $sale->fleet_id;
                $fleetLabel = trim((string) ($sale->vehicle_number ?? '')) ?: ('Fleet #' . $fleetKey);
                $customers[$customerId]['fleets'][$fleetKey] = $fleetLabel;
                $allFleets[$fleetKey] = true;
            }

            $periodTotals[$periodKey] = $periodLabel;
            $totalAmount += $amount;
            $transactionCount++;
        }

        $ranked = array_values($customers);
        usort($ranked, static function (array $a, array $b) {
            $cmp = ($b['amount'] <=> $a['amount']);
            return $cmp !== 0 ? $cmp : strcasecmp($a['customer'], $b['customer']);
        });
        $ranked = array_slice($ranked, 0, $limit);

        ksort($periodTotals);
        $periodKeys = array_keys($periodTotals);
        $periodLabels = array_values($periodTotals);
        $chartCustomers = [];
        $rows = [];
        $periodRows = [];

        foreach ($ranked as $index => $customer) {
            $fleetNames = array_values($customer['fleets']);
            natcasesort($fleetNames);
            $fleetNames = array_values($fleetNames);
            $periodValues = [];
            foreach ($periodKeys as $periodKey) {
                $periodValues[] = round((float) ($customer['periods'][$periodKey] ?? 0), 4);
                if ((float) ($customer['periods'][$periodKey] ?? 0) > 0) {
                    $periodRows[] = [
                        'period' => $periodTotals[$periodKey],
                        'customer' => $customer['customer'],
                        'amount' => round((float) $customer['periods'][$periodKey], 4),
                    ];
                }
            }

            $rows[] = [
                'rank' => $index + 1,
                'customer_id' => $customer['customer_id'],
                'customer' => $customer['customer'],
                'customer_code' => $customer['customer_code'],
                'mobile' => $customer['mobile'],
                'credit_sales' => round((float) $customer['amount'], 4),
                'transactions' => (int) $customer['transactions'],
                'fleet_count' => count($fleetNames),
                'fleets' => $fleetNames,
            ];
            $chartCustomers[] = [
                'customer' => $customer['customer'],
                'total' => round((float) $customer['amount'], 4),
                'period_values' => $periodValues,
            ];
        }

        return [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'period_labels' => $periodLabels,
            'customers' => $chartCustomers,
            'rows' => $rows,
            'period_rows' => $periodRows,
            'summary' => [
                'credit_sales' => round($totalAmount, 4),
                'customers' => count($customers),
                'transactions' => $transactionCount,
                'fleets' => count($allFleets),
            ],
        ];
    }

    private function salePaymentRows(int $businessId, Carbon $start, Carbon $end, ?int $locationId)
    {
        if (! Schema::hasTable('transaction_payments') || ! Schema::hasTable('transactions')) {
            return collect();
        }

        $q = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('tp.business_id', $businessId)
            ->whereBetween('t.transaction_date', [$start, $end])
            ->where('tp.amount', '>', 0)
            ->where(function ($scope) {
                $scope->where(function ($sell) {
                    $sell->where('t.type', 'sell')->where('t.status', 'final');
                })->orWhere(function ($settlement) {
                    $settlement->where('t.type', 'settlement')->where('t.status', 'final');
                });
            });

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $q->whereNull('t.deleted_at');
        }
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $q->whereNull('tp.deleted_at');
        }
        if (Schema::hasColumn('transaction_payments', 'is_return')) {
            $q->where(function ($ret) {
                $ret->whereNull('tp.is_return')->orWhere('tp.is_return', 0);
            });
        }
        if (Schema::hasColumn('transaction_payments', 'parent_id')) {
            $q->whereNull('tp.parent_id');
        }
        if ($locationId && Schema::hasColumn('transactions', 'location_id')) {
            $q->where('t.location_id', $locationId);
        }

        // Credit-sale transactions belong in the dedicated Credit Sales slice,
        // not again in Cash/Card/Transfer when they are collected later.
        $this->applyNotCreditSaleScope($q);

        $rows = $q->selectRaw('DATE(t.transaction_date) as report_date, tp.method, tp.amount')->get();
        $methodMap = $this->paymentMethodMap();
        foreach ($rows as $row) {
            $raw = trim((string) ($row->method ?? ''));
            if ($raw !== '' && ctype_digit($raw) && isset($methodMap[(int) $raw])) {
                $row->method = $methodMap[(int) $raw];
            }
        }
        return $rows;
    }

    private function creditSaleRows(int $businessId, Carbon $start, Carbon $end, ?int $locationId)
    {
        if (! Schema::hasTable('transactions')) {
            return collect();
        }
        $q = DB::table('transactions as t')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end]);
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $q->whereNull('t.deleted_at');
        }
        if ($locationId && Schema::hasColumn('transactions', 'location_id')) {
            $q->where('t.location_id', $locationId);
        }
        $this->applyCreditSaleScope($q);
        return $q->selectRaw('DATE(t.transaction_date) as report_date, SUM(COALESCE(t.final_total,0)) as amount')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get();
    }

    private function applyCreditSaleScope($query): void
    {
        $hasFlag = Schema::hasColumn('transactions', 'is_credit_sale');
        $hasSubType = Schema::hasColumn('transactions', 'sub_type');
        if ($hasFlag || $hasSubType) {
            $query->where(function ($credit) use ($hasFlag, $hasSubType) {
                if ($hasFlag) {
                    $credit->where('t.is_credit_sale', 1);
                    if ($hasSubType) {
                        $credit->orWhere('t.sub_type', 'credit_sale');
                    }
                } elseif ($hasSubType) {
                    $credit->where('t.sub_type', 'credit_sale');
                }
            });
            return;
        }
        if (Schema::hasColumn('transactions', 'payment_status')) {
            $query->whereIn('t.payment_status', ['due', 'partial']);
        }
    }

    private function applyNotCreditSaleScope($query): void
    {
        $hasFlag = Schema::hasColumn('transactions', 'is_credit_sale');
        $hasSubType = Schema::hasColumn('transactions', 'sub_type');
        if (! $hasFlag && ! $hasSubType) {
            return;
        }
        $query->where(function ($notCredit) use ($hasFlag, $hasSubType) {
            if ($hasFlag) {
                $notCredit->where(function ($flag) {
                    $flag->whereNull('t.is_credit_sale')->orWhere('t.is_credit_sale', 0);
                });
            }
            if ($hasSubType) {
                $notCredit->where(function ($sub) {
                    $sub->whereNull('t.sub_type')->orWhere('t.sub_type', '<>', 'credit_sale');
                });
            }
        });
    }

    private function paymentMethodMap(): array
    {
        if (! Schema::hasTable('payment_methods') || ! Schema::hasColumn('payment_methods', 'id') || ! Schema::hasColumn('payment_methods', 'name')) {
            return [];
        }
        return DB::table('payment_methods')->pluck('name', 'id')->mapWithKeys(static fn ($name, $id) => [(int) $id => (string) $name])->all();
    }

    private function classifyPaymentMethod(string $method): ?string
    {
        $value = strtolower(trim($method));
        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: '';
        if ($value === '') {
            return null;
        }

        // Do not treat settlement cash-deposit movements as online customer
        // payments; those are movements of cash already counted as Cash.
        if (in_array($value, ['cash deposit', 'cash deposited'], true)) {
            return null;
        }
        if (str_contains($value, 'online transfer')
            || str_contains($value, 'bank transfer')
            || str_contains($value, 'direct bank deposit')
            || str_contains($value, 'electronic transfer')
            || in_array($value, ['bank', 'online'], true)) {
            return 'online_transfer';
        }
        if (str_contains($value, 'card')
            || str_contains($value, 'visa')
            || str_contains($value, 'mastercard')
            || str_contains($value, 'master card')) {
            return 'card';
        }
        if ($value === 'cash' || str_contains($value, 'cash payment')) {
            return 'cash';
        }
        return null;
    }

    private function emptyPaymentTotals(): array
    {
        return ['cash' => 0.0, 'card' => 0.0, 'credit' => 0.0, 'online_transfer' => 0.0];
    }

    private function emptyCreditResponse(Carbon $start, Carbon $end, string $period): array
    {
        return [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'period_labels' => [],
            'customers' => [],
            'rows' => [],
            'period_rows' => [],
            'summary' => ['credit_sales' => 0.0, 'customers' => 0, 'transactions' => 0, 'fleets' => 0],
        ];
    }

    private function normaliseRange(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        if ($end->lt($start)) {
            $tmp = $start;
            $start = $end->copy()->startOfDay();
            $end = $tmp->copy()->endOfDay();
        }
        return [$start, $end];
    }

    private function period(string $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'daily';
    }

    private function periodBuckets(Carbon $start, Carbon $end, string $period): array
    {
        $buckets = [];
        if ($period === 'yearly') {
            $cursor = $start->copy()->startOfYear();
            $last = $end->copy()->startOfYear();
            while ($cursor->lte($last)) {
                $buckets[$cursor->format('Y')] = $cursor->format('Y');
                $cursor->addYear();
            }
            return $buckets;
        }
        if ($period === 'monthly') {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();
            while ($cursor->lte($last)) {
                $buckets[$cursor->format('Y-m')] = $cursor->format('M Y');
                $cursor->addMonth();
            }
            return $buckets;
        }
        if ($period === 'weekly') {
            $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
            $last = $end->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                $buckets[$key] = $cursor->format('d M Y') . ' - ' . $cursor->copy()->addDays(6)->format('d M Y');
                $cursor->addWeek();
            }
            return $buckets;
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $buckets[$cursor->format('Y-m-d')] = $cursor->format('d M Y');
            $cursor->addDay();
        }
        return $buckets;
    }

    private function bucketForDate(Carbon $date, string $period): array
    {
        if ($period === 'yearly') {
            return [$date->format('Y'), $date->format('Y')];
        }
        if ($period === 'monthly') {
            return [$date->format('Y-m'), $date->format('M Y')];
        }
        if ($period === 'weekly') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY);
            return [$start->format('Y-m-d'), $start->format('d M Y') . ' - ' . $start->copy()->addDays(6)->format('d M Y')];
        }
        return [$date->format('Y-m-d'), $date->format('d M Y')];
    }

    private function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
