<?php

namespace Modules\POS\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSReportService
{
    public function dashboard(array $filters = []): array
    {
        [$from, $to] = $this->dateRange($filters);

        return [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => $this->summary($from, $to),
            'daily_sales' => $this->dailySales($from, $to),
            'payment_summary' => $this->paymentSummary($from, $to),
            'item_sales' => $this->itemSales($from, $to),
            'category_sales' => $this->categorySales($from, $to),
            'cashier_sales' => $this->cashierSales($from, $to),
            'stock_valuation' => $this->stockValuation(),
            'low_stock' => $this->lowStock(),
        ];
    }

    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?: now()->startOfDay();
        $to = $to ?: now()->endOfDay();

        $sales = $this->salesBase($from, $to);
        $returns = $this->returnsBase($from, $to);
        $payments = $this->paymentsBase($from, $to);

        $salesTotal = (clone $sales)->sum($this->column('pos_sales', 'total_amount', 'final_total'));
        $returnsTotal = $this->tableExists('pos_sale_returns') ? (clone $returns)->sum('total_amount') : 0;
        $paymentTotal = $this->tableExists('pos_payments') ? (clone $payments)->sum('amount') : 0;
        $grossProfit = $this->grossProfit($from, $to);

        return [
            'sales_count' => (clone $sales)->count(),
            'sales_total' => $salesTotal,
            'payments_total' => $paymentTotal,
            'returns_total' => $returnsTotal,
            'net_sales' => $salesTotal - $returnsTotal,
            'gross_profit' => $grossProfit,
            'open_shifts' => $this->openShiftCount(),
            'stock_value' => $this->stockValue(),
            'low_stock_count' => count($this->lowStock()),
        ];
    }

    public function dailySales(Carbon $from, Carbon $to): array
    {
        if (!$this->tableExists('pos_sales')) return [];
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        $totalColumn = $this->column('pos_sales', 'total_amount', 'final_total');

        return $this->salesBase($from, $to)
            ->selectRaw('DATE(' . $dateColumn . ') as report_date')
            ->selectRaw('COUNT(*) as bills')
            ->selectRaw('SUM(' . $totalColumn . ') as total_amount')
            ->groupBy(DB::raw('DATE(' . $dateColumn . ')'))
            ->orderBy('report_date', 'desc')
            ->limit(90)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function paymentSummary(Carbon $from, Carbon $to): array
    {
        if (!$this->tableExists('pos_payments')) return [];
        $methodColumn = $this->column('pos_payments', 'payment_method', 'method');

        return $this->paymentsBase($from, $to)
            ->selectRaw('COALESCE(' . $methodColumn . ', "unknown") as payment_method')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM(amount) as amount')
            ->groupBy(DB::raw('COALESCE(' . $methodColumn . ', "unknown")'))
            ->orderBy('amount', 'desc')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function itemSales(Carbon $from, Carbon $to): array
    {
        if (!$this->tableExists('pos_sale_lines') || !$this->tableExists('pos_sales')) return [];
        $saleIdColumn = $this->column('pos_sale_lines', 'sale_id', 'pos_sale_id');
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        $totalColumn = $this->column('pos_sale_lines', 'line_total', 'total');
        $qtyColumn = $this->column('pos_sale_lines', 'quantity', 'qty');

        return DB::table('pos_sale_lines')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_lines.' . $saleIdColumn)
            ->leftJoin('pos_products', 'pos_products.id', '=', 'pos_sale_lines.product_id')
            ->whereBetween('pos_sales.' . $dateColumn, [$from, $to])
            ->where(function ($query) {
                if (Schema::hasColumn('pos_sales', 'status')) {
                    $query->whereNull('pos_sales.status')->orWhereNotIn('pos_sales.status', ['void', 'cancelled', 'draft']);
                }
            })
            ->selectRaw('COALESCE(pos_sale_lines.product_name, pos_products.name, CONCAT("Product #", pos_sale_lines.product_id)) as product_name')
            ->selectRaw('COALESCE(pos_products.sku, "") as sku')
            ->selectRaw('SUM(pos_sale_lines.' . $qtyColumn . ') as quantity')
            ->selectRaw('SUM(pos_sale_lines.' . $totalColumn . ') as total_amount')
            ->groupBy('product_name', 'sku')
            ->orderBy('total_amount', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function categorySales(Carbon $from, Carbon $to): array
    {
        if (!$this->tableExists('pos_sale_lines') || !$this->tableExists('pos_products') || !$this->tableExists('pos_sales')) return [];
        $saleIdColumn = $this->column('pos_sale_lines', 'sale_id', 'pos_sale_id');
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        $totalColumn = $this->column('pos_sale_lines', 'line_total', 'total');
        $qtyColumn = $this->column('pos_sale_lines', 'quantity', 'qty');

        $query = DB::table('pos_sale_lines')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_lines.' . $saleIdColumn)
            ->leftJoin('pos_products', 'pos_products.id', '=', 'pos_sale_lines.product_id')
            ->whereBetween('pos_sales.' . $dateColumn, [$from, $to]);

        if ($this->tableExists('pos_categories') && Schema::hasColumn('pos_products', 'category_id')) {
            $query->leftJoin('pos_categories', 'pos_categories.id', '=', 'pos_products.category_id')
                ->selectRaw('COALESCE(pos_categories.name, "Uncategorised") as category_name');
        } else {
            $query->selectRaw('"Uncategorised" as category_name');
        }

        return $query
            ->selectRaw('SUM(pos_sale_lines.' . $qtyColumn . ') as quantity')
            ->selectRaw('SUM(pos_sale_lines.' . $totalColumn . ') as total_amount')
            ->groupBy('category_name')
            ->orderBy('total_amount', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function cashierSales(Carbon $from, Carbon $to): array
    {
        if (!$this->tableExists('pos_sales')) return [];
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        $totalColumn = $this->column('pos_sales', 'total_amount', 'final_total');
        $userColumn = $this->column('pos_sales', 'created_by', 'user_id');

        return $this->salesBase($from, $to)
            ->selectRaw('COALESCE(' . $userColumn . ', 0) as cashier_id')
            ->selectRaw('COUNT(*) as bills')
            ->selectRaw('SUM(' . $totalColumn . ') as total_amount')
            ->groupBy(DB::raw('COALESCE(' . $userColumn . ', 0)'))
            ->orderBy('total_amount', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function stockValuation(): array
    {
        if (!$this->tableExists('pos_products')) return [];
        $stock = $this->column('pos_products', 'current_stock', 'stock_quantity');
        $cost = $this->column('pos_products', 'cost_price', 'unit_price');

        return DB::table('pos_products')
            ->selectRaw('name, sku, barcode')
            ->selectRaw($stock . ' as stock_quantity')
            ->selectRaw($cost . ' as cost_price')
            ->selectRaw('(' . $stock . ' * ' . $cost . ') as stock_value')
            ->where(function ($query) {
                if (Schema::hasColumn('pos_products', 'deleted_at')) $query->whereNull('deleted_at');
            })
            ->orderBy('stock_value', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function lowStock(): array
    {
        if (!$this->tableExists('pos_products')) return [];
        $stock = $this->column('pos_products', 'current_stock', 'stock_quantity');
        $alert = Schema::hasColumn('pos_products', 'alert_quantity') ? 'alert_quantity' : null;
        if (!$alert) return [];

        return DB::table('pos_products')
            ->select('name', 'sku', 'barcode')
            ->selectRaw($stock . ' as stock_quantity')
            ->selectRaw($alert . ' as alert_quantity')
            ->whereRaw($stock . ' <= ' . $alert)
            ->where($alert, '>', 0)
            ->where(function ($query) {
                if (Schema::hasColumn('pos_products', 'deleted_at')) $query->whereNull('deleted_at');
            })
            ->orderBy($stock)
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function exportRows(string $report, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        return match ($report) {
            'daily-sales' => $this->dailySales($from, $to),
            'payments' => $this->paymentSummary($from, $to),
            'item-sales' => $this->itemSales($from, $to),
            'category-sales' => $this->categorySales($from, $to),
            'cashier-sales' => $this->cashierSales($from, $to),
            'stock-valuation' => $this->stockValuation(),
            'low-stock' => $this->lowStock(),
            default => [],
        };
    }

    protected function dateRange(array $filters): array
    {
        $from = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : now()->startOfMonth();
        $to = !empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    protected function salesBase(Carbon $from, Carbon $to)
    {
        if (!$this->tableExists('pos_sales')) return DB::table(DB::raw('(select 1 as id, 0 as total_amount where 1=0) as empty_sales'));
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        return DB::table('pos_sales')
            ->whereBetween($dateColumn, [$from, $to])
            ->where(function ($query) {
                if (Schema::hasColumn('pos_sales', 'status')) {
                    $query->whereNull('status')->orWhereNotIn('status', ['void', 'cancelled', 'draft']);
                }
            });
    }

    protected function paymentsBase(Carbon $from, Carbon $to)
    {
        if (!$this->tableExists('pos_payments')) return DB::table(DB::raw('(select 1 as id, 0 as amount where 1=0) as empty_payments'));
        $dateColumn = $this->column('pos_payments', 'payment_date', 'paid_on');
        return DB::table('pos_payments')->whereBetween($dateColumn, [$from, $to]);
    }

    protected function returnsBase(Carbon $from, Carbon $to)
    {
        if (!$this->tableExists('pos_sale_returns')) return DB::table(DB::raw('(select 1 as id, 0 as total_amount where 1=0) as empty_returns'));
        $dateColumn = $this->column('pos_sale_returns', 'return_date', 'created_at');
        return DB::table('pos_sale_returns')->whereBetween($dateColumn, [$from, $to]);
    }

    protected function grossProfit(Carbon $from, Carbon $to): float
    {
        if (!$this->tableExists('pos_sale_lines') || !$this->tableExists('pos_sales')) return 0.0;
        $saleIdColumn = $this->column('pos_sale_lines', 'sale_id', 'pos_sale_id');
        $dateColumn = $this->column('pos_sales', 'sale_date', 'created_at');
        $lineTotal = $this->column('pos_sale_lines', 'line_total', 'total');
        $qtyColumn = $this->column('pos_sale_lines', 'quantity', 'qty');
        $costExpr = $this->tableExists('pos_products') ? $this->column('pos_products', 'cost_price', 'unit_price') : '0';

        return (float) DB::table('pos_sale_lines')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_lines.' . $saleIdColumn)
            ->leftJoin('pos_products', 'pos_products.id', '=', 'pos_sale_lines.product_id')
            ->whereBetween('pos_sales.' . $dateColumn, [$from, $to])
            ->selectRaw('SUM(pos_sale_lines.' . $lineTotal . ' - (COALESCE(pos_products.' . $costExpr . ', 0) * pos_sale_lines.' . $qtyColumn . ')) as gross_profit')
            ->value('gross_profit');
    }

    protected function stockValue(): float
    {
        if (!$this->tableExists('pos_products')) return 0.0;
        $stock = $this->column('pos_products', 'current_stock', 'stock_quantity');
        $cost = $this->column('pos_products', 'cost_price', 'unit_price');
        return (float) DB::table('pos_products')->selectRaw('SUM(' . $stock . ' * ' . $cost . ') as value')->value('value');
    }

    protected function openShiftCount(): int
    {
        if (!$this->tableExists('pos_register_sessions')) return 0;
        if (!Schema::hasColumn('pos_register_sessions', 'status')) return 0;
        return (int) DB::table('pos_register_sessions')->where('status', 'open')->count();
    }

    protected function tableExists(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function column(string $table, string $preferred, string $fallback): string
    {
        if (Schema::hasColumn($table, $preferred)) return $preferred;
        if (Schema::hasColumn($table, $fallback)) return $fallback;
        return $preferred;
    }
}
