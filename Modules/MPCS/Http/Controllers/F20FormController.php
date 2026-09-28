<?php

namespace Modules\MPCS\Http\Controllers;
use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use App\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Modules\MPCS\Entities\MeterSale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22Detail;
use App\Contact;
use App\Transaction;
use Modules\MPCS\Entities\Mpcs20FormSettings;
use App\MergedSubCategory;
class F20FormController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */


    public function index(Request $request)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? $request->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');
        // FIX: Filter by business_id and get latest settings (was missing business_id filter)
        $settings = Mpcs20FormSettings::where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->first();

        $form_type = strtolower(trim((string) ($request->get('form_type') ?? 'all')));
        $form_date = $request->get('form_date') ?? now()->toDateString();
        $is_credit = $form_type === 'credit';

        // F20 is a generated report and has no persisted App\Models\Form20 model.
        // Derive the form number from the tenant's F20 settings and selected date.
        $F20_form_sn = $this->calculateFormNumber($settings, $form_date, $form_type);

        $bname = Business::find($business_id);

        abort_if(
            empty($bname),
            403,
            'Business was not found in the active tenant database.'
        );

        $form_number = $settings?->starting_number ?? 1;
        $date = $settings?->opening_date
            ?? $settings?->date
            ?? now()->toDateString();

        $userAdded = $bname->name;

        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations = BusinessLocation::forDropdown($business_id);

        $business_details = $bname;
        $currency_precision = (int)$business_details->currency_precision;
        $qty_precision = (int)$business_details->quantity_precision;

        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
        $fuelCategoryId = Category::where('name', 'Fuel')->value('id');
        $fuelCategoryQuery = Category::where('parent_id', $fuelCategoryId)->select(['name', 'id']);
        $fuelCategory = auth()->user()->can('superadmin')
            ? $fuelCategoryQuery->get()->pluck('name', 'id')
            : $fuelCategoryQuery->where('business_id', $business_id)->get()->pluck('name', 'id');
        $productIds = [];
        if (!empty($settings) && !empty($settings->product)) {
            $productIds = explode(',', $settings->product);
        } elseif (!empty($settings) && !empty($settings->category)) {
            $categories = explode(',', $settings->category);
            // include products belonging either to a category or a sub-category
            $productIds = Product::where(function ($query) use ($categories) {
                    $query->whereIn('sub_category_id', $categories)
                          ->orWhereIn('category_id', $categories);
                })
                ->pluck('id');
        }

        $products = Product::leftJoin('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->leftJoin('transactions', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->leftJoin('mpcs_20_form_settings', function ($join) {
                // match either category_id or sub_category_id against configured categories
                $join->on(DB::raw("(
                        FIND_IN_SET(products.category_id, REPLACE(REPLACE(mpcs_20_form_settings.category, '(', ''), ')', ''))
                        OR FIND_IN_SET(products.sub_category_id, REPLACE(REPLACE(mpcs_20_form_settings.category, '(', ''), ')', ''))
                    )"), '>', DB::raw(0));
            })
            ->where('products.business_id', $business_id)
            ->whereIn('products.id', $productIds)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'transaction_sell_lines.quantity as qty',
                'transaction_sell_lines.unit_price',
                'transactions.id as transaction_id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price) as total_amount')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'transactions.id', 'transactions.transaction_date')
            ->get()
            ->groupBy('product_id');

