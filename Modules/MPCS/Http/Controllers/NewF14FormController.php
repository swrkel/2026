<?php
namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\MPCS\Entities\MpcsFormSetting;

class NewF14FormController extends Controller
{
    const FUEL_QTY_DECIMALS = 3;
    public function index()
    {
        Log::info('here in NewF14FormController');
        if (! auth()->check()) {
            return redirect()->route('login');
        }
        $business_id               = request()->session()->get('user.business_id');
        $business                  = Business::where('id', $business_id)->first();
        $setting                   = MpcsFormSetting::where('business_id', $business_id)->first();
        $business_locations        = BusinessLocation::forDropdown($business_id)->toArray();
        $businesslocationkeys      = array_keys($business_locations);
        $default_business_location = isset($businesslocationkeys[0]) ? $businesslocationkeys[0] : '';
        $fuel_qty_decimals         = self::FUEL_QTY_DECIMALS;

        $startdate = date('Y-m-01');
        $enddate   = date('Y-m-t');

        // Get all credit sale transactions for the table
        $transactions = Transaction::where('business_id', $business_id)
            ->where('is_credit_sale', 1)
            ->orderBy('transaction_date', 'desc')
            ->limit(100) // Limit to last 100 transactions for performance
            ->get();

        return view('mpcs::forms.F14')->with(compact(
            'business_locations',
            'setting',
            'business',
            'business_id',
            'fuel_qty_decimals',
            'startdate',
            'enddate',
            'default_business_location',
            'transactions'
        ));
    }

