<?php

namespace Modules\Finance\Http\Controllers\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradingProfitController extends Controller
{
    private const ALLOWED_GROUPS = [
        'product',
        'category',
        'sub-category',
        'brand',
        'location',
        'invoice',
        'date',
        'customer',
        'day',
    ];

    /**
     * Finance-owned DataTables endpoint for Trading Profit.
     *
     * This method performs its own grouped count, search, ordering and paging.
     * Yajra's automatic count wrapper was unreliable for the legacy grouped
     * report and produced the generic DataTables Ajax warning.
     */
    public function data(Request $request, string $by)
    {
        if (! in_array($by, self::ALLOWED_GROUPS, true)) {
            abort(404);
        }

        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
        $draw = max(0, (int) $request->input('draw', 0));

        try {
            [$query, $labelColumn] = $this->buildGroupedQuery($request, $businessId, $by);

            $recordsTotal = $this->countGrouped(clone $query);
            $search = trim((string) data_get($request->input('search', []), 'value', ''));

            if ($search !== '') {
                $this->applySearch($query, $by, $search);
            }

            $recordsFiltered = $search === ''
                ? $recordsTotal
                : $this->countGrouped(clone $query);

            $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 0);
            $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'asc')) === 'desc'
                ? 'desc'
                : 'asc';
            $orderColumns = [
                0 => $labelColumn,
                1 => $by === 'invoice' ? 'final_total' : 'total_sales',
                2 => 'gross_profit',
            ];
            $query->orderBy($orderColumns[$orderColumnIndex] ?? $labelColumn, $orderDirection);

            $start = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 25);
            $length = $length < 1 ? 25 : min($length, 250);

            $rows = $query->offset($start)->limit($length)->get();
            $data = $rows->map(function ($row) use ($by, $labelColumn) {
                $sales = round((float) ($row->total_sales ?? 0), 4);
                $profit = round((float) ($row->gross_profit ?? 0), 4);

                $result = [
                    $labelColumn => (string) ($row->{$labelColumn} ?? ''),
                    'total_sales' => $this->currencySpan($sales, 'total-sales'),
                    'gross_profit' => $this->currencySpan($profit, 'gross-profit'),
                ];

                if ($by === 'invoice') {
                    $result['final_total'] = round((float) ($row->final_total ?? $sales), 4);
                }

                return $result;
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance trading profit DataTable failed', [
                'business_id' => $businessId,
                'group' => $by,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function buildGroupedQuery(Request $request, int $businessId, string $by): array
    {
        $purchaseCost = DB::table('transaction_sell_lines_purchase_lines as TSP')
            ->leftJoin('purchase_lines as TPL', 'TSP.purchase_line_id', '=', 'TPL.id')
            ->select('TSP.sell_line_id')
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(TSP.quantity, 0) - COALESCE(TSP.qty_returned, 0), 0) * COALESCE(TPL.purchase_price_inc_tax, 0)), 0) as cost_total')
            ->selectRaw('COALESCE(SUM(COALESCE(TSP.qty_returned, 0)), 0) as qty_returned')
            ->groupBy('TSP.sell_line_id');

        $soldQty = 'GREATEST(COALESCE(TSL.quantity, 0) - COALESCE(PC.qty_returned, 0), 0)';
        $lineSales = "(
            ({$soldQty} * COALESCE(TSL.unit_price_inc_tax, 0))
            - CASE
                WHEN TSL.line_discount_type = 'percentage' THEN
                    (({$soldQty} * COALESCE(TSL.unit_price_inc_tax, 0)) * COALESCE(TSL.line_discount_amount, 0)) / 100
                ELSE COALESCE(TSL.line_discount_amount, 0) * {$soldQty}
              END
        )";
        $grossProfit = "({$lineSales} - COALESCE(PC.cost_total, 0))";

        $query = DB::table('transaction_sell_lines as TSL')
            ->join('transactions as SALE', 'TSL.transaction_id', '=', 'SALE.id')
            ->join('products as PROD', 'TSL.product_id', '=', 'PROD.id')
            ->leftJoinSub($purchaseCost, 'PC', function ($join) {
                $join->on('PC.sell_line_id', '=', 'TSL.id');
            })
            ->where('SALE.business_id', $businessId)
            ->where('SALE.type', 'sell')
            ->where('SALE.status', 'final')
            ->whereNull('SALE.deleted_at')
            ->where(function ($children) {
                $children->whereNull('TSL.children_type')
                    ->orWhere('TSL.children_type', '!=', 'combo');
            });

        $startDate = $this->safeDate($request->input('start_date'));
        $endDate = $this->safeDate($request->input('end_date'));
        if ($startDate) {
            $query->where('SALE.transaction_date', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('SALE.transaction_date', '<=', $endDate . ' 23:59:59');
        }

        $locationId = $request->input('location_id');
        if ($locationId !== null && $locationId !== '' && $locationId !== 'all') {
            $validLocation = DB::table('business_locations')
                ->where('business_id', $businessId)
                ->where('id', (int) $locationId)
                ->exists();
            if ($validLocation) {
                $query->where('SALE.location_id', (int) $locationId);
            }
        }

        switch ($by) {
            case 'product':
                $query->leftJoin('variations as VAR', 'TSL.variation_id', '=', 'VAR.id')
                    ->leftJoin('product_variations as PVAR', 'VAR.product_variation_id', '=', 'PVAR.id')
                    ->selectRaw("CASE WHEN PROD.type = 'variable' THEN CONCAT(PROD.name, ' - ', COALESCE(PVAR.name, ''), ' - ', COALESCE(VAR.name, ''), ' (', COALESCE(VAR.sub_sku, PROD.sku, ''), ')') ELSE CONCAT(PROD.name, ' (', COALESCE(PROD.sku, ''), ')') END as product")
                    ->groupBy('TSL.product_id', 'TSL.variation_id', 'PROD.name', 'PROD.type', 'PROD.sku', 'PVAR.name', 'VAR.name', 'VAR.sub_sku');
                $labelColumn = 'product';
                break;
            case 'category':
                $query->leftJoin('categories as CAT', 'CAT.id', '=', 'PROD.category_id')
                    ->selectRaw("COALESCE(CAT.name, 'Uncategorised') as category")
                    ->groupBy('PROD.category_id', 'CAT.name');
                $labelColumn = 'category';
                break;
            case 'sub-category':
                $query->leftJoin('categories as SUBCAT', 'SUBCAT.id', '=', 'PROD.sub_category_id')
                    ->selectRaw("COALESCE(SUBCAT.name, 'Uncategorised') as category")
                    ->groupBy('PROD.sub_category_id', 'SUBCAT.name');
                $labelColumn = 'category';
                break;
            case 'brand':
                $query->leftJoin('brands as BR', 'BR.id', '=', 'PROD.brand_id')
                    ->selectRaw("COALESCE(BR.name, 'No Brand') as brand")
                    ->groupBy('PROD.brand_id', 'BR.name');
                $labelColumn = 'brand';
                break;
            case 'location':
                $query->join('business_locations as BL', function ($join) use ($businessId) {
                    $join->on('SALE.location_id', '=', 'BL.id')
                        ->where('BL.business_id', '=', $businessId);
                })
                    ->addSelect('BL.name as location')
                    ->groupBy('SALE.location_id', 'BL.name');
                $labelColumn = 'location';
                break;
            case 'customer':
                $query->leftJoin('contacts as CUST', function ($join) use ($businessId) {
                    $join->on('SALE.contact_id', '=', 'CUST.id')
                        ->where('CUST.business_id', '=', $businessId);
                })
                    ->selectRaw("COALESCE(CUST.name, 'Walk-in Customer') as customer")
                    ->groupBy('SALE.contact_id', 'CUST.name');
                $labelColumn = 'customer';
                break;
            case 'invoice':
                $query->addSelect('SALE.invoice_no')
                    ->groupBy('SALE.id', 'SALE.invoice_no');
                $labelColumn = 'invoice_no';
                break;
            case 'date':
                $query->selectRaw('DATE(SALE.transaction_date) as transaction_date')
                    ->groupByRaw('DATE(SALE.transaction_date)');
                $labelColumn = 'transaction_date';
                break;
            case 'day':
                $query->selectRaw('DAYNAME(SALE.transaction_date) as transaction_date')
                    ->groupByRaw('DAYOFWEEK(SALE.transaction_date), DAYNAME(SALE.transaction_date)');
                $labelColumn = 'transaction_date';
                break;
            default:
                throw new \InvalidArgumentException('Unsupported trading-profit grouping.');
        }

        $query->selectRaw("COALESCE(SUM({$lineSales}), 0) as total_sales")
            ->selectRaw("COALESCE(SUM({$grossProfit}), 0) as gross_profit");

        if ($by === 'invoice') {
            $query->selectRaw("COALESCE(SUM({$lineSales}), 0) as final_total");
        }

        return [$query, $labelColumn];
    }

    private function applySearch(Builder $query, string $by, string $search): void
    {
        $like = '%' . $search . '%';

        switch ($by) {
            case 'product':
                $query->where(function ($filter) use ($like) {
                    $filter->where('PROD.name', 'like', $like)
                        ->orWhere('PROD.sku', 'like', $like)
                        ->orWhere('VAR.name', 'like', $like)
                        ->orWhere('VAR.sub_sku', 'like', $like);
                });
                break;
            case 'category':
                $query->where('CAT.name', 'like', $like);
                break;
            case 'sub-category':
                $query->where('SUBCAT.name', 'like', $like);
                break;
            case 'brand':
                $query->where('BR.name', 'like', $like);
                break;
            case 'location':
                $query->where('BL.name', 'like', $like);
                break;
            case 'customer':
                $query->where('CUST.name', 'like', $like);
                break;
            case 'invoice':
                $query->where('SALE.invoice_no', 'like', $like);
                break;
            case 'date':
                $date = $this->safeDate($search);
                if ($date) {
                    $query->whereDate('SALE.transaction_date', $date);
                } else {
                    $query->whereRaw('1 = 0');
                }
                break;
            case 'day':
                $query->whereRaw('DAYNAME(SALE.transaction_date) LIKE ?', [$like]);
                break;
        }
    }

    private function countGrouped(Builder $query): int
    {
        return (int) DB::query()->fromSub($query, 'FINANCE_TRADING_ROWS')->count();
    }

    private function currencySpan(float $value, string $class): string
    {
        return '<span class="display_currency ' . $class . '" data-currency_symbol="true" data-orig-value="' . $value . '">' . $value . '</span>';
    }

    private function safeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
