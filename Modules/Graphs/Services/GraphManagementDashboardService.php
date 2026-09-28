<?php

namespace Modules\Graphs\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GraphManagementDashboardService
{
    public function __construct(
        private GraphAnalyticsService $analytics,
        private GraphFinancialAnalyticsService $financial,
        private GraphOperationalAnalyticsService $operational
    ) {}

    public function dashboard(int $businessId, ?int $locationId = null): array
    {
        $today = now()->copy();
        $start = $today->copy()->startOfDay();
        $end = $today->copy()->endOfDay();
        $trendStart = $today->copy()->subDays(29)->startOfDay();

        $reconciliation = $this->financial->reconciliation($businessId, $start, $end, $locationId);
        $varianceData = $this->operational->dipVariance($businessId, $start, $end, 'daily', $locationId);
        $variance = (float) data_get($varianceData, 'summary.variance', 0);

        return [
            'as_of' => $today->format('Y-m-d H:i:s'),
            'kpis' => [
                'total_sales' => $this->totalSales($businessId, $start, $end, $locationId),
                'today_sold_liters' => $this->todayFuelLitres($businessId, $start, $end, $locationId),
                'outstanding_received' => $this->outstandingReceived($businessId, $start, $end, $locationId),
                'bank_deposited' => round((float) data_get($reconciliation, 'summary.bank_deposited', 0), 4),
            ],
            'tanks' => $this->analytics->tanks($businessId, $locationId),
            'sales_trend' => $this->salesTrend($businessId, $trendStart, $end, $locationId),
            'credit_risk' => $this->creditRisk($businessId, $today->copy()->endOfDay(), $locationId),
            'variance' => [
                'dip_stock' => round((float) data_get($varianceData, 'summary.dip_stock', 0), 3),
                'system_stock' => round((float) data_get($varianceData, 'summary.system_stock', 0), 3),
                'variance' => round($variance, 3),
                'fuel_loss' => round((float) data_get($varianceData, 'summary.fuel_loss', 0), 3),
                'reading_at' => data_get($varianceData, 'summary.reading_at'),
                'is_alert' => abs($variance) > 100,
                'threshold' => 100,
            ],
        ];
    }