    public function getForm14()
    {

        Log::info('in getForm14');

        $business_id = request()->session()->get('user.business_id');

        $startdate = date('Y-m-d', strtotime('-1 day'));
        $enddate   = date('Y-m-d');

        if (request()->has('date_range')) {
            // Use input() so merged params from getForm14WithDates (e.g. 9C Credit same-date) are used; query() only sees URL params
            $range = request()->input('date_range');
            if ($range && ! empty(trim($range))) {
                // Handle URL encoding - replace + with spaces
                $range = str_replace('+', ' ', $range);

                // Check if range contains a dash (date range) or is a single date
                if (strpos($range, ' - ') !== false) {
                    // Date range format: "YYYY-MM-DD - YYYY-MM-DD"
                    $dates = explode(' - ', $range);
                    if (count($dates) == 2) {
                        $startdate = trim($dates[0]);
                        $enddate   = trim($dates[1]);

                        // Validate dates using try-catch
                        try {
                            Carbon::parse($startdate);
                            Carbon::parse($enddate);
                            Log::info("Date range parsed successfully: $startdate to $enddate");
                        } catch (\Exception $e) {
                            $startdate = date('Y-m-d', strtotime('-1 day'));
                            $enddate   = date('Y-m-d');
                            Log::error("Date parsing failed: " . $e->getMessage());
                        }
                    }
                } else {
                    // Single date format: "YYYY-MM-DD"
                    try {
                        Carbon::parse($range);
                        $startdate = $enddate = $range;
                    } catch (\Exception $e) {
                        // Invalid date, use defaults
                        $startdate = date('Y-m-d', strtotime('-1 day'));
                        $enddate   = date('Y-m-d');
                    }
                }
            }
        }

        $totalBeforeStartDateQuery = Transaction::where('is_credit_sale', true)
            ->where('business_id', $business_id)
            ->whereDate('transaction_date', '<', $startdate);

        // Build from settlement_credit_sale_payments so we get all bills; join transactions via credit_sale_id OR transaction_id
        $query = DB::table('settlement_credit_sale_payments as scsp')
            ->leftJoin('transactions', function ($join) {
                $join->on('transactions.credit_sale_id', '=', 'scsp.id')
                    ->orOn('scsp.transaction_id', '=', 'transactions.id');
            })
            ->leftJoin('settlements', function ($join) {
                $scspNo = DB::raw('CONVERT(scsp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci');
                $stId = DB::raw('CAST(settlements.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci');
                $stNo = DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci');
                $join->on($scspNo, '=', $stId)->orOn($scspNo, '=', $stNo);
            })
            ->leftJoin('products', 'scsp.product_id', '=', 'products.id')
            // Join transaction_sell_lines to get the historical unit price at time of sale
            ->leftJoin('transaction_sell_lines', function ($join) {
                $join->on('transaction_sell_lines.transaction_id', '=', 'transactions.id')
                     ->on('transaction_sell_lines.product_id', '=', 'scsp.product_id');
            })
            ->leftJoin('contacts', 'scsp.customer_id', '=', 'contacts.id')
            ->leftJoin('business', 'transactions.business_id', '=', 'business.id')
            ->leftJoin('business_locations', function ($join) {
                $join->on(DB::raw('COALESCE(transactions.location_id, settlements.location_id)'), '=', 'business_locations.id');
            })
            ->where('scsp.business_id', $business_id)
            ->where(function ($q) use ($startdate, $enddate) {
                $q->whereBetween(DB::raw('COALESCE(DATE(transactions.transaction_date), scsp.order_date)'), [$startdate, $enddate]);
            })
            ->select(
                DB::raw('COALESCE(DATE(transactions.transaction_date), scsp.order_date) as settlement_date'),
                'transactions.final_total',
                'transactions.is_credit_sale',
                'products.name as description',
                'products.category_id as category',
                'scsp.qty as balance_qty',
                'scsp.order_date as order_date',
                // Use scsp.price (stored at time of sale) as the historical unit price.
                // This never changes even if the product price is changed later.
                'scsp.price as unit_price',
                // sell_price_inc_tax: prefer transaction_sell_lines.unit_price_inc_tax (historical)
                // fallback to scsp.price so we NEVER pull current live variation price
                DB::raw('COALESCE(transaction_sell_lines.unit_price_inc_tax, scsp.price) as sell_price_inc_tax'),
                'transactions.ref_no as our_ref',
                'transactions.invoice_no',
                'contacts.name as customer',
                'scsp.id as bill_no',
                DB::raw('COALESCE(NULLIF(scsp.order_number, 0), transactions.invoice_no, scsp.id) as order_no'),
                'settlements.settlement_no as sattlement_no',
                'scsp.customer_reference as customer_reference',
                // Use stored bill_number for voucher display; fallback to invoice_no
                DB::raw('COALESCE(NULLIF(scsp.bill_number, \'\'), transactions.invoice_no, scsp.id) as voucher_no'),
                'business.name as company',
                'business.quantity_precision as quantity_precision',
                'business.currency_precision as currency_precision',
                'business_locations.mobile as tel',
                // Add product-wise amount from settlement_credit_sale_payments
                'scsp.amount as product_amount',
                'scsp.sub_total as product_sub_total'
            );
        $locId = request()->input('business_location_id');
        $applyLocation = $locId !== null && $locId !== '' && $locId !== 'null' && $locId !== 'ALL';
        if ($applyLocation) {
            $query->where(function ($q) use ($locId) {
                $q->where('transactions.location_id', $locId)
                    ->orWhere('settlements.location_id', $locId);
            });
            $totalBeforeStartDateQuery->where('location_id', $locId);
        }

        $fuelSubCategory   = Category::subCategoryOnlyFuel($business_id)->pluck('id')->toArray();
        $fuelCategory = Category::where('categories.name', 'Fuel')->first();
        if ($fuelCategory) {
            $fuelSubCategory[] = $fuelCategory->id;
        }

        // Debug: Check if there are any transactions in the date range
        $debug_transactions = Transaction::where('is_credit_sale', true)
            ->where('business_id', $business_id)
            ->whereDate('transaction_date', '>=', $startdate)
            ->whereDate('transaction_date', '<=', $enddate)
            ->count();

        Log::info("Debug: Found $debug_transactions transactions in date range $startdate to $enddate");

        $credit_sales = $query->orderBy(DB::raw('COALESCE(settlements.id, scsp.id)'), 'desc')->get();

        Log::info("Debug: Complex query returned " . $credit_sales->count() . " records");

        // Also get direct credit sales that haven't been settled yet
        $unsettled_query = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftJoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftJoin('variations', 'products.id', 'variations.product_id')
            ->leftJoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftJoin('business', 'transactions.business_id', 'business.id')
            ->leftJoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.is_credit_sale', true)
            ->where('transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '>=', $startdate)
            ->whereDate('transactions.transaction_date', '<=', $enddate)
            ->whereNotIn('transactions.id', function($query) use ($business_id) {
                $query->select('transaction_id')
                      ->from('settlement_credit_sale_payments')
                      ->where('business_id', $business_id)
                      ->whereNotNull('transaction_id');
            })
            ->select(
                'transactions.transaction_date as settlement_date',
                'transactions.final_total',
                'transactions.is_credit_sale',
                'products.name as description',
                'products.category_id as category',
                'transaction_sell_lines.quantity as balance_qty',
                'transactions.transaction_date as order_date',
                'transaction_sell_lines.unit_price as unit_price',
                'variations.sell_price_inc_tax as sell_price_inc_tax',
                'transactions.ref_no as our_ref',
                'transactions.invoice_no',
                'contacts.name as customer',
                'transactions.id as bill_no',
                'transactions.invoice_no as order_no',
                'transactions.id as sattlement_no',
                'transactions.ref_no as customer_reference',
                'business.name as company',
                'business.quantity_precision as quantity_precision',
                'business.currency_precision as currency_precision',
                'business_locations.mobile as tel',
                DB::raw('transactions.invoice_no as voucher_no'),
                DB::raw('transaction_sell_lines.unit_price_inc_tax as product_amount'),
                DB::raw('transaction_sell_lines.line_total as product_sub_total')
            );

        if ($applyLocation) {
            $unsettled_query->where('transactions.location_id', $locId);
        }

        $unsettled_sales = $unsettled_query->orderBy('transactions.transaction_date', 'desc')->get();
        Log::info("Debug: Unsettled query returned " . $unsettled_sales->count() . " records");
        Log::info("Debug: Unsettled POS credit sales count: " . $unsettled_sales->where('is_credit_sale', 1)->count());

        // Combine settled and unsettled sales
        $all_sales = $credit_sales->concat($unsettled_sales);
        $credit_sales = $all_sales;
        
        Log::info("Debug: Combined total records: " . $credit_sales->count());

        // Fallback: If no data from settlement_credit_sale_payments, try direct transaction query
        if ($credit_sales->isEmpty()) {
            Log::info("No data from settlement query, trying fallback direct transaction query");
            
            $fallback_query = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                ->leftJoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftJoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('contacts', 'transactions.contact_id', 'contacts.id')
                ->leftJoin('business', 'transactions.business_id', 'business.id')
                ->leftJoin('business_locations', 'transactions.location_id', 'business_locations.id')
                ->where('transactions.is_credit_sale', true)
                ->where('transactions.business_id', $business_id)
                ->whereDate('transactions.transaction_date', '>=', $startdate)
                ->whereDate('transactions.transaction_date', '<=', $enddate)
                ->select(
                    'transactions.transaction_date as settlement_date',
                    'transactions.final_total',
                    'transactions.is_credit_sale',
                    'products.name as description',
                    'products.category_id as category',
                    'transaction_sell_lines.quantity as balance_qty',
                    'transactions.transaction_date as order_date',
                    'transaction_sell_lines.unit_price as unit_price',
                    'variations.sell_price_inc_tax as sell_price_inc_tax',
                    'transactions.ref_no as our_ref',
                    'transactions.invoice_no',
                    'contacts.name as customer',
                    'transactions.id as bill_no',
                    'transactions.invoice_no as order_no',
                    'transactions.id as sattlement_no',
                    'transactions.ref_no as customer_reference',
                    'business.name as company',
                    'business.quantity_precision as quantity_precision',
                    'business.currency_precision as currency_precision',
                    'business_locations.mobile as tel'
                );

            if ($applyLocation) {
                $fallback_query->where('transactions.location_id', $locId);
            }

            $credit_sales = $fallback_query->orderBy('transactions.transaction_date', 'desc')->get();
            Log::info("Debug: Fallback query returned " . $credit_sales->count() . " records");
        }

        foreach ($credit_sales as &$sale) {
            $sale->is_fuel = in_array($sale->category, $fuelSubCategory);
            $sale->date    = date('m/d/Y', strtotime($sale->settlement_date));

            // F14 receipt line amount: prefer line-level fields; transactions.final_total is often null on
            // settlement rows and is invoice-wide anyway. Never send null/unparseable final_total to the UI.
            $qty          = (float) ($sale->balance_qty ?? 0);
            $unit         = (float) ($sale->unit_price ?? 0);
            $fromQtyPrice = $qty * $unit;

            $display = null;
            foreach (['product_sub_total', 'product_amount', 'final_total'] as $field) {
                $raw = $sale->{$field} ?? null;
                if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                    $display = (float) $raw;
                    break;
                }
            }

            // Unsettled query aliases unit_price_inc_tax as product_amount; if we only have that, it matches unit not line total.
            $subRaw = $sale->product_sub_total ?? null;
            $hasSub = $subRaw !== null && $subRaw !== '' && is_numeric($subRaw) && (float) $subRaw != 0;
            if (! $hasSub && $qty > 0 && isset($sale->product_amount) && is_numeric($sale->product_amount)) {
                $pa = (float) $sale->product_amount;
                if (abs($pa - $unit) < 0.00001 || abs($pa - (float) ($sale->sell_price_inc_tax ?? 0)) < 0.00001) {
                    $display = $fromQtyPrice;
                }
            }

            if ($display === null || $display !== $display) {
                $display = $fromQtyPrice;
            }
            $sale->final_total = $display;
        }
        unset($sale);

        $totalBeforeStartDate = $totalBeforeStartDateQuery->count();

        // Get current setting for bill number
        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        // Calculate total_before_startdate based on settings
        if ($setting && $setting->F14_form_tdate) {
            // Convert setting date to Y-m-d format for comparison
            $setting_date = Carbon::parse($setting->F14_form_tdate)->format('Y-m-d');

            Log::info("Setting date: " . $setting->F14_form_tdate . " -> " . $setting_date);
            Log::info("Selected startdate: " . $startdate);
            Log::info("Setting F14_form_sn: " . $setting->F14_form_sn);

            if ($setting_date != $startdate) {
                // If selected date is different from setting date, calculate the offset
                // Count all transactions from setting date up to (but not including) selected date
                $previous_transactions_query = Transaction::where('is_credit_sale', true)
                    ->where('business_id', $business_id)
                    ->whereDate('transaction_date', '>=', $setting_date)
                    ->whereDate('transaction_date', '<', $startdate);

                if ($applyLocation) {
                    $previous_transactions_query->where('location_id', $locId);
                }

                $totalBeforeStartDate = $previous_transactions_query
                    ->select(DB::raw('DATE(transaction_date) as date'))
                    ->distinct()
                    ->get()
                    ->count();
                Log::info("Different date - totalBeforeStartDate: " . $totalBeforeStartDate);
            } else {
                // Same date as setting, start from 0
                $totalBeforeStartDate = 0;
                Log::info("Same date - totalBeforeStartDate: " . $totalBeforeStartDate);
            }
        }

        // Debug information
        $debug_info = [
            'business_id'    => $business_id,
            'startdate'      => $startdate,
            'enddate'        => $enddate,
            'total_records'  => $credit_sales->count(),
            'query_sql'      => $query->toSql(),
            'query_bindings' => $query->getBindings(),
        ];

        $grouped = $credit_sales->groupBy('date');

        // Build form_no_by_date: auto-increment form number for each date that has bills
        $formNoByDate = [];
        $baseFormNo = $setting ? (int)($setting->F14_form_sn ?? 1) : 1;
        $dateOffset = $totalBeforeStartDate;
        $sortedDates = $grouped->keys()->sort()->values();
        foreach ($sortedDates as $idx => $date) {
            if ($grouped[$date]->isNotEmpty()) {
                $formNoByDate[$date] = $baseFormNo + $dateOffset + $idx;
            }
        }

        // Billing-day form number for the filter header only
        $filterF14bFormNo = null;
        try {
            $filterF14bFormNo = $this->getFilterF14bFormNo(
                $business_id, $startdate, $enddate, $setting, $applyLocation, $locId
            );
        } catch (\Exception $e) {
            Log::error('filter_f14b_form_no calculation failed: ' . $e->getMessage());
        }

        return response()->json([
            'total_before_startdate' => $totalBeforeStartDate,
            'data'                   => $grouped->toArray(),
            'form_no_by_date'        => $formNoByDate,
            'setting'                => $setting,
            'filter_f14b_form_no'    => $filterF14bFormNo,
        ]);
    }

