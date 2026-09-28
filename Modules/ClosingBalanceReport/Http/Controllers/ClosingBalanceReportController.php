<?php

namespace Modules\ClosingBalanceReport\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\PurchaseLine;
use App\System;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClosingBalanceReportController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $business = Business::where('id', $businessId)->select('id', 'name')->first();

        $locations = BusinessLocation::forDropdown($businessId, true);
        $defaultLocation = $this->getDefaultLocationId($locations);

        // Include all categories/subcategories (including petro) and prepend "All"
        $categories = Category::forDropdown($businessId, 1);
        $categories->prepend(__('lang_v1.all'), '');

        $subCategories = Category::subCategoryforDropdown($businessId, 1);
        $subCategories->prepend(__('lang_v1.all'), '');

        $systemFooter = session()->get('business.report_footer') ?? env('REPORT_FOOTER');
        $systemToken = session()->get('business.system_token')
            ?? session()->get('business.token')
            ?? System::getProperty('system_token')
            ?? System::getProperty('systemToken');

        if (empty($systemToken) && !empty($systemFooter)) {
            if (preg_match('/system\s*token\s*[:#-]?\s*([A-Za-z0-9\-]+)/i', $systemFooter, $m)) {
                $systemToken = $m[1] ?? null;
            }
        }

        return view('closingbalancereport::index', compact(
            'business',
            'locations',
            'defaultLocation',
            'categories',
            'subCategories',
            'systemFooter',
            'systemToken'
        ));
    }

    public function summary(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $businessId = $request->session()->get('user.business_id');
        [$start, $end] = $this->getDateRange($request);
        $locationId = $this->validateLocation($request->get('location_id'));

        $previous = $this->buildSummary($businessId, $locationId, $start, $end, true);
        $current = $this->buildSummary($businessId, $locationId, $start, $end, false);
        $balance = $this->combineTotals($previous, $current);

        return response()->json([
            'previous' => $previous,
            'current' => $current,
            'balance' => $balance,
        ]);
    }

    private function buildSummary(int $businessId, ?int $locationId, Carbon $start, Carbon $end, bool $beforeStart): array
    {
        $permitted = $this->permittedLocationIds();

        $paymentsQuery = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereIn('t.type', ['sell', 'sell_return'])
            ->when($locationId, fn($q) => $q->where('t.location_id', $locationId))
            ->when(!$locationId && !empty($permitted), fn($q) => $q->whereIn('t.location_id', $permitted))
            ->when(
                $beforeStart,
                fn($q) => $q->whereDate('transaction_payments.paid_on', '<', $start->toDateString()),
                fn($q) => $q->whereBetween(DB::raw('DATE(transaction_payments.paid_on)'), [$start->toDateString(), $end->toDateString()])
            )
            ->whereNull('transaction_payments.deleted_at');

        $paymentTotals = $paymentsQuery->select([
            DB::raw("SUM(CASE WHEN transaction_payments.method = 'cash' THEN transaction_payments.amount ELSE 0 END) as cash"),
            DB::raw("SUM(CASE WHEN transaction_payments.method = 'card' THEN transaction_payments.amount ELSE 0 END) as card"),
            DB::raw("SUM(CASE WHEN transaction_payments.method = 'cheque' THEN transaction_payments.amount ELSE 0 END) as cheque"),
        ])->first();

        $creditSalesQuery = Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereIn('payment_status', ['due', 'partial', 'pending', 'price_later'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when(!$locationId && !empty($permitted), fn($q) => $q->whereIn('location_id', $permitted))
            ->when(
                $beforeStart,
                fn($q) => $q->whereDate('transaction_date', '<', $start->toDateString()),
                fn($q) => $q->whereBetween(DB::raw('DATE(transaction_date)'), [$start->toDateString(), $end->toDateString()])
            );

        $creditSales = (float)$creditSalesQuery->sum('final_total');

        $purchases = (float)Transaction::where('business_id', $businessId)
            ->where('type', 'purchase')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when(!$locationId && !empty($permitted), fn($q) => $q->whereIn('location_id', $permitted))
            ->when(
                $beforeStart,
                fn($q) => $q->whereDate('transaction_date', '<', $start->toDateString()),
                fn($q) => $q->whereBetween(DB::raw('DATE(transaction_date)'), [$start->toDateString(), $end->toDateString()])
            )
            ->sum('final_total');

        $expenses = (float)Transaction::where('business_id', $businessId)
            ->where('type', 'expense')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when(!$locationId && !empty($permitted), fn($q) => $q->whereIn('location_id', $permitted))
            ->when(
                $beforeStart,
                fn($q) => $q->whereDate('transaction_date', '<', $start->toDateString()),
                fn($q) => $q->whereBetween(DB::raw('DATE(transaction_date)'), [$start->toDateString(), $end->toDateString()])
            )
            ->sum('final_total');

        return [
            'cash' => $this->formatNumber($paymentTotals->cash ?? 0),
            'card' => $this->formatNumber($paymentTotals->card ?? 0),
            'cheque' => $this->formatNumber($paymentTotals->cheque ?? 0),
            'credit_sales' => $this->formatNumber($creditSales),
            'purchases' => $this->formatNumber($purchases),
            'expenses' => $this->formatNumber($expenses),
        ];
    }

    private function combineTotals(array $a, array $b): array
    {
        $keys = ['cash', 'card', 'cheque', 'credit_sales', 'purchases', 'expenses'];
        $combined = [];
        foreach ($keys as $key) {
            $combined[$key] = $this->formatNumber(($a[$key] ?? 0) + ($b[$key] ?? 0));
        }
        return $combined;
    }

    private function formatNumber($number): float
    {
        return round((float)$number, 2);
    }

    private function getDateRange(Request $request): array
    {
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        $startDate = !empty($start) ? Carbon::parse($start) : Carbon::today();
        $endDate = !empty($end) ? Carbon::parse($end) : Carbon::today();

        if ($endDate->lt($startDate)) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        return [$startDate->startOfDay(), $endDate->endOfDay()];
    }

    private function getDefaultLocationId($locations): ?int
    {
        $current = session('user.current_location');
        if (!empty($current) && User::can_access_this_location($current)) {
            return (int)$current;
        }

        if (!empty($locations) && is_iterable($locations)) {
            foreach ($locations as $id => $label) {
                if (!empty($id)) {
                    return (int)$id;
                }
            }
        }

        return null;
    }

    private function validateLocation($locationId): ?int
    {
        if (empty($locationId)) {
            return null;
        }

        $locationId = (int)$locationId;
        if (!User::can_access_this_location($locationId)) {
            abort(403, 'Unauthorized location');
        }

        return $locationId;
    }

    private function permittedLocationIds(): ?array
    {
        $permitted = auth()->user()->permitted_locations();
        if ($permitted === 'all') {
            return null;
        }
        return (array)$permitted;
    }

    public function sales(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $businessId = $request->session()->get('user.business_id');
        [$start, $end] = $this->getDateRange($request);
        $locationId = $this->validateLocation($request->get('location_id'));
        $permitted = $this->permittedLocationIds();

        $sales = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.sub_category_id', '=', 'c.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->when($locationId, fn($q) => $q->where('t.location_id', $locationId))
            ->when(!$locationId && !empty($permitted), fn($q) => $q->whereIn('t.location_id', $permitted))
            ->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start->toDateString(), $end->toDateString()])
            ->select([
                DB::raw('COALESCE(c.name, "Uncategorized") as sub_category'),
                DB::raw('SUM(transaction_sell_lines.quantity - IFNULL(transaction_sell_lines.quantity_returned,0)) as qty_sold'),
                DB::raw('SUM((transaction_sell_lines.quantity - IFNULL(transaction_sell_lines.quantity_returned,0)) * transaction_sell_lines.unit_price_inc_tax) as sold_amount')
            ])
            ->groupBy('p.sub_category_id', 'c.name')
            ->get();

        $totals = [
            'qty' => $sales->sum('qty_sold'),
            'amount' => $sales->sum('sold_amount'),
        ];

        return DataTables::of($sales)
            ->editColumn('qty_sold', fn($row) => number_format((float)$row->qty_sold, 2))
            ->editColumn('sold_amount', fn($row) => number_format((float)$row->sold_amount, 2))
            ->with('summary', [
                'qty' => number_format((float)$totals['qty'], 2),
                'amount' => number_format((float)$totals['amount'], 2),
            ])
            ->make(true);
    }

    public function productSummary(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $businessId = $request->session()->get('user.business_id');
        [$start, $end] = $this->getDateRange($request);
        $locationId = $this->validateLocation($request->get('location_id'));
        $permitted = $this->permittedLocationIds();

        $categoryId = $request->get('category_id');
        $subCategoryId = $request->get('sub_category_id');
        $productId = $request->get('product_id');

        $productsQuery = Product::leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
            ->where('products.business_id', $businessId)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.sub_category_id',
                DB::raw('COALESCE(sub_cat.name, "All") as sub_category_name')
            )
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->when($subCategoryId, fn($q) => $q->where('products.sub_category_id', $subCategoryId))
            ->when($productId, fn($q) => $q->where('products.id', $productId));

        if (!empty($locationId)) {
            $productsQuery->join('product_locations as pl', 'pl.product_id', '=', 'products.id')
                ->where('pl.location_id', $locationId);
        } elseif (!empty($permitted)) {
            $productsQuery->join('product_locations as pl', 'pl.product_id', '=', 'products.id')
                ->whereIn('pl.location_id', $permitted);
        }

        $products = $productsQuery->distinct()->get();
        $productIds = $products->pluck('id')->all();

        // Movements before the start date (to build opening) and within range (for in/out)
        $openingPurchases = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, true);
        $rangePurchases = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, false, $end);

        $openingPurchaseReturns = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, true, null, true);
        $rangePurchaseReturns = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, false, $end, true);

        $openingSales = $this->saleQuantities($businessId, $locationId, $start, $productIds, true);
        $rangeSales = $this->saleQuantities($businessId, $locationId, $start, $productIds, false, $end);

        $openingSalesReturns = $this->saleReturnQuantities($businessId, $locationId, $start, $productIds, true);
        $rangeSalesReturns = $this->saleReturnQuantities($businessId, $locationId, $start, $productIds, false, $end);

        // Transfers (out via sell_transfer, in via purchase_transfer/production) and adjustments
        $openingTransferIn = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, true, null, false, ['purchase_transfer', 'production_purchase']);
        $rangeTransferIn = $this->purchaseQuantities($businessId, $locationId, $start, $productIds, false, $end, false, ['purchase_transfer', 'production_purchase']);

        $openingTransferOut = $this->saleQuantities($businessId, $locationId, $start, $productIds, true, null, ['sell_transfer', 'production_sell']);
        $rangeTransferOut = $this->saleQuantities($businessId, $locationId, $start, $productIds, false, $end, ['sell_transfer', 'production_sell']);

        $openingAdjustments = $this->stockAdjustmentQuantities($businessId, $locationId, $start, $productIds, true);
        $rangeAdjustments = $this->stockAdjustmentQuantities($businessId, $locationId, $start, $productIds, false, $end);

        $rows = $products->map(function ($product) use (
            $openingPurchases,
            $rangePurchases,
            $openingPurchaseReturns,
            $rangePurchaseReturns,
            $openingSales,
            $rangeSales,
            $openingSalesReturns,
            $rangeSalesReturns
        ) {
            $id = $product->id;

            $openingQty = ($openingPurchases[$id] ?? 0)
                + ($openingSalesReturns[$id] ?? 0)
                + ($openingTransferIn[$id] ?? 0)
                + ($openingAdjustments[$id] ?? 0)
                - ($openingSales[$id] ?? 0)
                - ($openingPurchaseReturns[$id] ?? 0)
                - ($openingTransferOut[$id] ?? 0);

            $inflow = ($rangePurchases[$id] ?? 0)
                + ($rangeSalesReturns[$id] ?? 0)
                + ($rangeTransferIn[$id] ?? 0)
                + ($rangeAdjustments[$id] ?? 0);

            $outflow = ($rangeSales[$id] ?? 0)
                + ($rangePurchaseReturns[$id] ?? 0)
                + ($rangeTransferOut[$id] ?? 0);
            $balance = $openingQty + $inflow - $outflow;

            return [
                'sub_category' => $product->sub_category_name,
                'product' => $product->name,
                'code' => $product->sku,
                'opening_qty' => $this->formatNumber($openingQty),
                'in_qty' => $this->formatNumber($inflow),
                'out_qty' => $this->formatNumber($outflow),
                'balance_qty' => $this->formatNumber($balance),
            ];
        });

        $totals = [
            'opening' => $rows->sum('opening_qty'),
            'in_qty' => $rows->sum('in_qty'),
            'out_qty' => $rows->sum('out_qty'),
            'balance_qty' => $rows->sum('balance_qty'),
        ];

        return DataTables::of($rows)
            ->editColumn('opening_qty', fn($row) => number_format((float)$row['opening_qty'], 2))
            ->editColumn('in_qty', fn($row) => number_format((float)$row['in_qty'], 2))
            ->editColumn('out_qty', fn($row) => number_format((float)$row['out_qty'], 2))
            ->editColumn('balance_qty', fn($row) => number_format((float)$row['balance_qty'], 2))
            ->with('summary', [
                'opening' => number_format((float)$totals['opening'], 2),
                'in_qty' => number_format((float)$totals['in_qty'], 2),
                'out_qty' => number_format((float)$totals['out_qty'], 2),
                'balance_qty' => number_format((float)$totals['balance_qty'], 2),
            ])
            ->make(true);
    }

    private function purchaseQuantities(
        int $businessId,
        ?int $locationId,
        Carbon $start,
        array $productIds,
        bool $beforeStart = false,
        ?Carbon $end = null,
        bool $isReturn = false,
        ?array $types = null
    ): \Illuminate\Support\Collection {
        $permitted = $this->permittedLocationIds();
        // Treat opening_stock as inbound; allow overriding types for transfers/production.
        $typeFilter = $types ?? ($isReturn ? ['purchase_return'] : ['purchase', 'opening_stock']);

        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', $typeFilter)
            ->whereIn('purchase_lines.product_id', $productIds)
            ->whereNotIn('t.status', ['draft', 'cancelled']);

        if ($isReturn) {
            $query->select('purchase_lines.product_id', DB::raw('SUM(COALESCE(purchase_lines.quantity_returned, purchase_lines.quantity)) as qty'));
        } else {
            $query->select('purchase_lines.product_id', DB::raw('SUM(purchase_lines.quantity - IFNULL(purchase_lines.quantity_returned,0)) as qty'));
        }

        if (!empty($locationId)) {
            $query->where('t.location_id', $locationId);
        } elseif (!empty($permitted)) {
            $query->whereIn('t.location_id', $permitted);
        }

        if ($beforeStart) {
            $query->whereDate('t.transaction_date', '<', $start->toDateString());
        } else {
            $endDate = $end ?? $start;
            $query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start->toDateString(), $endDate->toDateString()]);
        }

        return $query->groupBy('purchase_lines.product_id')->pluck('qty', 'purchase_lines.product_id');
    }

    private function saleQuantities(
        int $businessId,
        ?int $locationId,
        Carbon $start,
        array $productIds,
        bool $beforeStart = false,
        ?Carbon $end = null,
        ?array $types = null
    ): \Illuminate\Support\Collection {
        $permitted = $this->permittedLocationIds();
        $typeFilter = $types ?? ['sell'];

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', $typeFilter)
            ->where('t.status', 'final')
            ->whereIn('transaction_sell_lines.product_id', $productIds)
            ->select('transaction_sell_lines.product_id', DB::raw('SUM(transaction_sell_lines.quantity - IFNULL(transaction_sell_lines.quantity_returned,0)) as qty'));

        if (!empty($locationId)) {
            $query->where('t.location_id', $locationId);
        } elseif (!empty($permitted)) {
            $query->whereIn('t.location_id', $permitted);
        }

        if ($beforeStart) {
            $query->whereDate('t.transaction_date', '<', $start->toDateString());
        } else {
            $endDate = $end ?? $start;
            $query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start->toDateString(), $endDate->toDateString()]);
        }

        return $query->groupBy('transaction_sell_lines.product_id')->pluck('qty', 'transaction_sell_lines.product_id');
    }

    private function saleReturnQuantities(
        int $businessId,
        ?int $locationId,
        Carbon $start,
        array $productIds,
        bool $beforeStart = false,
        ?Carbon $end = null
    ): \Illuminate\Support\Collection {
        $permitted = $this->permittedLocationIds();
        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell_return')
            ->whereIn('transaction_sell_lines.product_id', $productIds)
            ->select('transaction_sell_lines.product_id', DB::raw('SUM(transaction_sell_lines.quantity) as qty'));

        if (!empty($locationId)) {
            $query->where('t.location_id', $locationId);
        } elseif (!empty($permitted)) {
            $query->whereIn('t.location_id', $permitted);
        }

        if ($beforeStart) {
            $query->whereDate('t.transaction_date', '<', $start->toDateString());
        } else {
            $endDate = $end ?? $start;
            $query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start->toDateString(), $endDate->toDateString()]);
        }

        return $query->groupBy('transaction_sell_lines.product_id')->pluck('qty', 'transaction_sell_lines.product_id');
    }

    private function stockAdjustmentQuantities(
        int $businessId,
        ?int $locationId,
        Carbon $start,
        array $productIds,
        bool $beforeStart = false,
        ?Carbon $end = null
    ): \Illuminate\Support\Collection {
        $permitted = $this->permittedLocationIds();
        $query = DB::table('stock_adjustment_lines as al')
            ->join('transactions as t', 'al.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'stock_adjustment')
            ->whereIn('al.product_id', $productIds)
            ->select('al.product_id', DB::raw("SUM(CASE WHEN al.stock_adjustment_type = 'increase' THEN al.quantity ELSE -1 * al.quantity END) as qty"));

        if (!empty($locationId)) {
            $query->where('t.location_id', $locationId);
        } elseif (!empty($permitted)) {
            $query->whereIn('t.location_id', $permitted);
        }

        if ($beforeStart) {
            $query->whereDate('t.transaction_date', '<', $start->toDateString());
        } else {
            $endDate = $end ?? $start;
            $query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start->toDateString(), $endDate->toDateString()]);
        }

        return $query->groupBy('al.product_id')->pluck('qty', 'al.product_id');
    }

    public function subCategories(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $businessId = $request->session()->get('user.business_id');
        $categoryId = $request->get('category_id');

        $query = Category::where('business_id', $businessId)
            ->where('parent_id', '!=', 0);

        if (!empty($categoryId)) {
            $query->where('parent_id', $categoryId);
        }

        $subCategories = $query->select('id', 'name')->orderBy('name')->get();

        return response()->json($subCategories);
    }

    public function products(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $businessId = $request->session()->get('user.business_id');
        $categoryId = $request->get('category_id');
        $subCategoryId = $request->get('sub_category_id');
        $locationId = $this->validateLocation($request->get('location_id'));
        $search = $request->get('q');

        $query = Product::where('products.business_id', $businessId)
            ->select('products.id', 'products.name', 'products.sku');

        if (!empty($categoryId)) {
            $query->where('products.category_id', $categoryId);
        }

        if (!empty($subCategoryId)) {
            $query->where('products.sub_category_id', $subCategoryId);
        }

        if (!empty($locationId)) {
            $query->join('product_locations as pl', 'pl.product_id', '=', 'products.id')
                ->where('pl.location_id', $locationId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                    ->orWhere('products.sku', 'like', '%' . $search . '%');
            });
        }

        $products = $query->distinct()->orderBy('products.name')->limit(50)->get()
            ->map(function ($product) {
                $label = $product->name;
                if (!empty($product->sku)) {
                    $label .= ' (' . $product->sku . ')';
                }
                return [
                    'id' => $product->id,
                    'text' => $label,
                ];
            });

        return response()->json(['results' => $products]);
    }
}