// View layout
        $layout = 'layouts.app';

        return view('mpcs::forms.20Form.F20_form', compact(
            'F20_form_sn',
            'sub_categories',
            'fuelCategory',
            'currency_precision',
            'qty_precision',
            'merged_sub_categories',
            'business_locations',
            'settings',
            'form_number',
            'date',
            'userAdded',
            'layout',
            'products',
            'is_credit',

        ));

    }

        public function getForm20Data(Request $request)
        {
            try {
                $business_id = $request->session()->get('user.business_id')
                    ?? optional(auth()->user())->business_id
                    ?? $request->session()->get('business.id');

                if (!$business_id) {
                    \Log::error('F20 getForm20Data: No business_id in session');
                    return response()->json([
                        'products' => [],
                        'totals' => [],
                        'message' => 'Session expired. Please refresh the page.',
                    ], 401);
                }
                
                $form_date_range = trim((string) $request->input('form_date_range'));
                $form_type_input = $request->get('form_type') ?? 'All';
                $form_type = ucfirst(strtolower($form_type_input));

                if (!in_array($form_type, ['All', 'Credit', 'Cash'])) {
                    $form_type = 'All';
                }

            // Parse the date range safely (accept single date or range with "-" / "~")
            $start_date = Carbon::today()->toDateString();
            $end_date = $start_date;
            if (!empty($form_date_range)) {
                $raw_range = trim($form_date_range);
                $range_parts = [$raw_range, null];

                if (Str::contains($raw_range, '~')) {
                    $range_parts = array_map('trim', explode('~', $raw_range, 2));
                } elseif (Str::contains($raw_range, ' - ')) {
                    $range_parts = array_map('trim', explode(' - ', $raw_range, 2));
                }
                try {
                    $start_date = Carbon::parse($range_parts[0] ?? $raw_range)->toDateString();
                    $end_date = !empty($range_parts[1])
                        ? Carbon::parse($range_parts[1])->toDateString()
                        : $start_date;
                } catch (\Exception $e) {
                    // Fallback to today if parsing fails
                    $start_date = Carbon::today()->toDateString();
                    $end_date = $start_date;
                }
            }

            // FIX: Get latest settings for this business (orderBy desc ensures most recent config is used)
            $settings = Mpcs20FormSettings::where('business_id', $business_id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$settings) {
                return response()->json([
                    'products' => [],
                    'totals' => [],
                    'message' => 'Please configure F20 Form Settings first.',
                ]);
            }

            $form14Setting = $form_type === 'Credit'
                ? MpcsFormSetting::where('business_id', $business_id)->first()
                : null;

            $productIds = [];
            if (!empty($settings->product)) {
                $productIds = explode(',', $settings->product);
            } elseif (!empty($settings->category)) {
                $categories = explode(',', $settings->category);
                $productIds = Product::where(function ($query) use ($categories) {
                        $query->whereIn('sub_category_id', $categories)
                              ->orWhereIn('category_id', $categories);
                    })
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($productIds)) {
                return response()->json([
                    'products' => [],
                    'totals' => [],
                    'message' => 'No products configured in F20 Form Settings.',
                ]);
            }

            $locationId = $request->get('location_id');

            \Log::info('Form20 filters', [
                'business_id' => $business_id,
                'form_type' => $form_type,
                'form_date_range' => $form_date_range,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $locationId,
                'product_ids' => $productIds,
            ]);

            // Total sales (meter sales + other sales) grouped by settlement and product.
            // Unit Sales Price must come from each saved sale row's actual `price`.
            // Older rows may not have price populated, so use the configured product
            // selling price before falling back to sub_total / qty. Never derive the
            // unit price from cash/card/credit allocations.
            $variationPriceSubquery = DB::table('variations')
                ->whereIn('product_id', $productIds)
                ->select(
                    'product_id',
                    DB::raw('MAX(COALESCE(NULLIF(sell_price_inc_tax, 0), NULLIF(default_sell_price, 0), 0)) as configured_sell_price')
                )
                ->groupBy('product_id');

            // Use settlement_id for consistent matching with credit sales.
            $totalSales = MeterSale::leftJoin('settlements', 'meter_sales.settlement_no', '=', 'settlements.id')
                ->leftJoinSub(clone $variationPriceSubquery, 'variation_prices', function ($join) {
                    $join->on('variation_prices.product_id', '=', 'meter_sales.product_id');
                })
                ->leftJoin('products', 'meter_sales.product_id', '=', 'products.id')
                ->where('meter_sales.business_id', $business_id)
                ->whereIn('meter_sales.product_id', $productIds)
                ->when($start_date, function ($query) use ($start_date, $end_date) {
                    $query->whereDate('settlements.transaction_date', '>=', $start_date)
                        ->whereDate('settlements.transaction_date', '<=', $end_date);
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('settlements.location_id', $locationId);
                })
                ->select(
                    DB::raw('COALESCE(settlements.id, meter_sales.settlement_no) as settlement_id'),
                    DB::raw('COALESCE(settlements.settlement_no, meter_sales.settlement_no) as settlement_no'),
                    'meter_sales.product_id',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    DB::raw('SUM(meter_sales.qty) as total_qty'),
                    DB::raw('SUM(meter_sales.sub_total) as total_amount'),
                    DB::raw("SUM(
                        meter_sales.qty * COALESCE(
                            NULLIF(meter_sales.price, 0),
                            NULLIF(variation_prices.configured_sell_price, 0),
                            NULLIF(meter_sales.sub_total / NULLIF(meter_sales.qty, 0), 0),
                            0
                        )
                    ) as price_basis_amount")
                )
                ->groupBy(DB::raw('COALESCE(settlements.id, meter_sales.settlement_no)'), DB::raw('COALESCE(settlements.settlement_no, meter_sales.settlement_no)'), 'meter_sales.product_id', 'products.name', 'products.sku')
                ->get();

            // Also get other sales and combine with meter sales
            $otherSales = DB::table('other_sales')
                ->leftJoin('settlements', 'other_sales.settlement_no', '=', 'settlements.id')
                ->leftJoin('products', 'other_sales.product_id', '=', 'products.id')
                ->leftJoinSub(clone $variationPriceSubquery, 'variation_prices', function ($join) {
                    $join->on('variation_prices.product_id', '=', 'other_sales.product_id');
                })
                ->where('other_sales.business_id', $business_id)
                ->whereIn('other_sales.product_id', $productIds)
                ->when($start_date, function ($query) use ($start_date, $end_date) {
                    $query->whereDate('settlements.transaction_date', '>=', $start_date)
                        ->whereDate('settlements.transaction_date', '<=', $end_date);
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('settlements.location_id', $locationId);
                })
                ->select(
                    DB::raw('COALESCE(settlements.id, other_sales.settlement_no) as settlement_id'),
                    DB::raw('COALESCE(settlements.settlement_no, other_sales.settlement_no) as settlement_no'),
                    'other_sales.product_id',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    DB::raw('SUM(other_sales.qty) as total_qty'),
                    DB::raw('SUM(other_sales.sub_total) as total_amount'),
                    DB::raw("SUM(
                        other_sales.qty * COALESCE(
                            NULLIF(other_sales.price, 0),
                            NULLIF(variation_prices.configured_sell_price, 0),
                            NULLIF(other_sales.sub_total / NULLIF(other_sales.qty, 0), 0),
                            0
                        )
                    ) as price_basis_amount")
                )
                ->groupBy(DB::raw('COALESCE(settlements.id, other_sales.settlement_no)'), DB::raw('COALESCE(settlements.settlement_no, other_sales.settlement_no)'), 'other_sales.product_id', 'products.name', 'products.sku')
                ->get();

            // Combine meter sales and other sales
            $combinedSales = collect();
            
            // Add meter sales to combined collection
            foreach ($totalSales as $sale) {
                $combinedSales->push($sale);
            }
            
            // Add other sales to combined collection
            foreach ($otherSales as $sale) {
                $combinedSales->push($sale);
            }
            
            // Group combined sales by settlement_id and product_id to sum quantities
            $totalSales = $combinedSales->groupBy(function($item) {
                return ($item->settlement_id ?? $item->settlement_no) . '-' . $item->product_id;
            })->map(function($group) {
                $first = $group->first();
                $first->total_qty = $group->sum('total_qty');
                $first->total_amount = $group->sum('total_amount');
                $first->price_basis_amount = $group->sum(function ($row) {
                    return (float) ($row->price_basis_amount ?? $row->total_amount ?? 0);
                });
                return $first;
            })->values();

            $posSaleAmountExpr = 'COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0) * tsl.quantity';
            $posSalesBaseQuery = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('t.deleted_at')
                ->whereNull('tsl.deleted_at')
                ->whereIn('tsl.product_id', $productIds)
                ->where(function ($q) {
                    $q->whereNull('t.sub_type')
                        ->orWhere('t.sub_type', '!=', 'settlement');
                })
                ->whereNull('t.credit_sale_id')
                ->whereNotExists(function ($q) {
                    $q->from('meter_sales as ms')
                        ->whereColumn('ms.transaction_id', 't.id');
                })
                ->whereNotExists(function ($q) {
                    $q->from('other_sales as os')
                        ->whereColumn('os.transaction_id', 't.id');
                })
                ->when($start_date, function ($query) use ($start_date, $end_date) {
                    $query->whereDate('t.transaction_date', '>=', $start_date)
                        ->whereDate('t.transaction_date', '<=', $end_date);
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('t.location_id', $locationId);
                });

            $applyPosCreditFilter = function ($query) {
                return $query->where(function ($q) {
                    $q->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($sub) {
                            $sub->from('transaction_payments as tp2')
                                ->whereColumn('tp2.transaction_id', 't.id')
                                ->whereIn('tp2.method', ['credit', 'credit_sale'])
                                ->whereNull('tp2.deleted_at');
                        });
                });
            };

            $applyPosCashFilter = function ($query) {
                return $query->where(function ($q) {
                    $q->whereNull('t.is_credit_sale')
                        ->orWhere('t.is_credit_sale', 0);
                })
                    ->whereNotIn('t.payment_status', ['due', 'partial'])
                    ->whereNotExists(function ($sub) {
                        $sub->from('transaction_payments as tp2')
                            ->whereColumn('tp2.transaction_id', 't.id')
                            ->whereIn('tp2.method', ['credit', 'credit_sale'])
                            ->whereNull('tp2.deleted_at');
                    });
            };

            $selectPosSales = function ($query) use ($posSaleAmountExpr) {
                return $query->select(
                    DB::raw("CONCAT('POS-', t.id) as settlement_id"),
                    DB::raw("COALESCE(t.invoice_no, CONCAT('POS-', t.id)) as settlement_no"),
                    'tsl.product_id',
                    'p.name as product_name',
                    'p.sku as product_sku',
                    DB::raw('SUM(tsl.quantity) as total_qty'),
                    DB::raw("SUM($posSaleAmountExpr) as total_amount"),
                    DB::raw("SUM($posSaleAmountExpr) as price_basis_amount")
                )
                    ->groupBy('t.id', 't.invoice_no', 'tsl.product_id', 'p.name', 'p.sku')
                    ->get();
            };

            $posCashSales = $selectPosSales($applyPosCashFilter(clone $posSalesBaseQuery));
            $posCreditSales = $selectPosSales($applyPosCreditFilter(clone $posSalesBaseQuery));

            if ($form_type === 'Cash') {
                $totalSales = $totalSales->concat($posCashSales)->values();
            } elseif ($form_type === 'All') {
                $totalSales = $totalSales->concat($posCashSales)->concat($posCreditSales)->values();
            }

            // Card payments per settlement (used to exclude non-cash amounts when Cash form type is selected)
            $cardPayments = DB::table('settlement_card_payments as scp')
                ->leftJoin('settlements as st', function ($join) {
                    $scpNo = DB::raw("CONVERT(scp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                    $stId = DB::raw("CAST(st.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci");
                    $stNo = DB::raw("CONVERT(st.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");

                    $join->on($scpNo, '=', $stId)
                        ->orOn($scpNo, '=', $stNo);
                })
                ->where('scp.business_id', $business_id)
                ->when($start_date, function ($query) use ($start_date, $end_date) {
                    $query->where(function ($dateQuery) use ($start_date, $end_date) {
                        $dateQuery->where(function ($settlementDateQuery) use ($start_date, $end_date) {
                            $settlementDateQuery->whereNotNull('st.transaction_date')
                                ->whereBetween('st.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                        })->orWhere(function ($paymentCreatedDateQuery) use ($start_date, $end_date) {
                            $paymentCreatedDateQuery->whereNull('st.transaction_date')
                                ->whereBetween('scp.created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                        });
                    });
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('st.location_id', $locationId);
                })
                ->select(
                    DB::raw('COALESCE(st.settlement_no, scp.settlement_no) as settlement_no'),
                    DB::raw('SUM(scp.amount) as card_amount')
                )
                ->groupBy('settlement_no')
                ->get()
                ->keyBy('settlement_no');

            // Credit sales (reuse F14 joins)
            // NOTE: Wrap COALESCE in DATE() so filtering by a single day (start_date == end_date)
            // correctly matches all times on that day instead of only midnight.
            $creditDateColumn = DB::raw('DATE(COALESCE(st.transaction_date, transactions.transaction_date, scsp.order_date))');
            $creditBaseQuery = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions', 'transactions.credit_sale_id', '=', 'scsp.id')
                ->leftJoin('settlements as st', function ($join) {
                    $scspNo = DB::raw("CONVERT(scsp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                    $stId = DB::raw("CAST(st.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci");
                    $stNo = DB::raw("CONVERT(st.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");

                    $join->on('st.business_id', '=', 'scsp.business_id')
                        ->on(function ($settlementKeyJoin) use ($scspNo, $stId, $stNo) {
                            $settlementKeyJoin->on($scspNo, '=', $stId)
                                ->orOn($scspNo, '=', $stNo);
                        });
                })
                ->leftJoin('products', 'scsp.product_id', '=', 'products.id')
                ->where('scsp.business_id', $business_id)
                ->whereIn('scsp.product_id', $productIds)
                ->when($start_date, function ($query) use ($start_date, $end_date, $creditDateColumn) {
                    $query->whereBetween($creditDateColumn, [$start_date, $end_date]);
                })
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where(function ($q) use ($locationId) {
                        $q->where('transactions.location_id', $locationId)
                            ->orWhere('st.location_id', $locationId);
                    });
                });

            $creditAggregates = (clone $creditBaseQuery)
                ->select(
                    DB::raw('COALESCE(st.settlement_no, scsp.settlement_no) as settlement_no'),
                    'scsp.product_id',
                    DB::raw('SUM(scsp.qty) as total_qty'),
                    DB::raw('SUM(scsp.amount) as total_amount')
                )
                ->groupBy('settlement_no', 'scsp.product_id')
                ->get()
                ->keyBy(function ($row) {
                    return ($row->settlement_no ?? '') . '-' . $row->product_id;
                });

            $creditEntries = (clone $creditBaseQuery)
                ->select(
                    DB::raw('COALESCE(st.transaction_date, DATE(transactions.transaction_date), scsp.order_date) as credit_date'),
                    DB::raw('COALESCE(st.settlement_no, scsp.settlement_no) as settlement_no'),
                    'scsp.product_id',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'scsp.qty',
                    'scsp.price',
                    'scsp.amount',
                    'scsp.id as credit_id'
                )
                ->orderByDesc('st.id')
                ->orderByDesc('scsp.id')
                ->get();

            $posCreditEntries = $posCreditSales->map(function ($sale) {
                $qty = (float) $sale->total_qty;
                $amount = (float) $sale->total_amount;

                return (object) [
                    'credit_date' => null,
                    'settlement_no' => $sale->settlement_no,
                    'product_id' => $sale->product_id,
                    'product_name' => $sale->product_name,
                    'product_sku' => $sale->product_sku,
                    'qty' => $qty,
                    'price' => $qty > 0 ? $amount / $qty : 0,
                    'amount' => $amount,
                    'credit_id' => $sale->settlement_id,
                ];
            });

            $creditEntries = $creditEntries->concat($posCreditEntries)->values();

            // Bill number offset (reuse F14 numbering logic)
            $totalBeforeStartDate = 0;
            if (!empty($start_date)) {
                $offsetQuery = DB::table('settlement_credit_sale_payments as scsp')
                    ->leftJoin('transactions', 'transactions.credit_sale_id', '=', 'scsp.id')
                    ->leftJoin('settlements as st', function ($join) {
                        $scspNo = DB::raw("CONVERT(scsp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $stId = DB::raw("CAST(st.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $stNo = DB::raw("CONVERT(st.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");

                        $join->on($scspNo, '=', $stId)
                            ->orOn($scspNo, '=', $stNo);
                    })
                    ->where('scsp.business_id', $business_id);

                if ($locationId) {
                    $offsetQuery->where(function ($q) use ($locationId) {
                        $q->where('transactions.location_id', $locationId)
                            ->orWhere('st.location_id', $locationId);
                    });
                }

                if (!empty($settings->F14_form_tdate)) {
                    try {
                        $settingDate = Carbon::parse($settings->F14_form_tdate)->toDateString();
                        if ($settingDate != $start_date) {
                            $offsetQuery->whereDate($creditDateColumn, '>=', $settingDate)
                                ->whereDate($creditDateColumn, '<', $start_date);
                        }
                    } catch (\Exception $e) {
                        $offsetQuery->whereDate($creditDateColumn, '<', $start_date);
                    }
                } else {
                    $offsetQuery->whereDate($creditDateColumn, '<', $start_date);
                }

                $totalBeforeStartDate = $offsetQuery->count();
            }

            // Calculate bill number starting points
            $billCounterStart = ($settings->bill_no_form_14 ?? 0) + $totalBeforeStartDate;
            $billNoDisplayStart = null;
            
            // Calculate Cash bill number starting point
            $cashBillCounterStart = 1;
            
            if ($form_type === 'Cash' && !empty($start_date)) {
                // Count unique settlements with CASH sales (Total Sale - Credit Sales > 0) before start_date
                // Get all meter sales with other sales before start_date
                $allSettlementsBefore = MeterSale::leftJoin('settlements', 'meter_sales.settlement_no', '=', 'settlements.id')
                    ->where('meter_sales.business_id', $business_id)
                    ->whereIn('meter_sales.product_id', $productIds)
                    ->whereDate('settlements.transaction_date', '<', $start_date)
                    ->when($locationId, function ($q) use ($locationId) {
                        $q->where('settlements.location_id', $locationId);
                    })
                    ->select(
                        DB::raw('COALESCE(settlements.settlement_no, meter_sales.settlement_no) as settlement_no'),
                        'meter_sales.product_id',
                        DB::raw('SUM(meter_sales.qty) as total_qty')
                    )
                    ->groupBy('settlement_no', 'meter_sales.product_id')
                    ->get()
                    ->groupBy('settlement_no');
                
                // Get other sales before start_date
                $otherSettlementsBefore = DB::table('other_sales')
                    ->leftJoin('settlements', 'other_sales.settlement_no', '=', 'settlements.id')
                    ->where('other_sales.business_id', $business_id)
                    ->whereIn('other_sales.product_id', $productIds)
                    ->whereDate('settlements.transaction_date', '<', $start_date)
                    ->when($locationId, function ($q) use ($locationId) {
                        $q->where('settlements.location_id', $locationId);
                    })
                    ->select(
                        DB::raw('COALESCE(settlements.settlement_no, other_sales.settlement_no) as settlement_no'),
                        'other_sales.product_id',
                        DB::raw('SUM(other_sales.qty) as total_qty')
                    )
                    ->groupBy('settlement_no', 'other_sales.product_id')
                    ->get()
                    ->groupBy('settlement_no');
                
                // Combine meter sales and other sales for settlements before start date
                $combinedSettlementsBefore = collect();
                
                // Add meter sales
                foreach ($allSettlementsBefore as $settlementNo => $salesRows) {
                    if (!isset($combinedSettlementsBefore[$settlementNo])) {
                        $combinedSettlementsBefore[$settlementNo] = collect();
                    }
                    foreach ($salesRows as $saleRow) {
                        $combinedSettlementsBefore[$settlementNo]->push($saleRow);
                    }
                }
                
                // Add other sales
                foreach ($otherSettlementsBefore as $settlementNo => $salesRows) {
                    if (!isset($combinedSettlementsBefore[$settlementNo])) {
                        $combinedSettlementsBefore[$settlementNo] = collect();
                    }
                    foreach ($salesRows as $saleRow) {
                        $combinedSettlementsBefore[$settlementNo]->push($saleRow);
                    }
                }
                
                // Group combined sales by settlement and product to sum quantities
                $finalSettlementsBefore = collect();
                foreach ($combinedSettlementsBefore as $settlementNo => $salesRows) {
                    $grouped = $salesRows->groupBy('product_id')->map(function($group) {
                        $first = $group->first();
                        $first->total_qty = $group->sum('total_qty');
                        return $first;
                    });
                    $finalSettlementsBefore[$settlementNo] = $grouped;
                }
                
                // Get credit sales for same settlements
                $creditSettlementsBefore = DB::table('settlement_credit_sale_payments as scsp')
                    ->leftJoin('settlements as st', function ($join) {
                        $scspNo = DB::raw("CONVERT(scsp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $stId = DB::raw("CAST(st.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $stNo = DB::raw("CONVERT(st.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $join->on('st.business_id', '=', 'scsp.business_id')
                            ->on(function ($settlementKeyJoin) use ($scspNo, $stId, $stNo) {
                                $settlementKeyJoin->on($scspNo, '=', $stId)
                                    ->orOn($scspNo, '=', $stNo);
                            });
                    })
                    ->where('scsp.business_id', $business_id)
                    ->whereIn('scsp.product_id', $productIds)
                    ->whereDate(DB::raw('COALESCE(st.transaction_date, scsp.order_date)'), '<', $start_date)
                    ->when($locationId, function ($q) use ($locationId) {
                        $q->where('st.location_id', $locationId);
                    })
                    ->select(
                        DB::raw('COALESCE(st.settlement_no, scsp.settlement_no) as settlement_no'),
                        'scsp.product_id',
                        DB::raw('SUM(scsp.qty) as credit_qty')
                    )
                    ->groupBy('settlement_no', 'scsp.product_id')
                    ->get()
                    ->groupBy('settlement_no')
                    ->map(function ($group) {
                        return $group->keyBy('product_id');
                    });
                
                // Count settlements that have cash sales (total_qty - credit_qty > 0 for at least one product)
                $cashSettlementsCount = 0;
                foreach ($finalSettlementsBefore as $settlementNo => $salesRows) {
                    $hasCashSales = false;
                    foreach ($salesRows as $saleRow) {
                        $creditQty = isset($creditSettlementsBefore[$settlementNo][$saleRow->product_id]) 
                            ? (float) $creditSettlementsBefore[$settlementNo][$saleRow->product_id]->credit_qty 
                            : 0.0;
                        $saleQty = (float) $saleRow->total_qty;
                        $cashQty = $saleQty - $creditQty;
                        
                        if ($cashQty > 0) {
                            $hasCashSales = true;
                            break;
                        }
                    }
                    if ($hasCashSales) {
                        $cashSettlementsCount++;
                    }
                }
                
                $cashBillCounterStart = 1 + $cashSettlementsCount;
            }
            
            if ($form_type === 'Credit') {
                $billNoDisplayStart = $form14Setting->bill_no_form_14
                    ?? ($settings->bill_no_form_14 ?? 0);

                $displayOffset = 0;
                if (!empty($start_date)) {
                    $displayOffsetQuery = Transaction::where('is_credit_sale', true)
                        ->where('business_id', $business_id);

                    if ($locationId) {
                        $displayOffsetQuery->where('location_id', $locationId);
                    }

                    if (!empty($form14Setting?->F14_form_tdate)) {
                        try {
                            $settingDate = Carbon::parse($form14Setting->F14_form_tdate)->toDateString();
                            if ($settingDate != $start_date) {
                                $displayOffsetQuery->whereDate('transaction_date', '>=', $settingDate)
                                    ->whereDate('transaction_date', '<', $start_date);
                            }
                        } catch (\Exception $e) {
                            $displayOffsetQuery->whereDate('transaction_date', '<', $start_date);
                        }
                    } else {
                        $displayOffsetQuery->whereDate('transaction_date', '<', $start_date);
                    }

                    $displayOffset = $displayOffsetQuery->count();
                }

                $billNoDisplayStart += $displayOffset;
            }

            $totals = [];
            $data = [];

            /*
             * Keep Unit Sales Price independent from payment allocations. In Cash
             * mode card amounts are removed from cash value, but that must never
             * reduce the product's actual selling price.
             */
            $addToTotals = function (
                $productId,
                $qty,
                $amount,
                $priceBasisQty = null,
                $priceBasisAmount = null
            ) use (&$totals) {
                $key = (string) $productId;
                if (!isset($totals[$key])) {
                    $totals[$key] = [
                        'qty' => 0.0,
                        'unit_price' => 0.0,
                        'amount' => 0.0,
                        '_price_basis_qty' => 0.0,
                        '_price_basis_amount' => 0.0,
                    ];
                }

                $totals[$key]['qty'] += (float) $qty;
                $totals[$key]['amount'] += (float) $amount;
                $totals[$key]['_price_basis_qty'] += (float) ($priceBasisQty ?? $qty);
                $totals[$key]['_price_basis_amount'] += (float) ($priceBasisAmount ?? $amount);
            };

            if ($form_type === 'Credit') {
                foreach ($creditEntries as $index => $entry) {
                    $billNo = $billCounterStart + $index;
                    $displayBillNo = $billNoDisplayStart !== null ? $billNoDisplayStart + $index : $billNo;
                    $settlementNo = $entry->settlement_no ?? '';
                    $qty = (float) $entry->qty;
                    $unitPrice = (float) $entry->price;
                    $amount = (float) ($entry->amount ?? ($qty * $unitPrice));

                    $data[] = [
                        'bill_no' => $billNo,
                        'bill_no_display' => $displayBillNo,
                        'settlement_no' => $settlementNo,
                        'details' => [[
                            'product_id' => (string) $entry->product_id,
                            'product_name' => $entry->product_name,
                            'product_sku' => $entry->product_sku,
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'amount' => $amount,
                        ]],
                    ];

                    $addToTotals($entry->product_id, $qty, $amount, $qty, $amount);
                }
            } elseif ($form_type === 'Cash') {
                $groupedSales = $totalSales->groupBy('settlement_no');

                // Allocate card payments back to products proportionally so we can drop them from cash view
                $settlementTotals = [];
                foreach ($groupedSales as $settlementNo => $rows) {
                    $settlementTotals[$settlementNo] = $rows->sum(function ($row) {
                        return (float) $row->total_amount;
                    });
                }

                $cardAllocations = [];
                foreach ($groupedSales as $settlementNo => $rows) {
                    $cardAmount = (float) ($cardPayments[$settlementNo]->card_amount ?? 0);
                    $totalAmount = $settlementTotals[$settlementNo] ?? 0.0;

                    if ($cardAmount <= 0 || $totalAmount <= 0) {
                        continue;
                    }

                    foreach ($rows as $row) {
                        $priceBasisAmount = (float) ($row->price_basis_amount ?? $row->total_amount ?? 0);
                        $unitPrice = $row->total_qty != 0
                            ? ($priceBasisAmount / (float) $row->total_qty)
                            : 0.0;
                        $productAmount = (float) $row->total_amount;
                        $allocatedAmount = $productAmount > 0 ? ($productAmount / $totalAmount) * $cardAmount : 0.0;
                        $allocatedQty = $unitPrice > 0 ? $allocatedAmount / $unitPrice : 0.0;

                        $key = ($settlementNo ?? '') . '-' . $row->product_id;
                        $cardAllocations[$key] = [
                            'qty' => $allocatedQty,
                            'amount' => $allocatedAmount,
                        ];
                    }
                }

                // Generate bill numbers for Cash entries - use separate Cash bill counter
                $billCounter = $cashBillCounterStart;

                foreach ($groupedSales as $settlementNo => $rows) {
                    $details = [];

                    // dd($rows);
                    foreach ($rows as $row) {
                        $key = ($settlementNo ?? '') . '-' . $row->product_id;
                        $creditQty = isset($creditAggregates[$key]) ? (float) $creditAggregates[$key]->total_qty : 0.0;
                        $cardQty = (float) ($cardAllocations[$key]['qty'] ?? 0.0);
                        // dd($cardQty);
                        $cardAmount = (float) ($cardAllocations[$key]['amount'] ?? 0.0);
                        $saleQty = (float) $row->total_qty;
                        $priceBasisAmount = (float) ($row->price_basis_amount ?? $row->total_amount ?? 0);
                        $unitPrice = $saleQty != 0
                            ? ($priceBasisAmount / $saleQty)
                            : 0.0;
                        $saleAmount = $saleQty * $unitPrice;

                        // $rawCashQty = $saleQty - $creditQty - $cardQty;
                        $rawCashQty = $saleQty - $creditQty;
                        $cashQty = $rawCashQty < 0 ? 0.0 : $rawCashQty;
                        $rawCashAmount = $saleAmount - ($creditQty * $unitPrice) - $cardAmount;
                        $cashAmount = $rawCashAmount < 0 ? 0.0 : $rawCashAmount;

                        if ($cashQty > 0 || $cashAmount > 0) {
                            $details[] = [
                                'product_id' => (string) $row->product_id,
                                'product_name' => $row->product_name,
                                'product_sku' => $row->product_sku,
                                'qty' => $cashQty,
                                'unit_price' => $unitPrice,
                                'amount' => $cashAmount,
                            ];
                            $addToTotals($row->product_id, $cashQty, $cashAmount, $saleQty, $saleAmount);
                        }
                    }
                    // dd($details);

                    if (!empty($details)) {
                        $data[] = [
                            'bill_no' => $billCounter,
                            'settlement_no' => $settlementNo,
                            'details' => $details,
                        ];
                        $billCounter++;
                    }
                }
            } else {
                // All: show settlements only, hide bill numbers on UI
                $groupedSales = $totalSales->groupBy('settlement_no');
                foreach ($groupedSales as $settlementNo => $rows) {
                    $details = [];

                    foreach ($rows as $row) {
                        $qty = (float) $row->total_qty;
                        $priceBasisAmount = (float) ($row->price_basis_amount ?? $row->total_amount ?? 0);
                        $unitPrice = $qty != 0
                            ? ($priceBasisAmount / $qty)
                            : 0.0;
                        $amount = $qty * $unitPrice;

                        $details[] = [
                            'product_id' => (string) $row->product_id,
                            'product_name' => $row->product_name,
                            'product_sku' => $row->product_sku,
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'amount' => $amount,
                        ];
                        $addToTotals($row->product_id, $qty, $amount, $qty, $amount);
                    }

                    $data[] = [
                        'bill_no' => '',
                        'settlement_no' => $settlementNo,
                        'details' => $details,
                    ];
                }
            }

            foreach ($totals as $pid => &$t) {
                $priceBasisQty = (float) ($t['_price_basis_qty'] ?? 0);
                $priceBasisAmount = (float) ($t['_price_basis_amount'] ?? 0);
                $allocatedAmount = (float) ($t['amount'] ?? 0);

                $t['qty'] = round((float) $t['qty'], 3);
                $t['unit_price'] = $priceBasisQty > 0
                    ? round($priceBasisAmount / $priceBasisQty, 2)
                    : ($t['qty'] > 0 ? round($allocatedAmount / $t['qty'], 2) : 0.00);

                /*
                 * IS1727: For Cash forms the footer Total Amount must follow the
                 * printed form formula exactly:
                 *
                 *     Total Amount = Total Qty x Unit Sales Price
                 *
                 * Do not reuse the payment-allocation amount here because card
                 * allocations can reduce that value while the displayed cash
                 * quantity and actual unit selling price remain unchanged.
                 */
                $t['amount'] = $form_type === 'Cash'
                    ? round($t['qty'] * $t['unit_price'], 2)
                    : round($allocatedAmount, 2);

                unset($t['_price_basis_qty'], $t['_price_basis_amount']);
            }
            unset($t);

            return response()->json([
                'products' => $data,
                'totals' => (object) $totals,
            ]);
            
            } catch (\Exception $e) {
                \Log::error('F20 getForm20Data exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'request' => $request->all(),
                ]);
                
                return response()->json([
                    'products' => [],
                    'totals' => [],
                    'message' => 'An error occurred while loading data: ' . $e->getMessage(),
                ], 500);
            }
        }

        /**
         * Helper function to calculate the adjusted form number.
         */
        private function calculateFormNumber($settings, $form_date, $form_type)
        {
            if (!$settings) {
                return 1;
            }

            $normalizedType = strtolower(trim((string) $form_type));
            $baseNumber = match ($normalizedType) {
                'credit' => (int) ($settings->credit_sale ?? 0),
                'cash' => (int) ($settings->cash_sale ?? 0),
                default => (int) ($settings->total_sale ?? $settings->starting_number ?? 1),
            };

            $openingDateValue = $settings->opening_date ?? $settings->date ?? null;
            if (empty($openingDateValue)) {
                return $baseNumber;
            }

            // The UI may send a single date or a complete date range. F20 numbering
            // follows the first date in the selected range.
            $selectedDateValue = trim((string) $form_date);
            if (Str::contains($selectedDateValue, '~')) {
                $selectedDateValue = trim(explode('~', $selectedDateValue, 2)[0]);
            } elseif (Str::contains($selectedDateValue, ' - ')) {
                $selectedDateValue = trim(explode(' - ', $selectedDateValue, 2)[0]);
            }

            try {
                $openingDate = Carbon::parse($openingDateValue)->startOfDay();
                $selectedDate = Carbon::parse($selectedDateValue)->startOfDay();
            } catch (\Throwable $exception) {
                return $baseNumber;
            }

            if ($selectedDate->equalTo($openingDate)) {
                return $baseNumber;
            }

            $dayDifference = $openingDate->diffInDays($selectedDate);

            return $selectedDate->greaterThan($openingDate)
                ? $baseNumber + $dayDifference
                : max(0, $baseNumber - $dayDifference);
        }

public function fetchFormNumber(Request $request)
{
    $business_id = $request->session()->get('user.business_id')
        ?? optional(auth()->user())->business_id
        ?? $request->session()->get('business.id');

    abort_if(empty($business_id), 403, 'Business context is missing.');

    $form_type = $request->get('form_type') ?? 'all';
    $form_date = $request->get('form_date') ?? now()->toDateString();
    $cacheKey = 'form_number_' . $business_id . '_' . strtolower((string) $form_type) . '_' . md5((string) $form_date);

    if (function_exists('cache')) {
        $cached = cache()->get($cacheKey);
        if ($cached !== null) {
            return response()->json(['form_number' => $cached]);
        }
    }

    $settings = Mpcs20FormSettings::where('business_id', $business_id)
        ->orderByDesc('id')
        ->first();

    $F20_form_sn = $this->calculateFormNumber($settings, $form_date, $form_type);

    if (function_exists('cache')) {
        cache()->put($cacheKey, $F20_form_sn, 1800);
    }

    return response()->json(['form_number' => $F20_form_sn]);
}

    public function get20FormSettings()
    {

        $business_id = request()->session()->get('user.business_id');
        $pumps = [];
        if (auth()->user()->can('superadmin')) {
            $categories = Category::where('parent_id', '!=', 0)->select(['name', 'id'])
                ->get();

        } else {
            $categories = Category::where('parent_id', '!=', 0)->where('business_id', $business_id)
                ->select(['name', 'id'])
                ->get();
        }
        $products = Product::whereIn('sub_category_id', $categories->pluck('id')->toArray())
            ->select(['name', 'id', 'sub_category_id'])->get();

        return view('mpcs::forms.20Form.create_20_form_settings', compact('categories', 'pumps', 'products'));

    }

    public function store20FormSettings(Request $request)
    {

        $business_id = session()->get('user.business_id');
        $category = is_array($request->category)
            ? implode(',', $request->category)
            : $request->category;

        $product = is_array($request->product)
            ? implode(',', $request->product)
            : $request->product;

        // Prepare the data for insertion
        $formData = [
            'business_id' => $business_id,
            'opening_date' => $request->date,
            'starting_number' => $request->starting_number,
            'total_sale' => $request->total_sale,
            'cash_sale' => $request->cash_sale,
            'credit_sale' => $request->credit_sale,
            'category' => $category,
            'product' => $product,
            'created_by' => Auth::user()->id
        ];


        if (Mpcs20FormSettings::where('business_id', $business_id)->where('opening_date', $request->date)->where('starting_number', $request->starting_number)->where('total_sale', $request->total_sale)->where('cash_sale', $request->cash_sale)->where('credit_sale', $request->credit_sale)->where('category', $request->selected_categories)->doesntExist()) {
            // Insert into database
            Mpcs20FormSettings::create($formData);
        }

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_16a_settings_add_success')
        ];

        return $output;
    }

    public function mpcs20FormSettings()
    {
        if (request()->ajax()) {
            $header = Mpcs20FormSettings::select('*');
            $business_id = request()->session()->get('user.business_id');
            return DataTables::of($header)
                ->addColumn('action', function ($row) {
                    // if (auth()->user()->can('superadmin')) {
                        return '<button href="#" data-href="' . url('/mpcs/edit-20-form-settings/' . $row->id) . '" class="btn-modal btn btn-primary btn-xs" data-container=".update_form_16_a_settings_modal"><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</button>';
                    // }
                    return '';
                })
                //  ->editColumn('starting_number', function($row) {
                //     return $row->starting_number;
                // })
                ->addColumn('opening_date', function ($row) {
                    return $row->opening_date;
                })
                ->editColumn('total_sale', function ($row) {
                    return $row->total_sale;
                })
                ->editColumn('cash_sale', function ($row) {
                    return $row->cash_sale;
                })
                ->editColumn('credit_sale', function ($row) {
                    return $row->credit_sale;
                })
                ->editColumn('category', function ($row) {
                    $categoryId = explode(",", $row->category);
                    $category = Category::whereIn('id', $categoryId)
                        ->select(['name', 'id'])
                        ->get();
                    $html = '<div class="f20-settings-items">';
                    foreach ($category as $cat) {
                        $html .= '<span class="badge badge-primary">' . $cat->name . '</span>';
                    }
                    return $html . '</div>';
                })
                ->editColumn('product', function ($row) {
                    $productIds = explode(",", $row->product);
                    $products = Product::whereIn('id', $productIds)
                        ->select(['name', 'id'])
                        ->get();
                    $html = '<div class="f20-settings-items">';
                    foreach ($products as $product) {
                        $html .= '<span class="badge badge-primary">' . $product->name . '</span>';
                    }
                    return $html . '</div>';
                })
                ->rawColumns(['action', 'opening_date', 'total_sale', 'cash_sale', 'credit_sale', 'category', 'product'])
                ->make(true);
        }
    }

    public function edit20FormSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = Mpcs20FormSettings::where('business_id', $business_id)->where('id', $id)->first();

        if (auth()->user()->can('superadmin')) {
            $categories = Category::where('parent_id', '!=', 0)->select(['name', 'id'])
                ->get();
        } else {
            $categories = Category::where('parent_id', '!=', 0)->where('business_id', $business_id)
                ->select(['name', 'id'])
                ->get();
        }
        $products = Product::whereIn('sub_category_id', array_keys($categories->pluck('id')->toArray()))
            ->select(['name', 'id', 'sub_category_id'])->get();
        return view('mpcs::forms.20Form.edit_20_form_settings')->with(compact(
            'categories',
            'settings',
            'products'
        ));
    }


    public function mpcs20Update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $prev20cDet = Mpcs20FormSettings::find($id);
        if ($prev20cDet) {
            $category = is_array($request->category)
                ? implode(',', $request->category)
                : $request->category;

            $product = is_array($request->product)
                ? implode(',', $request->product)
                : $request->product;

            $formData = [
                'business_id' => $business_id,
                'opening_date' => $request->date,
                'starting_number' => $request->starting_number,
                'total_sale' => $request->total_sale,
                'cash_sale' => $request->cash_sale,
                'credit_sale' => $request->credit_sale,
                'category' => $category,
                'product' => $product,
                'created_by' => Auth::user()->id
            ];

            $prev20cDet->update($formData);
        }

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_21c_settings_update_success')
        ];

        return $output;
    }

    public function getFrom20Data()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS BS',
                    'transactions.location_id',
                    '=',
                    'BS.id'
                )
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->leftJoin(
                    'transactions AS PR',
                    'transactions.id',
                    '=',
                    'PR.return_parent_id'
                )
                ->leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->select(
                    'transactions.id',
                    'transactions.ref_no as reference_no',
                    'transaction_sell_lines.quantity as sold_qty',
                    'transaction_sell_lines.unit_price as unit_price',
                    'transactions.final_total as total_purchase_price',
                    'transactions.is_credit_sale',
                    'BS.name as location',
                    'products.sku as sku',
                    'products.name as product',
                    'products.id as product_id',
                    'TP.method as payment_method',
                    'variations.default_sell_price',
                    'transactions.pay_term_number',
                    'transactions.pay_term_type',
                    'PR.id as return_transaction_id',
                    DB::raw('SUM(TP.amount) as amount_paid'),
                    DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=PR.id ) as return_paid'),
                    DB::raw('COUNT(PR.id) as return_exists'),
                    DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                )
                ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $purchases->where('transactions.location_id', request()->location_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $business_id = session()->get('user.business_id');
            $business_details = Business::find($business_id);
    // dd($purchases->get());
            return DataTables::of($purchases)
                ->addIndexColumn()
                ->addColumn('total_amount', function ($row) use ($business_details) {
                    $total_amount = $row->sold_qty * $row->unit_price;
                    $html = '';
                    if ($row->payment_method == 'cash' || $row->payment_method == 'card' || $row->payment_method == 'cheque') {
                         $html .='<span class="display_currency cash_sale" data-orig-value="' . $total_amount . '" data-currency_symbol = "false">' . $this->productUtil->num_f($total_amount, false, $business_details, false) . '</span>';
                    }
                    if ($row->is_credit_sale == 1) {
                         $html .='<span class="display_currency credit_sale" data-orig-value="' . $total_amount . '" data-currency_symbol = "false">' . $this->productUtil->num_f($total_amount, false, $business_details, false) . '</span>';
                    }
                    return $html;
                })
                ->addColumn('unit_price', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->unit_price, false, $business_details, false);
                })
                ->addColumn('sold_qty', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->sold_qty, false, $business_details, true);
                })
                ->removeColumn('id')

                ->rawColumns(['total_amount', 'unit_price'])
                ->make(true);
        }
    }
}