    /**
     * Helper method to get F14 data with specific parameters for 9C Credit form
     */
    public function getForm14WithDates($business_id, $start_date, $end_date, $location_id = null)
    {
        // Use the existing getForm14 logic but with specific parameters
        request()->merge([
            'date_range' => $start_date == $end_date ? $start_date : $start_date . ' - ' . $end_date,
            'business_location_id' => $location_id ?? 'ALL'
        ]);
        
        return $this->getForm14();
    }

    /**
     * Compute the filter "F14B Form No" using billing-day ranking.
     * Only days with actual credit-sale activity (settled OR unsettled) advance the counter.
     */
    private function getFilterF14bFormNo(
        int $business_id,
        string $startdate,
        string $enddate,
        $setting,
        bool $applyLocation,
        $locId
    ): ?int {
        if (! $setting || ! $setting->F14_form_sn) {
            return null;
        }

        $baseFormNo = (int) $setting->F14_form_sn;
        $anchorDate = $setting->F14_form_tdate
            ? Carbon::parse($setting->F14_form_tdate)->format('Y-m-d')
            : $startdate;

        $billDateExpr = 'COALESCE(DATE(transactions.transaction_date), scsp.order_date)';

        // Settled credit-sale dates
        $settledQuery = DB::table('settlement_credit_sale_payments as scsp')
            ->leftJoin('transactions', function ($join) {
                $join->on('transactions.credit_sale_id', '=', 'scsp.id')
                    ->orOn('scsp.transaction_id', '=', 'transactions.id');
            })
            ->leftJoin('settlements', function ($join) {
                $scspNo = DB::raw('CONVERT(scsp.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci');
                $stId   = DB::raw('CAST(settlements.id AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci');
                $stNo   = DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci');
                $join->on($scspNo, '=', $stId)->orOn($scspNo, '=', $stNo);
            })
            ->where('scsp.business_id', $business_id)
            ->whereBetween(DB::raw($billDateExpr), [$anchorDate, $enddate]);

        if ($applyLocation) {
            $settledQuery->where(function ($q) use ($locId) {
                $q->where('transactions.location_id', $locId)
                    ->orWhere('settlements.location_id', $locId);
            });
        }

        $settledDates = $settledQuery
            ->select(DB::raw("DISTINCT {$billDateExpr} as bill_date"))
            ->pluck('bill_date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));

        // Unsettled credit-sale dates
        $unsettledQuery = DB::table('transactions')
            ->where('transactions.is_credit_sale', true)
            ->where('transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '>=', $anchorDate)
            ->whereDate('transactions.transaction_date', '<=', $enddate)
            ->whereNotIn('transactions.id', function ($sub) use ($business_id) {
                $sub->select('transaction_id')
                    ->from('settlement_credit_sale_payments')
                    ->where('business_id', $business_id)
                    ->whereNotNull('transaction_id');
            });

        if ($applyLocation) {
            $unsettledQuery->where('transactions.location_id', $locId);
        }

        $unsettledDates = $unsettledQuery
            ->select(DB::raw('DISTINCT DATE(transactions.transaction_date) as bill_date'))
            ->pluck('bill_date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));

        // Merge, sort, rank
        $allBillingDays = $settledDates->merge($unsettledDates)
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Find rank of $startdate (single-day) or first billing day in range
        $targetDate = $startdate;
        $rank = array_search($targetDate, $allBillingDays);

        if ($rank === false) {
            return null;
        }

        return $baseFormNo + $rank;
    }
}