    private function totalSales(int $businessId, Carbon $start, Carbon $end, ?int $locationId): float
    {
        if (! Schema::hasTable('transactions')) {
            return 0.0;
        }

        $q = DB::table('transactions as t')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end]);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $q->whereNull('t.deleted_at');
        }
        if ($locationId) {
            $q->where('t.location_id', $locationId);
        }

        return round((float) $q->sum('t.final_total'), 4);
    }

    private function todayFuelLitres(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $result = ['total' => 0.0, 'subcategories' => []];
        if (! Schema::hasTable('fuel_tanks') || ! Schema::hasTable('transactions') || ! Schema::hasTable('transaction_sell_lines') || ! Schema::hasTable('products')) {
            return $result;
        }

        $fuelIds = DB::table('fuel_tanks')
            ->where('business_id', $businessId)
            ->whereNotNull('product_id')
            ->when($locationId, static fn ($q, $locationId) => $q->where('location_id', $locationId))
            ->distinct()
            ->pluck('product_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (! $fuelIds) {
            return $result;
        }

        $q = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->whereIn('tsl.product_id', $fuelIds);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $q->whereNull('t.deleted_at');
        }
        if ($locationId) {
            $q->where('t.location_id', $locationId);
        }

        if (Schema::hasTable('categories')) {
            $q->leftJoin('categories as sc', 'sc.id', '=', 'p.sub_category_id');
            $nameExpr = "COALESCE(NULLIF(sc.name,''), NULLIF(p.name,''), 'Fuel')";
        } else {
            $nameExpr = "COALESCE(NULLIF(p.name,''), 'Fuel')";
        }

        $qtyExpr = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0)';
        $rows = $q->selectRaw("{$nameExpr} as subcategory, SUM({$qtyExpr}) as litres")
            ->groupBy('subcategory')
            ->orderByDesc('litres')
            ->get();

        foreach ($rows as $row) {
            $litres = round((float) $row->litres, 3);
            $result['subcategories'][] = [
                'name' => (string) $row->subcategory,
                'litres' => $litres,
            ];
            $result['total'] += $litres;
        }
        $result['total'] = round($result['total'], 3);

        return $result;
    }

    private function outstandingReceived(int $businessId, Carbon $start, Carbon $end, ?int $locationId): float
    {
        if (! Schema::hasTable('transaction_payments') || ! Schema::hasTable('transactions')) {
            return 0.0;
        }

        $q = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('tp.business_id', $businessId)
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '<', $start->toDateString())
            ->whereBetween('tp.paid_on', [$start, $end]);

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $q->whereNull('tp.deleted_at');
        }
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $q->whereNull('t.deleted_at');
        }
        if ($locationId) {
            $q->where('t.location_id', $locationId);
        }
        if (Schema::hasColumn('transaction_payments', 'is_advance')) {
            $q->where(function ($advance) {
                $advance->whereNull('tp.is_advance')->orWhere('tp.is_advance', 0);
            });
        }

        $amountExpr = Schema::hasColumn('transaction_payments', 'is_return')
            ? 'CASE WHEN COALESCE(tp.is_return,0)=1 THEN -COALESCE(tp.amount,0) ELSE COALESCE(tp.amount,0) END'
            : 'COALESCE(tp.amount,0)';

        return round((float) $q->selectRaw("SUM({$amountExpr}) as total")->value('total'), 4);
    }

    private function salesTrend(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $labels = [];
        $values = [];
        $byDate = [];

        if (Schema::hasTable('transactions')) {
            $q = DB::table('transactions as t')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereBetween('t.transaction_date', [$start, $end]);
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $q->whereNull('t.deleted_at');
            }
            if ($locationId) {
                $q->where('t.location_id', $locationId);
            }
            foreach ($q->selectRaw('DATE(t.transaction_date) as sale_date, SUM(COALESCE(t.final_total,0)) as total')->groupBy('sale_date')->get() as $row) {
                $byDate[(string) $row->sale_date] = round((float) $row->total, 4);
            }
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d M');
            $values[] = (float) ($byDate[$key] ?? 0);
            $cursor->addDay();
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => $labels,
            'data' => $values,
            'total' => round(array_sum($values), 4),
        ];
    }

    private function creditRisk(int $businessId, Carbon $asOf, ?int $locationId): array
    {
        $combined = [];
        foreach (['31_60', '61_90', 'over_90'] as $bucket) {
            $payload = $this->financial->debtorsAgeingCustomers($businessId, $asOf, $bucket, $locationId);
            foreach (($payload['rows'] ?? []) as $row) {
                $id = (int) ($row['customer_id'] ?? 0);
                $key = $id > 0 ? (string) $id : (($row['customer_code'] ?? '') . '|' . ($row['customer_name'] ?? ''));
                if (! isset($combined[$key])) {
                    $combined[$key] = [
                        'customer_id' => $id,
                        'customer_code' => (string) ($row['customer_code'] ?? ''),
                        'customer_name' => (string) ($row['customer_name'] ?? ''),
                        'mobile' => (string) ($row['mobile'] ?? ''),
                        'invoices' => 0,
                        'min_age_days' => 999999,
                        'max_age_days' => 0,
                        'outstanding' => 0.0,
                    ];
                }
                $combined[$key]['invoices'] += (int) ($row['invoices'] ?? 0);
                $combined[$key]['min_age_days'] = min($combined[$key]['min_age_days'], (int) ($row['min_age_days'] ?? 0));
                $combined[$key]['max_age_days'] = max($combined[$key]['max_age_days'], (int) ($row['max_age_days'] ?? 0));
                $combined[$key]['outstanding'] += (float) ($row['outstanding'] ?? 0);
            }
        }

        $rows = array_values($combined);
        foreach ($rows as &$row) {
            if ($row['min_age_days'] === 999999) {
                $row['min_age_days'] = 31;
            }
            $row['outstanding'] = round((float) $row['outstanding'], 4);
        }
        unset($row);

        usort($rows, static fn ($a, $b) => ($b['outstanding'] <=> $a['outstanding']) ?: strcasecmp($a['customer_name'], $b['customer_name']));

        return [
            'as_of' => $asOf->toDateString(),
            'customers' => count($rows),
            'total_outstanding' => round(array_sum(array_column($rows, 'outstanding')), 4),
            'rows' => $rows,
        ];
    }
}
