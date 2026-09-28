<?php
namespace Modules\MPCS\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\MergedSubCategory;
use App\Product;
use App\Transaction;
use App\Variation;
use App\AccountGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\FormF16Details;
use Modules\MPCS\Entities\Form9cSubCategory;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF22Detail;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\Mpcs9aFormSettings;
use Modules\MPCS\Entities\Mpcs9aFormTextDetail;
use Modules\MPCS\Entities\Mpcs9cCashFormSettings;
use Modules\MPCS\Entities\Mpcs9cCreditFormSettings;
use Modules\MPCS\Entities\Mpcs16aFormSettings;
use Modules\MPCS\Entities\Mpcs21cFormSettings;
use Modules\MPCS\Entities\MpcsFormSetting;
use Modules\MPCS\Services\FormHelper;
use Modules\MPCS\Entities\Pump;
use Yajra\DataTables\Facades\DataTables;
use Modules\MPCS\Http\Controllers\NewF14FormController;

class MPCSController extends Controller
{
    public function FromSet1()
    {
        // Task 7788: Block access to Form Set 1
        abort(404, 'This page is no longer available.');
        
        if (! auth()->check()) {
            return Redirect::route('login');
        }
        $business_id = request()->session()->get('business.id');
        $settings    = MpcsFormSetting::where('business_id', $business_id)->first();
        if (! empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (! empty($settings)) {
            $F16a_from_no = $settings->F16A_form_sn;
        } else {
            $F16a_from_no = 1;
        }
        if (! empty($settings)) {
            $F21c_from_no = $settings->F21C_form_sn;
        } else {
            $F21c_from_no = 1;
        }
        if (! empty($settings)) {
            $F15a9ab_from_no = $settings->F159ABC_form_sn;
        } else {
            $F15a9ab_from_no = 1;
        }

        $business_location_name = BusinessLocation::where('business_id', $business_id)->pluck('name')->first();
        $ref_pre_form_number    = Mpcs9cCashFormSettings::where('business_id', $business_id)->orderBy('id', 'desc')->first();
        if (! empty($ref_pre_form_number)) {
            $form_9a_no = $ref_pre_form_number->starting_number;
        } else {
            $form_9a_no = 1;
        }

        $business_locations    = BusinessLocation::forDropdown($business_id);
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();

        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms.form_set_1')->with(compact('business_locations', 'F9C_sn', 'business_location_name', 'form_9a_no', 'F16a_from_no', 'F21c_from_no', 'F15a9ab_from_no', 'merged_sub_categories', 'sub_categories', 'setting'));
    }

    public function From9A()
    {
        $business_id = request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? request()->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');

        // Forms are location-specific. Load real active locations in creation order
        // and default to the first-created location while keeping the dropdown editable.
        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('id', 'asc')
            ->select(
                DB::raw("IF(location_id IS NULL OR location_id = '', name, CONCAT(name, ' (', location_id, ')')) AS display_name"),
                'id'
            )
            ->pluck('display_name', 'id');

        $default_location_id = $business_locations->keys()->first();
        $default_location_name = $default_location_id !== null
            ? $business_locations->get($default_location_id)
            : '';

        // Get form settings - use oldest setting for cumulative form numbering
        $form_settings = Mpcs9aFormSettings::where('business_id', $business_id)->orderBy('date', 'asc')->first();

        $form_9a_no = $form_settings ? $form_settings->starting_number : '';
        $date       = $form_settings ? $form_settings->date : '';

        // Get business name
        $business = Business::where('id', $business_id)->select('name')->first();

        $name = $business ? $business->name : '';

        // Default form number
        $form_number = 1;

        if (! empty($form_settings) && ! empty($form_settings->starting_number) && ! empty($form_settings->date)) {
            try {
                $current_date = \Carbon\Carbon::today();
                $starting_day = \Carbon\Carbon::parse($form_settings->date);
                $days_passed  = $starting_day->diffInDays($current_date, false);

                // Ensure form number is at least the starting number
                $form_number = (int) $form_settings->starting_number + (int) $days_passed;
            } catch (\Exception $e) {
                Log::error("Error parsing 9A form date: " . $e->getMessage());
                $form_number = $form_settings->starting_number;
            }
        }

        // Data required by 9a_settings_form partial (bank/card accounts and manual values)
        $business_bank_accounts = Account::where('business_id', $business_id)
            ->where('is_business_bank_account', 1)
            ->orderBy('name')
            ->get();
        $card_group_biz = DB::table('account_groups')->where('business_id', $business_id)->where('name', 'Card')->first();
        $card_asset_type_biz = $card_group_biz ? $card_group_biz->id : 7;
        $card_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_asset_type_biz)
            ->where('is_main_account', 0)
            ->orderBy('name')
            ->get();
        $bank_manual = [];
        $card_manual = [];
        if (! empty($form_settings)) {
            $bank_manual = json_decode($form_settings->pre_day_bank_manual ?? '[]', true) ?: [];
            $card_manual = json_decode($form_settings->pre_day_card_manual ?? '[]', true) ?: [];
        }

        return view('mpcs::forms.form_9a')->with(compact(
            'business_locations',
            'form_9a_no',
            'form_number',
            'date',
            'name',
            'form_settings',
            'business_bank_accounts',
            'card_accounts',
            'bank_manual',
            'card_manual',
            'default_location_id',
            'default_location_name'
        ));
    }

    //  public function From9C()
    //     {
    //         $business_id = request()->session()->get('business.id');
    //         $business_locations = BusinessLocation::forDropdown($business_id);
    //         $business_location_name = BusinessLocation::where('business_id', $business_id)
    //                     ->pluck('name')
    //                     ->first();
    //         $ref_pre_form_number = Mpcs9cCashFormSettings::where('business_id', $business_id)
    //                             ->orderBy('id', 'desc')
    //                             ->first();

    //           if (!empty($ref_pre_form_number)) {
    //             $form_9a_no = $ref_pre_form_number->starting_number;
    //         } else {
    //             $form_9a_no = 1;
    //         }

    //         return view('mpcs::forms.form_9c')->with(compact(
    //             'business_locations',
    //             'business_location_name',
    //             'form_9a_no'
    //         ));
    //     }

    public function From9C()
    {
        $business_id = request()->session()->get('business.id')
            ?? request()->session()->get('user.business_id');
        $prefix = $this->getF9CCachePrefix($business_id);

        $business_locations = $this->rememberMpcsValue($prefix . ':locations', 120, function () use ($business_id) {
            return BusinessLocation::forDropdown($business_id);
        });
        $business_location_name = $this->rememberMpcsValue($prefix . ':location-name', 120, function () use ($business_id) {
            return BusinessLocation::where('business_id', $business_id)->value('name');
        });

        $today = Carbon::today()->format('Y-m-d');
        $latest_entry = $this->getEffectiveF9CSetting(Mpcs9cCashFormSettings::class, $business_id, $today);
        $form_9a_no = $latest_entry ? (int) $latest_entry->starting_number : 0;

        $categories = $this->rememberMpcsValue($prefix . ':root-categories:' . $this->getF9CDataVersion($business_id), 600, function () use ($business_id) {
            return Category::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        $business = $this->rememberMpcsValue($prefix . ':business-precision', 120, function () use ($business_id) {
            return Business::where('id', $business_id)
                ->select('id', 'currency_precision', 'quantity_precision')
                ->first();
        });
        $currency_precision = $business ? (int) ($business->currency_precision ?? 2) : 2;
        $quantity_precision = $business ? (int) ($business->quantity_precision ?? 2) : 2;

        return view('mpcs::forms.form_9c')->with(compact(
            'business_locations',
            'business_location_name',
            'form_9a_no',
            'categories',
            'currency_precision',
            'quantity_precision'
        ));
    }

    public function From9CCR()
    {
        $business_id = request()->session()->get('business.id')
            ?? request()->session()->get('user.business_id');
        $prefix = $this->getF9CCachePrefix($business_id);

        $business_locations = $this->rememberMpcsValue($prefix . ':locations', 120, function () use ($business_id) {
            return BusinessLocation::forDropdown($business_id);
        });
        $business_location_name = $this->rememberMpcsValue($prefix . ':location-name', 120, function () use ($business_id) {
            return BusinessLocation::where('business_id', $business_id)->value('name');
        });

        $today = Carbon::today()->format('Y-m-d');
        $setting = $this->getEffectiveF9CSetting(Mpcs9cCreditFormSettings::class, $business_id, $today);
        $form_9a_no = $setting ? (int) $setting->starting_number : 0;

        $precision = $this->rememberMpcsValue($prefix . ':business-precision', 120, function () use ($business_id) {
            return Business::where('id', $business_id)
                ->select('id', 'currency_precision', 'quantity_precision')
                ->first();
        });
        $currency_precision = $precision ? (int) ($precision->currency_precision ?? 2) : 2;

        $business = '';
        $default_business_location = '';

        return view('mpcs::forms.form_9ccr')->with(compact(
            'business_locations',
            'business_location_name',
            'form_9a_no',
            'setting',
            'business',
            'default_business_location',
            'currency_precision'
        ));
    }

    /**
     * Aggregate direct settlement credit sales by product sub category for F9A.
     * Uses the edited settlement/linked transaction date as the business date, with order_date only as a legacy fallback.
     * Amount uses the saved amount column when available, otherwise qty * price.
     */
    private function getF9ADirectSettlementCreditAmount($business_id, $sub_category_id, $date_from, $date_to, $location_id = null)
    {
        /*
         * F9A credit rows must use the actual credit sale business date.
         * In direct settlements, older credit sales can be saved/finalized today,
         * while the bill/order date remains a previous date. Therefore do not
         * filter only by created_at. For settlement-owned rows the linked transaction date
         * follows the settlement date, so it must win over the original order_date.
         * order_date remains only a legacy fallback when a transaction is not linked.
         */
        $dateExpr = "DATE(COALESCE(t.transaction_date, NULLIF(scsp.order_date, ''), scsp.created_at))";
        $amountExpr = "COALESCE(NULLIF(scsp.amount, 0), (COALESCE(scsp.qty,0) * COALESCE(scsp.price,0)), 0)";

        $query = DB::table('settlement_credit_sale_payments as scsp')
            ->join('products as p', 'scsp.product_id', '=', 'p.id')
            ->leftJoin('transactions as t', function ($join) {
                $join->on('t.id', '=', 'scsp.transaction_id')
                    ->orOn('t.credit_sale_id', '=', 'scsp.id');
            })
            ->where('scsp.business_id', $business_id)
            ->where('p.sub_category_id', $sub_category_id)
            ->whereRaw("{$dateExpr} >= ?", [$date_from])
            ->whereRaw("{$dateExpr} <= ?", [$date_to]);

        if (! empty($location_id)) {
            $locationExpression = $this->getSettlementCreditLocationExpression('scsp', 't');
            $query->whereRaw($locationExpression . ' = ?', [$location_id]);
        }

        return (float) $query->sum(DB::raw($amountExpr));
    }

    private function getF9ASubCategoryList($business_id, $settings)
    {
        /*
         * Keep the F9A paper structure stable even before entering sales.
         * First use the configured Form 9A sub-categories, then add any product
         * sub-categories found in the business. This prevents the page from
         * rendering only the TOTAL column when the selected date has no sales.
         */
        $settings_subcats = [];
        if ($settings && $settings->sub_categories_data) {
            $settings_subcats = json_decode($settings->sub_categories_data, true) ?: [];
        }

        $ordered_ids = collect($settings_subcats)
            ->pluck('id')
            ->filter()
            ->map(function ($id) { return (int) $id; })
            ->unique()
            ->values();

        $product_subcat_ids = Product::where('business_id', $business_id)
            ->whereNotNull('sub_category_id')
            ->where('sub_category_id', '>', 0)
            ->distinct()
            ->pluck('sub_category_id')
            ->map(function ($id) { return (int) $id; });

        $all_ids = $ordered_ids->merge($product_subcat_ids)->unique()->values()->all();

        $query = Category::where('business_id', $business_id)->where('parent_id', '!=', 0);
        if (! empty($all_ids)) {
            $query->whereIn('id', $all_ids);
        }

        $categories = $query->get();

        if ($categories->count() > 0) {
            $order = array_flip($ordered_ids->all());
            return $categories->sortBy(function ($cat) use ($order) {
                return array_key_exists((int)$cat->id, $order)
                    ? $order[(int)$cat->id]
                    : 10000 . '-' . $cat->name;
            })->values();
        }

        return collect($settings_subcats)->map(function ($row) {
            return (object) [
                'id' => $row['id'] ?? 0,
                'name' => $row['name'] ?? ($row['sub_category_name'] ?? ''),
            ];
        })->filter(function ($row) {
            return ! empty($row->id) || ! empty($row->name);
        })->values();
    }

    /**
     * Show the form for getFrom20
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get Form 9A data for the selected date
     * 
     * Image Number Mapping:
     * =====================
     * SALES SECTION:
     * - Image 4: Card Sale Today (card_today)
     * - Image 5: Cash Sale Today (cash_today)
     * - Image 7: Credit Sale Today (credit_today)
     * - Image 8: Today Sale Total = cash + card + credit (total_sale_today)
     * - Image 9: Total Sale up to Previous Day (total_sale_previous) - zero on 1st of month
     * - Image 10: Total Sale as of Today = Image 8 + Image 9 (total_sale_to_today)
     * 
     * RECEIPTS SECTION (Image 20):
     * - Image 11: Cash Sales Previous Day = previous day's cash (cash_previous)
     * - Image 12: Card Sales Previous Day = previous day's card (card_previous)
     * - Image 13: Cash Sales Today = Image 5 (cash_today)
     * - Image 14: Card Sales Today = Image 4 (card_today)
     * - Image 15: Cash Total as of Today = Image 11 + Image 13 (cash_total)
     * - Image 16: Card Total as of Today = Image 12 + Image 14 (card_total)
     * - Image 17: Credit Previous Day = previous day's credit (credit_previous)
     * - Image 18: Credit Today = Image 7 (credit_today)
     * - Image 19: Credit Total = Image 17 + Image 18 (credit_total)
     * 
     * PAYMENTS SECTION (Image 21):
     * - Image 22: Cash Received Previous (cumulative from month start to yesterday)
     * - Image 23: Cheques/Cards Received Previous (cumulative)
     * - Image 24: Total Payments Previous = Image 22 + Image 23
     * - Image 25: Balance Previous = Total Sales Previous - Total Payments Previous
     * - Image 26: Grand Total Previous = Total Sales Previous
     * - Image 27: Cash input today (frontend manual input, defaults to today's cash sales)
     * - Image 28: Card input today (frontend manual input, defaults to today's card sales)
     * - Image 29: Other input today (frontend manual input)
     * - Image 30: Today Total = Image 28 + Image 29
     * - Image 31: Cash Total = Image 22 + Image 28
     * - Image 32: Card Total = Image 23 + Image 29
     * - Image 33: Payments Total = Image 31 + Image 32
     * - Image 34: Balance = Image 35 - Image 33 (should equal Credit Sales Outstanding)
     * - Image 35: Grand Total = Image 26 + Image 36
     * - Image 36: = Image 30
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function get9AForm(Request $request)
    {
        try {
        $business_id = $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? $request->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');
        $location_id = $request->input('form_9a_location_id');
        $date        = $request->input('start_date', $request->input('selected_date'));
        
        try {
            $date_obj = Carbon::parse($date);
        } catch (\Exception $e) {
            $date_obj = Carbon::today();
            $date = $date_obj->toDateString();
        }

        // 1. Get location and settings
        $location = BusinessLocation::find($location_id);
        $settings = Mpcs9aFormSettings::where('business_id', $business_id)
            ->orderBy('date', 'desc')
            ->first();

        // Previous-day columns reset only on the first calendar day of a month.
        // F22 stock-taking dates must not affect Form 9A carry-forward values.
        $is_first_day_of_month = $date_obj->day === 1;

        $is_opening_date = $settings && $date_obj->isSameDay(Carbon::parse($settings->date));

        // 3. Get all sub-categories. Prefer Form 9A settings categories so the
        //    paper structure stays the same even before any sales have been entered.
        $sub_categories = $this->getF9ASubCategoryList($business_id, $settings);

        // 4. Get Reset Date and Type
        $opening_date = $settings ? $settings->date : null;
        $reset_info = $this->getLastResetInfo($business_id, $location_id, $date, $opening_date);
        $reset_date = $reset_info['date']->toDateString(); // ensure string for DB comparisons
        $is_opening_reset = $reset_info['type'] == 'opening';

        // 5. Build Sub-Categories Data
        $sub_categories_data = [];
        $total_row1 = 0; $total_row2 = 0; $total_row3 = 0; $total_row4 = 0;
        $total_row5 = 0; $total_row6 = 0; $total_row7 = 0;

        $settings_subcats = [];
        if ($settings && $settings->sub_categories_data) {
            $settings_subcats = json_decode($settings->sub_categories_data, true) ?: [];
        }
        $settings_subcats_by_id = collect($settings_subcats)->keyBy('id');

        foreach ($sub_categories as $index => $cat) {
            // Get today's income and credit
            // Join transaction_payments to determine actual cash vs credit per sub-category.
            // A transaction is credit if: is_credit_sale=1, payment_status due/partial,
            // OR payment method is 'credit'/'credit_sale'. Cash = paid via cash/card/cheque (non-credit).
            $today_sales = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('t.deleted_at')
                ->whereNull('tsl.deleted_at')
                ->where('p.sub_category_id', $cat->id)
                ->whereDate('t.transaction_date', $date)
                ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
                ->selectRaw("
                    SUM(tsl.unit_price_inc_tax * tsl.quantity) as income,
                    SUM(CASE
                        WHEN (t.is_credit_sale = 1
                              OR t.payment_status IN ('due','partial')
                              OR EXISTS (
                                  SELECT 1 FROM transaction_payments tp2
                                  WHERE tp2.transaction_id = t.id
                                    AND tp2.method IN ('credit', 'credit_sale')
                                    AND tp2.deleted_at IS NULL
                              ))
                             AND NOT EXISTS (
                                 SELECT 1 FROM settlement_credit_sale_payments scsp_lk
                                 WHERE (scsp_lk.transaction_id = t.id OR scsp_lk.id = t.credit_sale_id)
                             )
                        THEN tsl.unit_price_inc_tax * tsl.quantity
                        ELSE 0
                    END) as credit
                ")
                ->first();

            // Credit sales from Direct Settlement/Petro credit entries.
            // Use the actual credit sale business date, not only current date.
            $petro_credit_today = $this->getF9ADirectSettlementCreditAmount(
                $business_id,
                $cat->id,
                $date,
                $date,
                $location_id
            );

            // Row 1: Cash Today = Income - Credit (transaction-based) - Petro credit
            $row1 = (float)($today_sales->income ?? 0) - (float)($today_sales->credit ?? 0) - $petro_credit_today;
            // Row 2: Credit Today = transaction credit + unlinked Petro credit
            $row2 = (float)($today_sales->credit ?? 0) + $petro_credit_today;

            // Row 3 & 4 (Previous Day)
            $row3 = 0;
            $row4 = 0;

            $opening_cash_seed = (float)($settings_subcats_by_id[$cat->id]['cash_previous_day'] ?? 0);
            $opening_credit_seed = (float)($settings_subcats_by_id[$cat->id]['credit_previous_day'] ?? 0);

            if ($is_first_day_of_month) {
                // Previous-day values are zero only on the first day of every month.
                $row3 = 0;
                $row4 = 0;
            } elseif ($is_opening_date) {
                // On the opening date: show the values entered in settings.
                $row3 = $opening_cash_seed;
                $row4 = $opening_credit_seed;
            } else {
                // All other dates: cumulative from last reset date up to yesterday
                $cumulative_prev = DB::table('transactions as t')
                    ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                    ->join('products as p', 'tsl.product_id', '=', 'p.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'sell')
                    ->where('t.status', 'final')
                    ->whereNull('t.deleted_at')
                    ->whereNull('tsl.deleted_at')
                    ->where('p.sub_category_id', $cat->id)
                    ->whereDate('t.transaction_date', '>=', $reset_date)
                    ->whereDate('t.transaction_date', '<', $date)
                    ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
                    ->selectRaw("
                        SUM(tsl.unit_price_inc_tax * tsl.quantity) AS total_income,
                        SUM(CASE
                            WHEN (t.is_credit_sale = 1
                                  OR t.payment_status IN ('due','partial')
                                  OR EXISTS (
                                      SELECT 1 FROM transaction_payments tp2
                                      WHERE tp2.transaction_id = t.id
                                        AND tp2.method IN ('credit', 'credit_sale')
                                        AND tp2.deleted_at IS NULL
                                  ))
                                  AND NOT EXISTS (
                                      SELECT 1 FROM settlement_credit_sale_payments scsp_lk
                                      WHERE (scsp_lk.transaction_id = t.id OR scsp_lk.id = t.credit_sale_id)
                                  )
                            THEN tsl.unit_price_inc_tax * tsl.quantity
                            ELSE 0
                        END) AS total_credit
                    ")
                    ->first();

                $sum_income = (float)($cumulative_prev->total_income ?? 0);
                $sum_credit = (float)($cumulative_prev->total_credit ?? 0);

                // Add Direct Settlement/Petro credit sales for the previous period.
                // This must go to Credit Previous Day row, not Cash Previous Day.
                $previous_to = Carbon::parse($date)->subDay()->toDateString();
                $petro_credit_prev = ($previous_to >= $reset_date)
                    ? $this->getF9ADirectSettlementCreditAmount(
                        $business_id,
                        $cat->id,
                        $reset_date,
                        $previous_to,
                        $location_id
                    )
                    : 0;

                $row3 = $sum_income - $sum_credit - $petro_credit_prev;
                $row4 = $sum_credit + $petro_credit_prev;

                // If the latest reset is the opening date, carry the configured opening
                // balances forward into future dates.
                if ($is_opening_reset) {
                    $row3 += $opening_cash_seed;
                    $row4 += $opening_credit_seed;
                }
            }

            // Calculations
            $row5 = $row1 + $row3;
            $row6 = $row2 + $row4;
            $row7 = $row5 + $row6;

            $sub_categories_data[] = [
                'name' => $cat->name,
                'row1' => $row1,
                'row2' => $row2,
                'row3' => $row3,
                'row4' => $row4,
                'row5' => $row5,
                'row6' => $row6,
                'row7' => $row7,
            ];

            // Totals
            $total_row1 += $row1; $total_row2 += $row2; $total_row3 += $row3; $total_row4 += $row4;
            $total_row5 += $row5; $total_row6 += $row6; $total_row7 += $row7;
        }

        // 6. Build Receipts Section Data
        $receipts_data = [];
        foreach ($sub_categories_data as $sc_data) {
            $receipts_data[] = [
                'description' => $sc_data['name'],
                'prev' => $sc_data['row3'],
                'today' => $sc_data['row1'],
                'total' => $sc_data['row5'] // Row 5 is Cash Cumulative (Previous + Today Cash)
            ];
        }

        // 7. Payments Section Logic
        $payments = [
            'cash' => [ 'prev' => 0, 'today' => 0, 'total' => 0, 'form_no' => '' ],
            'cheques' => [ 'prev' => 0, 'today' => 0, 'total' => 0 ],
            'cards' => [],
            'banks' => [],
            'other' => [ 'prev' => 0, 'today' => 0, 'total' => 0 ],
            'pre_day_balance' => 0,
            'pre_day_grand_total' => 0,
        ];

        // --- A. Cash Today = F9C Cash "Total This Page" formula:
        //     SUM(total sale by sell lines) - SUM(credit sale from settlement_credit_sale_payments)
        //     This matches exactly what F9C Cash form shows as "Total This Page".
        $cash_today_sell_total = (float) DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', $date)
            ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
            // Do not count the separate direct-credit sell transaction a second time.
            ->whereNotExists(function ($q) use ($business_id) {
                $q->select(DB::raw(1))
                    ->from('settlement_credit_sale_payments as linked_credit')
                    ->where('linked_credit.business_id', $business_id)
                    ->where(function ($linkQuery) {
                        $linkQuery->whereColumn('linked_credit.transaction_id', 't.id')
                            ->orWhereColumn('linked_credit.id', 't.credit_sale_id');
                    });
            })
            ->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));

        // Credit from transactions (is_credit_sale=1 or payment_status due/partial or credit/credit_sale method).
        // Exclude transactions already linked to settlement_credit_sale_payments to avoid double counting.
        $cash_today_credit_tx = (float) DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', $date)
            ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
            ->where(function($q) {
                $q->where('t.is_credit_sale', 1)
                  ->orWhereIn('t.payment_status', ['due', 'partial'])
                  ->orWhereExists(function($sub) {
                      $sub->from('transaction_payments as tp2')
                          ->whereColumn('tp2.transaction_id', 't.id')
                          ->whereIn('tp2.method', ['credit', 'credit_sale'])
                          ->whereNull('tp2.deleted_at');
                  });
            })
            ->whereNotExists(function($q) {
                $q->from('settlement_credit_sale_payments as scsp_lk')
                    ->where(function ($qq) {
                        $qq->whereColumn('scsp_lk.transaction_id', 't.id')
                           ->orWhereColumn('scsp_lk.id', 't.credit_sale_id');
                    });
            })
            ->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));

        // Petro/direct-settlement credit sales (linked + unlinked).
        // IS2188: the edited settlement date is authoritative. A back-dated bill/order
        // date must not keep the amount on the old MPCS day after the settlement moves.
        $cashTodayPetroQuery = DB::table('settlement_credit_sale_payments as scsp')
            ->leftJoin('transactions as linked_t', function ($join) {
                $join->on('linked_t.id', '=', 'scsp.transaction_id')
                    ->orOn('linked_t.credit_sale_id', '=', 'scsp.id');
            })
            ->join('products as p', 'scsp.product_id', '=', 'p.id')
            ->where('scsp.business_id', $business_id);
        $this->applySettlementCreditDateRange($cashTodayPetroQuery, 'scsp', 'linked_t', $date, $date);
        if (! empty($location_id)) {
            $cashTodayPetroQuery->whereRaw(
                $this->getSettlementCreditLocationExpression('scsp', 'linked_t') . ' = ?',
                [$location_id]
            );
        }
        $cash_today_credit_petro = (float) $cashTodayPetroQuery
            ->sum(DB::raw('COALESCE(NULLIF(scsp.amount, 0), COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), 0)'));

        $f9c_cash_today = max(0, $cash_today_sell_total - $cash_today_credit_tx - $cash_today_credit_petro);

        $f10_today_query = DB::table('mpcs_f10_headers')
            ->where('business_id', $business_id)
            ->when(!empty($location_id), fn($q) => $q->where('location_id', $location_id))
            ->whereDate('form_date', $date)
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhereNull('status')
                    ->orWhere('status', '');
            });

        $f10_cash_today = (float) (clone $f10_today_query)->sum('cash_amount');
        $f10_cheque_today = (float) (clone $f10_today_query)->sum('cheque_amount');

        $f10_form_nos = (clone $f10_today_query)
            ->pluck('form_no')
            ->implode(', ');

        $payments['cash']['today']   = $f10_cash_today;
        $payments['cash']['form_no'] = $f10_form_nos ? 'F10: ' . $f10_form_nos : '';
        $payments['cash']['total']   = $payments['cash']['today'];

        $payments['cheques']['today'] = $f10_cheque_today;

        // --- B. Cumulative Logic Helpers ---
        // Historical cumulative fetching for Prev Day columns
        /*
         | One correctly scoped query for card debits, shared by the Previous Day
         | total and the Today total so the two can never be scoped differently.
        */
        $card_debit_query = function ($account_id) use ($business_id, $location_id) {
            $query = DB::table('account_transactions')
                ->where('account_transactions.account_id', $account_id)
                ->where('account_transactions.type', 'debit');

            if (Schema::hasColumn('account_transactions', 'business_id')) {
                // Nullable on this table, so rows that predate it are kept.
                $query->where(function ($scoped) use ($business_id) {
                    $scoped->where('account_transactions.business_id', $business_id)
                        ->orWhereNull('account_transactions.business_id');
                });
            }

            if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                $query->whereNull('account_transactions.deleted_at');
            }

            /*
             | account_transactions has no location column, so location is
             | reached through the linked transaction. A row with no link has no
             | knowable location and is kept rather than dropped - excluding it
             | would hide takings instead of attributing them.
            */
            if (! empty($location_id)) {
                $query->where(function ($scoped) use ($location_id) {
                    $scoped->whereNull('account_transactions.transaction_id')
                        ->orWhereExists(function ($sub) use ($location_id) {
                            $sub->select(DB::raw(1))
                                ->from('transactions as card_txn')
                                ->whereColumn('card_txn.id', 'account_transactions.transaction_id')
                                ->where('card_txn.location_id', $location_id);
                        });
                });
            }

            return $query;
        };

        $get_historical_val = function($type, $account_id = null) use ($business_id, $location_id, $reset_date, $date_obj, $card_debit_query) {
            $yesterday = $date_obj->copy()->subDay()->toDateString();
            if ($reset_date > $yesterday) return 0;

            if ($type == 'f10_cash') {
                return (float) DB::table('mpcs_f10_headers')
                    ->where('business_id', $business_id)
                    ->whereDate('form_date', '>=', $reset_date)
                    ->whereDate('form_date', '<=', $yesterday)
                    ->when(!empty($location_id), fn($q) => $q->where('location_id', $location_id))
                    ->where(function ($q) {
                        $q->where('status', 'active')
                            ->orWhereNull('status')
                            ->orWhere('status', '');
                    })
                    ->sum('cash_amount');
            }
            if ($type == 'f10_cheque') {
                $q = DB::table('transaction_payments as tp')
                    ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
                    ->where('t.business_id', $business_id)
                    ->whereDate('tp.paid_on', '>=', $reset_date)
                    ->whereDate('tp.paid_on', '<=', $yesterday)
                    ->whereNull('tp.deleted_at')
                    ->where('tp.method', 'cheque');
                if (!empty($location_id)) $q->where('t.location_id', $location_id);
                return (float)$q->sum('tp.amount');
            }
            if ($type == 'acc_debit') {
                /*
                 | IS2180: scoped the same way as every other figure on F9A.
                 |
                 | This query had none of the guards the cash and cheque figures
                 | beside it have had all along:
                 |
                 |   business_id  - absent. account_transactions carries one, and
                 |                  every other F9A query filters on it.
                 |   deleted_at   - absent. The table uses soft deletes, so the
                 |                  card transactions of a DELETED settlement were
                 |                  still being added into Previous Day.
                 |   location_id  - absent. Cash filters by location, cheques
                 |                  filter by location; cards summed every
                 |                  location's takings into a form that is
                 |                  produced per location.
                 |
                 | Rows that carry no transaction link are still counted, so this
                 | can only ever remove an amount that provably belongs to another
                 | location - never one whose location is simply unknown.
                */
                return (float) $card_debit_query($account_id)
                    ->whereDate('account_transactions.operation_date', '>=', $reset_date)
                    ->whereDate('account_transactions.operation_date', '<=', $yesterday)
                    ->sum('account_transactions.amount');
            }
            return 0;
        };

        // Cash Prev & Total
        $cash_cum = $get_historical_val('f10_cash');
        $cash_seed = (float)($settings->pre_day_cash ?? 0);
        
        if ($is_first_day_of_month) {
            $payments['cash']['prev'] = 0;
        } elseif ($is_opening_date) {
            $payments['cash']['prev'] = $cash_seed;
        } else {
            $payments['cash']['prev'] = $cash_cum + ($is_opening_reset ? $cash_seed : 0);
        }
        $payments['cash']['total'] = $payments['cash']['prev'] + $payments['cash']['today'];

        // Cheques Prev & Total
        $cheque_cum = $get_historical_val('f10_cheque');
        $cheque_seed = (float)($settings->pre_day_cheques ?? 0);
        if ($is_first_day_of_month) {
            $payments['cheques']['prev'] = 0;
        } elseif ($is_opening_date) {
            $payments['cheques']['prev'] = $cheque_seed;
        } else {
            $payments['cheques']['prev'] = $cheque_cum + ($is_opening_reset ? $cheque_seed : 0);
        }
        $payments['cheques']['total'] = $payments['cheques']['prev'] + $payments['cheques']['today'];

        // --- C. Dynamic Card Accounts ---
        /*
         | IS2187 - Settlement card figures must come from the settlement source.
         |
         | The previous implementation read only account_transactions. That works
         | only after every historical settlement card has a matching Finance
         | posting. Older/imported/finalized settlements can legitimately have
         | settlement_card_payments rows without a corresponding account transaction,
         | which is exactly why the 2026-08-17 .. 2026-08-31 cards disappeared from
         | F9A even though they were saved in the settlement.
         |
         | Use settlement_card_payments + settlements as the authoritative source
         | whenever source rows exist for an account/date. Keep the old Finance
         | ledger query only as a backward-compatible fallback for accounts/dates
         | where there is no settlement source row at all. This avoids both data loss
         | and double counting.
        */
        $card_group = DB::table('account_groups')
            ->where('business_id', $business_id)
            ->where('name', 'Card')
            ->first();
        $card_asset_type = $card_group ? $card_group->id : 7;
        $card_manual = $settings ? json_decode($settings->pre_day_card_manual, true) : [];

        $previous_to = $date_obj->copy()->subDay()->toDateString();
        $card_source_today = $this->getF9ASettlementCardTotalsByAccount(
            $business_id,
            $date,
            $date,
            $location_id
        );
        $card_source_previous = ($reset_date <= $previous_to)
            ? $this->getF9ASettlementCardTotalsByAccount(
                $business_id,
                $reset_date,
                $previous_to,
                $location_id
            )
            : ['totals' => [], 'counts' => []];

        // Do not hide a historically used card account merely because its account
        // classification changed after the settlement was entered.
        $source_card_account_ids = array_values(array_unique(array_merge(
            array_map('intval', array_keys($card_source_today['totals'] ?? [])),
            array_map('intval', array_keys($card_source_previous['totals'] ?? [])),
            array_map('intval', array_keys(is_array($card_manual) ? $card_manual : []))
        )));
        $source_card_account_ids = array_values(array_filter($source_card_account_ids, fn($id) => $id > 0));

        $card_accounts = Account::where('business_id', $business_id)
            ->where(function ($query) use ($card_asset_type, $source_card_account_ids) {
                $query->where(function ($cardQuery) use ($card_asset_type) {
                    $cardQuery->where('asset_type', $card_asset_type)
                        ->where(function ($mainQuery) {
                            $mainQuery->where('is_main_account', 0)
                                ->orWhereNull('is_main_account');
                        });
                });

                if (! empty($source_card_account_ids)) {
                    $query->orWhereIn('id', $source_card_account_ids);
                }
            })
            ->orderBy('name')
            ->get();

        foreach ($card_accounts as $acc) {
            $ledger_today = (float) $card_debit_query($acc->id)
                ->whereDate('account_transactions.operation_date', $date)
                ->sum('account_transactions.amount');

            $source_today_count = (int) ($card_source_today['counts'][$acc->id] ?? 0);
            $source_today_amount = (float) ($card_source_today['totals'][$acc->id] ?? 0);
            $today = $source_today_count > 0 ? $source_today_amount : $ledger_today;

            $ledger_cum = $get_historical_val('acc_debit', $acc->id);
            $source_prev_count = (int) ($card_source_previous['counts'][$acc->id] ?? 0);
            $source_prev_amount = (float) ($card_source_previous['totals'][$acc->id] ?? 0);
            $cum = $source_prev_count > 0 ? $source_prev_amount : $ledger_cum;

            // Diagnostic only: F9A uses the settlement source when there is a
            // mismatch, but the log makes any missing Finance backfill visible.
            if ($source_today_count > 0 && abs($ledger_today - $source_today_amount) > 0.01) {
                Log::info('MPCS F9A card source/ledger mismatch (today)', [
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'date' => $date,
                    'account_id' => $acc->id,
                    'settlement_source' => $source_today_amount,
                    'finance_ledger' => $ledger_today,
                ]);
            }
            if ($source_prev_count > 0 && abs($ledger_cum - $source_prev_amount) > 0.01) {
                Log::info('MPCS F9A card source/ledger mismatch (previous)', [
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'from' => $reset_date,
                    'to' => $previous_to,
                    'account_id' => $acc->id,
                    'settlement_source' => $source_prev_amount,
                    'finance_ledger' => $ledger_cum,
                ]);
            }

            if ($is_first_day_of_month) {
                $prev = 0;
            } elseif ($is_opening_date) {
                $prev = (float)($card_manual[$acc->id] ?? 0);
            } else {
                $prev = $cum + ($is_opening_reset ? (float)($card_manual[$acc->id] ?? 0) : 0);
            }

            $payments['cards'][] = [
                'name' => $acc->name,
                'prev' => $prev,
                'today' => $today,
                'total' => $prev + $today
            ];
        }

        // --- D. Dynamic Bank Accounts ---
        $bank_accounts = Account::where('business_id', $business_id)
            ->where('is_business_bank_account', 1)
            ->get();
        $bank_manual = $settings ? json_decode($settings->pre_day_bank_manual, true) : [];

        foreach ($bank_accounts as $acc) {
            $today = (float)DB::table('account_transactions')
                ->where('account_id', $acc->id)
                ->whereDate('operation_date', $date)
                ->where('type', 'debit')
                ->sum('amount');

            $cum = $get_historical_val('acc_debit', $acc->id);

            if ($is_first_day_of_month) {
                $prev = 0;
            } elseif ($is_opening_date) {
                $prev = (float)($bank_manual[$acc->id] ?? 0);
            } else {
                $prev = $cum + ($is_opening_reset ? (float)($bank_manual[$acc->id] ?? 0) : 0);
            }

            $payments['banks'][] = [
                'name' => $acc->name,
                'prev' => $prev,
                'today' => $today,
                'total' => $prev + $today
            ];
        }

        // --- E. Other & Summary ---
        // Fetch Other from transaction_payments as catch-all (initial logic maintained for catch-all)
        $known_methods = ['cash', 'cheque', 'card', 'bank', 'credit', 'credit_sale'];
        $today_catchall = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->whereDate('tp.paid_on', $date)
            ->whereNotIn('tp.method', $known_methods)
            ->sum('tp.amount');
        
        $cum_catchall = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->whereDate('tp.paid_on', '>=', $reset_date)
            ->whereDate('tp.paid_on', '<=', $date_obj->copy()->subDay()->toDateString())
            ->whereNotIn('tp.method', $known_methods)
            ->sum('tp.amount');

        $payments['other']['today'] = (float)$today_catchall;
        $payments['other']['prev'] = $is_first_day_of_month ? 0 : (float)$cum_catchall;
        $payments['other']['total'] = $payments['other']['prev'] + $payments['other']['today'];

        // --- F. Final Cumulative Balances ---
        if ($is_first_day_of_month) {
            $payments['pre_day_balance']     = 0;
            $payments['pre_day_grand_total'] = 0;
            $payments['pre_day_total']       = 0;
        } elseif ($is_opening_date) {
            $payments['pre_day_balance']     = (float)($settings->pre_day_balance ?? 0);
            $payments['pre_day_grand_total'] = (float)($settings->pre_day_grand_total ?? 0);
            $payments['pre_day_total']       = (float)($settings->pre_day_total ?? 0);
        } else {
            // Total Payments (Prev) = sum of all payment rows 'prev' columns
            $payments_prev_total = $payments['cash']['prev'] + $payments['cheques']['prev'] + $payments['other']['prev']
                + collect($payments['cards'])->sum('prev') + collect($payments['banks'])->sum('prev');
            
            // Total Payments (Today) = sum of all payment rows 'today' columns
            $payments_today_total = $payments['cash']['today'] + $payments['cheques']['today'] + $payments['other']['today']
                + collect($payments['cards'])->sum('today') + collect($payments['banks'])->sum('today');

            $payments['pre_day_total'] = $payments_prev_total;

            // Balance in Hand (Prev) = (Opening Balance ONLY if reset is today) + Total Receipts (Cumulative to yesterday) - Total Payments (Cumulative to yesterday)
            $receipts_prev_total = $total_row3;
            $opening_balance = $is_opening_reset ? (float)($settings->pre_day_balance ?? 0) : 0;
            
            $payments['pre_day_balance'] = $opening_balance + $receipts_prev_total - $payments_prev_total;
            
            // Grand Total (Prev) = Balance in Hand (Prev) + Total Payments (Prev)
            $payments['pre_day_grand_total'] = $payments['pre_day_balance'] + $payments['pre_day_total'];
        }

        return response()->json([
            'form_number'              => $this->getFormNumber($business_id, $date),
            'location'                 => [
                'name' => $location->name ?? '',
            ],
            'sub_categories_data'      => $sub_categories_data,
            'total_row1'               => $total_row1,
            'total_row2'               => $total_row2,
            'total_row3'               => $total_row3,
            'total_row4'               => $total_row4,
            'total_row5'               => $total_row5,
            'total_row6'               => $total_row6,
            'total_row7'               => $total_row7,
            'receipts_data'            => $receipts_data,
            'payments'                 => $payments,
            'text_details'             => Mpcs9aFormTextDetail::where('business_id', $business_id)->where('form', $this->getFormNumber($business_id, $date))->first()
        ]);
        } catch (\Exception $e) {
            Log::error('get9AForm error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(["error" => "An error occurred while processing the request."], 500);
        }
    }

    /**
     * Return F9A settlement-card totals grouped by the selected Card account.
     *
     * settlement_card_payments is the operational source of truth for settlement
     * cards. settlement_no is a VARCHAR in historical databases and can contain
     * either settlements.id or settlements.settlement_no, so both identifiers are
     * resolved before reading the payment rows.
     *
     * Duplicate protection intentionally mirrors Petro PD accounting: prefer
     * pump_payment_id, then daily_card_id, then customer_payment_id, and finally
     * the settlement_card_payments primary key.
     */
    private function getF9ASettlementCardTotalsByAccount($business_id, $from_date, $to_date, $location_id = null)
    {
        $empty = ['totals' => [], 'counts' => []];

        if (empty($business_id)
            || ! Schema::hasTable('settlements')
            || ! Schema::hasTable('settlement_card_payments')
            || ! Schema::hasColumn('settlements', 'transaction_date')
            || ! Schema::hasColumn('settlement_card_payments', 'settlement_no')
            || ! Schema::hasColumn('settlement_card_payments', 'amount')) {
            return $empty;
        }

        try {
            $from = Carbon::parse($from_date)->toDateString();
            $to = Carbon::parse($to_date)->toDateString();
        } catch (\Throwable $e) {
            return $empty;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $settlementQuery = DB::table('settlements')
            ->where('business_id', $business_id)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to);

        if (! empty($location_id) && Schema::hasColumn('settlements', 'location_id')) {
            $settlementQuery->where('location_id', $location_id);
        }

        $settlements = $settlementQuery
            ->select('id', 'settlement_no')
            ->get();

        if ($settlements->isEmpty()) {
            return $empty;
        }

        $settlementKeys = $settlements
            ->flatMap(function ($settlement) {
                return [
                    (string) $settlement->id,
                    (string) $settlement->settlement_no,
                ];
            })
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($settlementKeys)) {
            return $empty;
        }

        $columns = ['id', 'settlement_no', 'amount'];
        foreach (['card_type', 'pump_payment_id', 'daily_card_id', 'customer_payment_id'] as $column) {
            if (Schema::hasColumn('settlement_card_payments', $column)) {
                $columns[] = $column;
            }
        }

        $rows = DB::table('settlement_card_payments')
            ->where('business_id', $business_id)
            ->whereIn('settlement_no', $settlementKeys)
            ->where('amount', '>', 0)
            ->select($columns)
            ->get();

        if ($rows->isEmpty()) {
            return $empty;
        }

        $rows = $rows->unique(function ($row) {
            if (isset($row->pump_payment_id) && ! empty($row->pump_payment_id)) {
                return 'pump_payment_id:' . $row->pump_payment_id;
            }
            if (isset($row->daily_card_id) && ! empty($row->daily_card_id)) {
                return 'daily_card_id:' . $row->daily_card_id;
            }
            if (isset($row->customer_payment_id) && ! empty($row->customer_payment_id)) {
                return 'customer_payment_id:' . $row->customer_payment_id;
            }

            return 'id:' . $row->id;
        })->values();

        $genericCardAccountId = Account::where('business_id', $business_id)
            ->where('name', 'Cards (Credit Debit) Account')
            ->value('id');

        $totals = [];
        $counts = [];

        foreach ($rows as $row) {
            $accountId = isset($row->card_type) && ! empty($row->card_type)
                ? (int) $row->card_type
                : (int) $genericCardAccountId;

            if ($accountId <= 0) {
                // A source row without a resolvable Card account cannot be placed
                // on a named F9A card row. Keep it out rather than misclassify it.
                continue;
            }

            $totals[$accountId] = ($totals[$accountId] ?? 0) + (float) $row->amount;
            $counts[$accountId] = ($counts[$accountId] ?? 0) + 1;
        }

        return ['totals' => $totals, 'counts' => $counts];
    }

    private function getLastResetInfo($business_id, $location_id, $date, $opening_date)
    {
        $date_obj = Carbon::parse($date);
        $start_of_month = $date_obj->copy()->startOfMonth();

        $reset_dates = collect();

        // 1. Monthly Reset
        $reset_dates->push(['date' => $start_of_month, 'type' => 'monthly', 'priority' => 1]);

        // 2. Opening Date Reset - Restrict to current month.
        // F22 stock-taking dates are intentionally excluded from Form 9A resets.
        if ($opening_date) {
            $opening_carbon = Carbon::parse($opening_date);
            if ($opening_carbon->isSameMonth($date_obj, true)) {
                $reset_dates->push(['date' => $opening_carbon, 'type' => 'opening', 'priority' => 2]);
            }
        }

        // We only care about reset dates that are <= Yesterday
        // Because Row 3/4 are totals UNTIL yesterday.
        $yesterday = $date_obj->copy()->subDay();
        $valid_resets = $reset_dates->filter(function($r) use ($yesterday) {
            return $r['date']->lte($yesterday);
        });

        if ($valid_resets->isEmpty()) {
            return ['date' => $start_of_month, 'type' => 'monthly'];
        }

        // Sort by date DESC, then by priority DESC (Opening > Monthly).
        $winner = $valid_resets->sort(function($a, $b) {
            if ($a['date']->eq($b['date'])) {
                return $b['priority'] <=> $a['priority'];
            }
            return $b['date'] <=> $a['date'];
        })->first();

        return ['date' => $winner['date'], 'type' => $winner['type']];
    }

    private function getFormNumber($business_id, $date)
    {
        if (empty($date)) return null;

        try {
            $date = Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }

        $setting = Mpcs9aFormSettings::where('business_id', $business_id)
            ->orderBy('date', 'asc')
            ->first();

        if (! $setting || empty($setting->date) || empty($setting->starting_number)) {
            return null;
        }

        // Simple form number calculation: starting number + days difference from opening date
        try {
            $opening_date = Carbon::parse($setting->date);
            $days_diff = $opening_date->diffInDays($date, false);
            return (int) $setting->starting_number + (int) $days_diff;
        } catch (\Exception $e) {
            return (int) $setting->starting_number;
        }
    }



    /**
     * Show the form for getFrom20
     * @return Response
     */
    public function get9CForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings    = MpcsFormSetting::where('business_id', $business_id)->first();
        if (! empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (request()->ajax()) {
            $start_date       = $request->start_date;
            $end_date         = $request->end_date;
            $start_today_date = $start_date;
            $end_today_date   = $end_date;
            $cash_sales_today = DB::table('transactions')
                ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->join('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id', $business_id)
                ->where('account_transactions.type', 'debit')
                ->whereNull('transactions.customer_group_id')
                ->whereDate('transactions.transaction_date', '>=', $start_today_date)
                ->whereDate('transactions.transaction_date', '<=', $end_today_date)
                ->groupBy('categories.id', 'categories.name')
                ->select(
                    'products.name as product',
                    'transactions.invoice_no as billno',
                    DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                    DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price) as total_sales')
                )
                ->get();
            return Datatables::of($cash_sales_today)

                ->make(true);
        }
    }

    public function get9CCRForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings    = MpcsFormSetting::where('business_id', $business_id)->first();
        if (! empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (request()->ajax()) {
            $start_date  = $request->start_date;
            $end_date    = $request->end_date;
            $location_id = $request->location_id;

            // Get credit sales data from F14 page for the same date
            $f14Controller = app(NewF14FormController::class);
            $f14Response = $f14Controller->getForm14WithDates($business_id, $start_date, $end_date, $location_id);
            $f14_data = $f14Response instanceof \Illuminate\Http\JsonResponse
                ? (array) $f14Response->getData(true)
                : (is_array($f14Response) ? $f14Response : (array) $f14Response);
            
            // Transform F14 data to match expected format for 9CCredit form
            $credit_sales = collect();
            if (isset($f14_data['data']) && is_array($f14_data['data'])) {
                foreach ($f14_data['data'] as $date_group) {
                    foreach ($date_group as $sale) {
                        // $sale is an array because it came from toArray() via JSON
                        $sale_date = $sale['settlement_date'] ?? ($sale['order_date'] ?? null);
                        $page_no = $this->getFormNumber($business_id, $sale_date);

                        $credit_sales->push((object)[
                            'billno' => $sale['bill_no'] ?? ($sale['invoice_no'] ?? ''),
                            'ourref' => $sale['our_ref'] ?? ($sale['ref_no'] ?? ''),
                            'product' => $sale['description'],
                            'quantity' => $sale['balance_qty'],
                            'page' => $page_no,
                            'final_total_rs' => floor($sale['final_total'] ?? 0),
                            'final_total_cents' => round((($sale['final_total'] ?? 0) - floor($sale['final_total'] ?? 0)) * 100),
                            'goods_rs' => floor(($sale['final_total'] ?? 0) * 0.8), // Example split
                            'goods_cents' => round((($sale['final_total'] ?? 0) * 0.8 - floor(($sale['final_total'] ?? 0) * 0.8)) * 100),
                            'loading_rs' => floor(($sale['final_total'] ?? 0) * 0.1), // Example split
                            'loading_cents' => round((($sale['final_total'] ?? 0) * 0.1 - floor(($sale['final_total'] ?? 0) * 0.1)) * 100),
                            'empty_rs' => floor(($sale['final_total'] ?? 0) * 0.05), // Example split
                            'empty_cents' => round((($sale['final_total'] ?? 0) * 0.05 - floor(($sale['final_total'] ?? 0) * 0.05)) * 100),
                            'transport_rs' => floor(($sale['final_total'] ?? 0) * 0.03), // Example split
                            'transport_cents' => round((($sale['final_total'] ?? 0) * 0.03 - floor(($sale['final_total'] ?? 0) * 0.03)) * 100),
                            'other_rs' => floor(($sale['final_total'] ?? 0) * 0.02), // Example split
                            'other_cents' => round((($sale['final_total'] ?? 0) * 0.02 - floor(($sale['final_total'] ?? 0) * 0.02)) * 100),
                        ]);
                    }
                }
            }

            $location = [];
            if (! empty($request->location_id)) {
                $location = BusinessLocation::findOrFail($request->location_id);
            }

            $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

            // Calculate previous totals for Grand Total display
            $previous_total = $this->getPreviousValue9CForm($request);
            $previous_total_rs = 0;
            foreach ($previous_total as $cat_id => $data) {
                $previous_total_rs += $data['amount'];
            }

            return response()->json([
                'data' => $credit_sales,
                'previous_total' => ['rs' => $previous_total_rs],
                'form_9ccr_no' => $F9C_sn,
                'custom_message' => $credit_sales->isEmpty() ? 'No data found for selected date' : ''
            ]);
        }
    }
    public function get21CForms(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings    = MpcsFormSetting::where('business_id', $business_id)->first();
        if (! empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        $business_details   = Business::find($business_id);
        $currency_precision = (int) $business_details->currency_precision;
        $qty_precision      = (int) $business_details->quantity_precision;
        if (request()->ajax()) {
            $start_date          = $request->start_date;
            $end_date            = $request->end_date;
            $location_id         = $request->location_id;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date   = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $credit_sales        = $this->Form9CQuery($business_id, $start_date, $end_date, $location_id);
            $todays              = $this->Form21CQuerytoday($business_id, $start_date, $end_date, $location_id);
            $receipts            = $this->Form21CQueryReceipts($business_id, $start_date, $end_date, $location_id);
            $previous_days       = $this->Form21CQuerypreviousday($business_id, $previous_start_date, $previous_end_date, $location_id);

            //   $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            //         $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
            $cash_sales_todays = $this->Form21CQueryCashToday($business_id, $start_date, $end_date, $location_id);
            $opening_stocks    = $this->Form21CQueryOpeningStock($business_id, $start_date, $end_date, $location_id);
            $issuess           = $this->Form21CQueryIssue($business_id, $start_date, $end_date, $location_id);
            //         $total_receipts =$this->Form21CQueryOpeningStock($business_id, $start_date, $end_date, $location_id);
            $total_receipts_todates    = $this->Form21CQueryTotalReceipts($business_id, $start_date, $end_date, $location_id);
            $corporate_section_todates = $this->Form21CQueryCorporate($business_id, $start_date, $end_date, $location_id);
            $total_issuess             = $this->Form21CQueryTotalIssue($business_id, $start_date, $end_date, $location_id);
            $issue_upto_last_days      = $this->Form21CQueryTotalIssueLastday($business_id, $start_date, $end_date, $location_id);
            $total_issue_ones          = $this->Form21CQueryTotalIssueOne($business_id, $start_date, $end_date, $location_id);
            $price_discount_todays     = $this->Form21CQueryDiscountToday($business_id, $start_date, $end_date, $location_id);
            $price_discount_previouss  = $this->Form21CQueryDiscountPrevious($business_id, $previous_start_date, $previous_end_date, $location_id);
            $total_discount_twos       = $this->Form21CQueryDiscountTotal($business_id, $start_date, $end_date, $location_id);
            $total_discount_one_twos   = $this->Form21CQueryOpeningStock($business_id, $start_date, $end_date, $location_id);
            $balances                  = $this->Form21CQueryBalance($business_id, $start_date, $end_date, $location_id);
            $subtotal_for_todays       = $this->Form21CQuerySubtotal($business_id, $start_date, $end_date, $location_id);
            $pump_one_last_meters      = $this->Form21CQueryPumperLast($business_id, $start_date, $end_date, $location_id);
            $issue_qty_todays          = $this->Form21CQueryQuantityIssue($business_id, $start_date, $end_date, $location_id);
            $location                  = [];
            if (! empty($request->location_id)) {
                $location = BusinessLocation::findOrFail($request->location_id);
            }

            $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

            return view('mpcs::forms.partials.21c_details_section')->with(compact('receipts', 'todays', 'previous_days', 'opening_stocks', 'cash_sales_todays', 'issuess', 'total_receipts_todates', 'credit_sales', 'corporate_section_todates', 'total_issuess', 'issue_upto_last_days', 'total_issue_ones', 'price_discount_todays', 'price_discount_previouss', 'total_discount_twos', 'total_discount_one_twos', 'balances', 'subtotal_for_todays', 'pump_one_last_meters', 'issue_qty_todays', 'sub_categories', 'start_date', 'end_date', 'currency_precision', 'qty_precision', 'location', 'F9C_sn'));
        }
    }

    public function Form9CQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')->leftjoin('business', 'transactions.business_id', 'business.id')->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')->where('transactions.business_id', $business_id)->where('transactions.is_credit_sale', 1)->whereNotNull('transaction_sell_lines.product_id') // Ensure product_id is not null
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'products.id as product_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        if (! empty($start_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date);
        }
        if (! empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        $credit_sales = $query->get();

        // Calculate totals for each product
        // Get all sales (not just credit sales) for date range
        // This includes ALL transaction types for the selected date range and product
        $all_sales_query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')->where('transactions.business_id', $business_id)->whereNotNull('transaction_sell_lines.product_id');
        
        if (! empty($location_id)) {
            $all_sales_query->where('transactions.location_id', $location_id);
        }
        if (! empty($start_date)) {
            $all_sales_query->whereDate('transactions.transaction_date', '>=', $start_date);
        }
        if (! empty($end_date)) {
            $all_sales_query->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        
        $all_sales = $all_sales_query->select('products.id as product_id', DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price) as total_sale_amount'))
            ->groupBy('products.id')
            ->get()
            ->keyBy('product_id');

        // Get credit sales totals for each product in the date range
        $credit_totals_query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')->where('transactions.business_id', $business_id)->where('transactions.is_credit_sale', 1)->whereNotNull('transaction_sell_lines.product_id');
        
        if (! empty($location_id)) {
            $credit_totals_query->where('transactions.location_id', $location_id);
        }
        if (! empty($start_date)) {
            $credit_totals_query->whereDate('transactions.transaction_date', '>=', $start_date);
        }
        if (! empty($end_date)) {
            $credit_totals_query->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        
        $credit_totals = $credit_totals_query->select('products.id as product_id', DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price) as total_credit_amount'))
            ->groupBy('products.id')
            ->get()
            ->keyBy('product_id');

        // Add calculated fields to each credit sale record
        // calculated_total = total_sale_amount (all transactions for product) - total_credit_amount (all credit sales for product)
        foreach ($credit_sales as $sale) {
            // Handle null product_id gracefully
            if (empty($sale->product_id)) {
                $sale->calculated_total = 0;
                continue;
            }

            $total_sale = $all_sales->get($sale->product_id)?->total_sale_amount ?? 0;
            $total_credit = $credit_totals->get($sale->product_id)?->total_credit_amount ?? 0;
            $sale->calculated_total = max(0, (float)$total_sale - (float)$total_credit); // Ensure result is never negative
        }

        return $credit_sales;
    }

    public function getPreviousValue9CForm(Request $request)
    {
        $business_id       = request()->session()->get('user.business_id');
        $settings          = MpcsFormSetting::where('business_id', $business_id)->first();
        $F9C_tdate         = $settings->F9C_tdate;
        $checkSetValueZero = $this->checkSetValueZero9C($request);
        $start_date        = $checkSetValueZero['start_date'];
        $end_date          = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $location_id       = $request->location_id;

        $credit_sales   = $this->Form9CQuery($business_id, $start_date, $end_date, $location_id);
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

        $sub_cat_data = [];
        foreach ($sub_categories as $item) {
            $pre_page_set_value = Form9cSubCategory::where('business_id', $business_id)->where('sub_category_id', $item->id)->first();
            if ($request->start_date == $F9C_tdate) {
                $sub_cat_data[$item->id]['qty']    = ! empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0;
                $sub_cat_data[$item->id]['amount'] = ! empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0;
            } elseif ($checkSetValueZero['status']) {
                $sub_cat_data[$item->id]['qty']    = 0.0;
                $sub_cat_data[$item->id]['amount'] = 0.0;
            } elseif ($checkSetValueZero['lap']) {
                $sub_cat_data[$item->id]['qty']    = 0;
                $sub_cat_data[$item->id]['amount'] = 0;
                foreach ($credit_sales as $sale) {
                    if ($item->id == $sale->sub_category_id) {
                        $sub_cat_data[$item->id]['qty']    = $sale->quantity;
                        $sub_cat_data[$item->id]['amount'] = $sale->unit_price * $sale->quantity;
                    }
                }
            } else {
                $sub_cat_data[$item->id]['qty']    = ! empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0;
                $sub_cat_data[$item->id]['amount'] = ! empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0;
                foreach ($credit_sales as $sale) {
                    if ($item->id == $sale->sub_category_id) {
                        $sub_cat_data[$item->id]['qty']    = ! empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0 + $sale->quantity;
                        $sub_cat_data[$item->id]['amount'] = ! empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0 + $sale->unit_price * $sale->quantity;
                    }
                }
            }
        }

        return $sub_cat_data;
    }

    public function checkSetValueZero9C($request)
    {
        $business_id          = request()->session()->get('user.business_id');
        $settings             = MpcsFormSetting::where('business_id', $business_id)->first();
        $result['status']     = 0;
        $result['lap']        = 0;
        $result['start_date'] = $settings->F9C_tdate;
        $date_diff_days       = Carbon::parse($settings->F9C_tdate)->diffInDays(Carbon::parse($request->start_date), false);
        $date_diff_months     = Carbon::parse($settings->F9C_tdate)->diffInMonths(Carbon::parse($request->start_date), false);
        if ($date_diff_days < 0) {
            $result['status'] = 1;
        }
        $stock_taking_date = FormF22Header::where('business_id', $business_id)->orderBy('id', 'desc')->first();
        if (! empty($stock_taking_date)) {
            if ($request->start_date == $stock_taking_date->form_date) {
                $result['lap'] = 1;
            }
        }

        if ($settings->F9C_first_day_of_next_month) {
            $F9C_first_day_of_next_month_selected = $date_diff_days <= 30 * $settings->F9C_first_day_of_next_month_selected ? $settings->F9C_first_day_of_next_month_selected - 1 : $settings->F9C_first_day_of_next_month_selected;
            if (! empty($F9C_first_day_of_next_month_selected)) {
                $first_date_after_selected_mothn_from_start_date = Carbon::parse($settings->F9C_tdate)->addMonth($F9C_first_day_of_next_month_selected)->firstOfMonth()->format('Y-m-d');

                if ($date_diff_months % $F9C_first_day_of_next_month_selected == 0 && $date_diff_months > 0) {
                    $result['start_date'] = $first_date_after_selected_mothn_from_start_date;
                    $result['lap']        = 1;
                }
                if ($request->start_date == $first_date_after_selected_mothn_from_start_date) {
                    $result['status'] = 1;
                }
            }
        }

        return $result;
    }

    public function get16AForm(Request $request)
    {
        try {
            $businessId = $request->session()->get('user.business_id')
                ?? $request->session()->get('business.id');

            if (empty($businessId)) {
                return response()->json(['error' => 'Business context was not found.'], 422);
            }

            $business = Business::find($businessId);
            if (! $business) {
                return response()->json(['error' => 'Business was not found.'], 404);
            }

            $currencyPrecision = max(0, min(6, (int) ($business->currency_precision ?? 2)));
            $qtyPrecision = max(0, min(6, (int) ($business->quantity_precision ?? 2)));
            $selectedDate = Carbon::parse($request->input('start_date', now()->format('Y-m-d')))->format('Y-m-d');
            $endDate = Carbon::parse($request->input('end_date', $selectedDate))->format('Y-m-d');
            $locationId = $request->filled('location_id') ? trim((string) $request->input('location_id')) : null;
            $locationId = $locationId !== '' ? $locationId : null;
            $productId = $request->filled('product_id') ? trim((string) $request->input('product_id')) : null;
            $productId = $productId !== '' ? $productId : null;

            $formNo = $this->getF16AFormNumberForSelectedDate(
                $businessId,
                $selectedDate,
                $locationId
            );

            $setting21c = Mpcs21cFormSettings::where('business_id', $businessId)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            $getRelated21cFormNumber = function ($transactionDate) use ($setting21c) {
                if (empty($setting21c) || empty($transactionDate)) {
                    return '';
                }

                $startingNumber = (int) ($setting21c->starting_number
                    ?: $setting21c->ref_pre_form_number
                    ?: 1);

                if (empty($setting21c->date)) {
                    return (string) $startingNumber;
                }

                $openingDate = Carbon::parse($setting21c->date)->startOfDay();
                $purchaseDate = Carbon::parse($transactionDate)->startOfDay();

                if ($purchaseDate->lt($openingDate)) {
                    return (string) $startingNumber;
                }

                return (string) ($startingNumber + $openingDate->diffInDays($purchaseDate));
            };

            $rowsCacheKey = $this->getF16ACachePrefix($businessId)
                . ':' . $this->getF16ADataVersion($businessId)
                . ':rows:' . sha1(json_encode([
                    $selectedDate,
                    $endDate,
                    (string) $locationId,
                    (string) $productId,
                    $currencyPrecision,
                    $qtyPrecision,
                ]));

            $rows = collect($this->rememberMpcsValue($rowsCacheKey, 900, function () use (
                $businessId,
                $selectedDate,
                $endDate,
                $locationId,
                $productId,
                $currencyPrecision,
                $qtyPrecision,
                $getRelated21cFormNumber
            ) {
                $purchaseList = $this->F16AQuery(
                    $businessId,
                    $selectedDate,
                    $endDate,
                    $locationId,
                    $productId
                )->get();

                $f16ByTransaction = collect();
                if ($purchaseList->isNotEmpty()) {
                    $f16ByTransaction = FormF16Detail::whereIn(
                        'transaction_id',
                        $purchaseList->pluck('id')->filter()->values()->all()
                    )->orderByDesc('id')->get()->unique('transaction_id')->keyBy('transaction_id');
                }

                $preparedRows = [];
                foreach ($purchaseList as $purchase) {
                    $savedF16 = $f16ByTransaction->get($purchase->id);
                    $indexNo = $savedF16 ? $savedF16->form_no : $purchase->order_no;
                    $invoiceNo = $savedF16
                        ? ($savedF16->invoice_no ?? $purchase->invoice_no)
                        : ($purchase->invoice_no ?? '-');

                    foreach ($purchase->purchase_lines as $line) {
                        $unitPurchase = $line->purchase_price_inc_tax;
                        if ($unitPurchase === null || (float) $unitPurchase <= 0) {
                            $unitPurchase = (float) ($line->purchase_price ?? 0)
                                + (float) ($line->item_tax ?? 0);
                        }
                        $unitPurchase = (float) ($unitPurchase ?? 0);

                        $unitSale = $line->sell_price_at_purchase;
                        if ($unitSale === null || (float) $unitSale <= 0) {
                            $unitSale = optional($line->variation)->sell_price_inc_tax;
                        }
                        if ($unitSale === null || (float) $unitSale <= 0) {
                            $unitSale = optional($line->variation)->default_sell_price;
                        }
                        if ($unitSale === null || (float) $unitSale <= 0) {
                            $unitSale = optional($line->variations)->sell_price_inc_tax;
                        }
                        if ($unitSale === null || (float) $unitSale <= 0) {
                            $unitSale = optional($line->variations)->default_sell_price;
                        }
                        $unitSale = (float) ($unitSale ?? 0);

                        $quantity = (float) ($line->quantity ?? 0);
                        $purchaseTotal = round($unitPurchase * $quantity, $currencyPrecision, PHP_ROUND_HALF_UP);
                        $saleTotal = round($unitSale * $quantity, $currencyPrecision, PHP_ROUND_HALF_UP);
                        $lineId = (int) ($line->id ?? 0);
                        $transactionId = (int) ($purchase->id ?? 0);
                        $transactionDate = Carbon::parse($purchase->transaction_date ?? $selectedDate)
                            ->format('Y-m-d H:i:s');

                        $preparedRows[] = [
                            'index_no' => $indexNo,
                            'reference_no' => $purchase->reference_no ?? '-',
                            'invoice_no' => $invoiceNo,
                            'product' => optional($line->product)->name,
                            'location' => $purchase->location_name
                                ?? $purchase->location
                                ?? optional($purchase->location)->name
                                ?? '-',
                            'received_qty' => number_format($quantity, $qtyPrecision),
                            'unit_purchase_price' => number_format($unitPurchase, $currencyPrecision),
                            'total_purchase_price' => '<span class="display_currency total_purchase_price" data-orig-value="'
                                . $purchaseTotal . '" data-currency_symbol="false">'
                                . number_format($purchaseTotal, $currencyPrecision) . '</span>',
                            'unit_sale_price' => number_format($unitSale, $currencyPrecision),
                            'total_sale_price' => '<span class="display_currency total_sale_price" data-orig-value="'
                                . $saleTotal . '" data-currency_symbol="false">'
                                . number_format($saleTotal, $currencyPrecision) . '</span>',
                            'stock_book_no' => $getRelated21cFormNumber($purchase->transaction_date),
                            'action' => '',
                            '_purchase_total' => $purchaseTotal,
                            '_sale_total' => $saleTotal,
                            '_sort_date' => $transactionDate,
                            '_transaction_id' => $transactionId,
                            '_line_id' => $lineId,
                            '_stable_id' => $transactionId . ':' . $lineId,
                        ];
                    }
                }

                return $preparedRows;
            }));

            $previousTotals = $this->getF16APreviousDayTotals(
                $businessId,
                $selectedDate,
                $locationId,
                $productId,
                $currencyPrecision
            );

            $page = $this->paginateF16ACollection(
                $rows,
                $request,
                $businessId,
                $selectedDate,
                $locationId,
                $productId,
                $currencyPrecision,
                $previousTotals
            );

            $pageCount = $page['page_length'] > 0
                ? (int) ceil($page['recordsFiltered'] / $page['page_length'])
                : 1;
            $displayForm = (string) $formNo;
            if ($pageCount > 1) {
                $displayForm .= '-' . ((int) $page['page_index'] + 1);
            }

            return response()->json([
                'draw' => $page['draw'],
                'recordsTotal' => $page['recordsTotal'],
                'recordsFiltered' => $page['recordsFiltered'],
                'data' => $page['data'],
                'form_no' => $formNo,
                'page_no' => (int) $page['page_index'] + 1,
                'display_form' => $displayForm,
                'page_index' => $page['page_index'],
                'page_length' => $page['page_length'],
                'page_total_purchase' => $page['page_total_purchase'],
                'page_total_sale' => $page['page_total_sale'],
                'total_previous_day_purchase' => $page['base_previous_purchase'],
                'total_previous_day_sale' => $page['base_previous_sale'],
                'previous_page_total_purchase' => $page['previous_page_total_purchase'],
                'previous_page_total_sale' => $page['previous_page_total_sale'],
                'grand_total_purchase' => $page['grand_total_purchase'],
                'grand_total_sale' => $page['grand_total_sale'],
                'report_version' => $page['report_version'],
                'report_criteria' => $page['report_criteria'],
                'reset_to_first_page' => $page['reset_to_first_page'],
                'data_version_changed' => $page['data_version_changed'],
                'criteria_changed' => $page['criteria_changed'],
                'max_page_length' => 100,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error in get16AForm', [
                'message' => $exception->getMessage(),
                'request_data' => $request->except(['columns']),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Unable to load F16A data. Please check the application log.',
            ], 500);
        }
    }

    private function getF16AFormNumberForSelectedDate($businessId, $selectedDate, $locationId = null)
    {
        $setting = Mpcs16aFormSettings::where('business_id', $businessId)
            ->whereDate('date', '<=', $selectedDate)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if (! $setting) {
            $setting = Mpcs16aFormSettings::where('business_id', $businessId)
                ->orderBy('date')
                ->orderBy('id')
                ->first();
        }

        $startingNumber = (int) ($setting->starting_number ?? 1);
        if (! $setting || empty($setting->date)) {
            return max(1, $startingNumber);
        }

        $openingDate = Carbon::parse($setting->date)->format('Y-m-d');
        if ($selectedDate < $openingDate) {
            return max(1, $startingNumber);
        }

        $purchaseDates = Transaction::where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->where('transaction_date', '>=', Carbon::parse($openingDate)->startOfDay())
            ->where('transaction_date', '<', Carbon::parse($selectedDate)->addDay()->startOfDay())
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->selectRaw('DATE(transaction_date) as tx_date')
            ->distinct()
            ->orderBy('tx_date')
            ->pluck('tx_date')
            ->values();

        $dateIndex = $purchaseDates->search($selectedDate);

        return max(1, $startingNumber + ($dateIndex === false ? 0 : (int) $dateIndex));
    }

    private function getF16ACachePrefix($businessId)
    {
        $database = (string) DB::connection()->getDatabaseName();

        return 'mpcs:f16a:' . sha1($database . '|' . (string) $businessId);
    }

    private function getF16ADataVersion($businessId)
    {
        static $requestVersions = [];

        $prefix = $this->getF16ACachePrefix($businessId);
        if (isset($requestVersions[$prefix])) {
            return $requestVersions[$prefix];
        }

        $version = $this->rememberMpcsValue($prefix . ':data-version', 2, function () use ($businessId) {
            $sources = [
                ['transactions', 'business_id'],
                ['mpcs_16a_form_settings', 'business_id'],
                ['mpcs_21c_form_settings', 'business_id'],
                ['products', 'business_id'],
                ['purchase_lines', null],
                ['variations', null],
                ['form_f16_details', null],
            ];

            $parts = [];
            foreach ($sources as [$table, $businessColumn]) {
                try {
                    if (! Schema::hasTable($table)) {
                        $parts[] = $table . ':missing';
                        continue;
                    }

                    $query = DB::table($table);
                    if ($businessColumn && Schema::hasColumn($table, $businessColumn)) {
                        $query->where($businessColumn, $businessId);
                    }

                    if (Schema::hasColumn($table, 'updated_at')) {
                        $row = $query->selectRaw('COALESCE(MAX(id), 0) as max_id, MAX(updated_at) as max_updated_at')->first();
                        $parts[] = $table . ':' . ($row->max_id ?? 0) . ':' . ($row->max_updated_at ?? '');
                    } else {
                        $parts[] = $table . ':' . ($query->max('id') ?? 0);
                    }
                } catch (\Throwable $ignored) {
                    $parts[] = $table . ':unavailable';
                }
            }

            return sha1(implode('|', $parts));
        });

        $requestVersions[$prefix] = $version;

        return $version;
    }

    private function getF16APriceExpressions($purchaseAlias = 'pl', $variationAlias = 'v')
    {
        static $expressionsByDatabase = [];

        $database = (string) DB::connection()->getDatabaseName();
        if (isset($expressionsByDatabase[$database])) {
            return $expressionsByDatabase[$database];
        }

        $purchaseParts = [];
        if (Schema::hasColumn('purchase_lines', 'purchase_price_inc_tax')) {
            $purchaseParts[] = "NULLIF({$purchaseAlias}.purchase_price_inc_tax, 0)";
        }
        if (Schema::hasColumn('purchase_lines', 'purchase_price')) {
            if (Schema::hasColumn('purchase_lines', 'item_tax')) {
                $purchaseParts[] = "(COALESCE({$purchaseAlias}.purchase_price, 0) + COALESCE({$purchaseAlias}.item_tax, 0))";
            } else {
                $purchaseParts[] = "COALESCE({$purchaseAlias}.purchase_price, 0)";
            }
        }
        $purchaseParts[] = '0';

        $saleParts = [];
        if (Schema::hasColumn('purchase_lines', 'sell_price_at_purchase')) {
            $saleParts[] = "NULLIF({$purchaseAlias}.sell_price_at_purchase, 0)";
        }
        if (Schema::hasColumn('variations', 'sell_price_inc_tax')) {
            $saleParts[] = "NULLIF({$variationAlias}.sell_price_inc_tax, 0)";
        }
        if (Schema::hasColumn('variations', 'default_sell_price')) {
            $saleParts[] = "NULLIF({$variationAlias}.default_sell_price, 0)";
        }
        $saleParts[] = '0';

        return $expressionsByDatabase[$database] = [
            'purchase' => 'COALESCE(' . implode(', ', $purchaseParts) . ')',
            'sale' => 'COALESCE(' . implode(', ', $saleParts) . ')',
        ];
    }

    private function getF16APreviousDayTotals(
        $businessId,
        $selectedDate,
        $locationId,
        $productId,
        $currencyPrecision
    ) {
        $version = $this->getF16ADataVersion($businessId);
        $cacheKey = $this->getF16ACachePrefix($businessId)
            . ':' . $version
            . ':previous-monthly-v2:' . sha1(json_encode([
                $selectedDate,
                (string) $locationId,
                (string) $productId,
                (int) $currencyPrecision,
            ]));

        return $this->rememberMpcsValue($cacheKey, 900, function () use (
            $businessId,
            $selectedDate,
            $locationId,
            $productId,
            $currencyPrecision
        ) {
            $selected = Carbon::parse($selectedDate)->startOfDay();
            $monthStart = $selected->copy()->startOfMonth();

            /*
             * IS1726: F16A previous totals are monthly.  The first day of each
             * month starts at zero.  On later days only purchases from the
             * selected month, up to the previous day, are carried.
             */
            if ($selected->isSameDay($monthStart)) {
                return [
                    'purchase' => 0.0,
                    'sale' => 0.0,
                    'previous_date' => $selected->copy()->subDay()->format('Y-m-d'),
                ];
            }

            $setting = Mpcs16aFormSettings::where('business_id', $businessId)
                ->whereDate('date', '<=', $selectedDate)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            $openingDate = $setting && ! empty($setting->date)
                ? Carbon::parse($setting->date)->startOfDay()
                : null;

            // Opening balances belong only to the month in which that setting starts.
            $openingIsInSelectedMonth = $openingDate
                && $openingDate->isSameMonth($selected, true);

            $openingPurchase = $openingIsInSelectedMonth
                ? (float) ($setting->total_purchase_price_with_vat ?? 0)
                : 0.0;
            $openingSale = $openingIsInSelectedMonth
                ? (float) ($setting->total_sale_price_with_vat ?? 0)
                : 0.0;

            $periodStart = $monthStart->copy();
            if ($openingIsInSelectedMonth && $openingDate->gt($periodStart)) {
                $periodStart = $openingDate->copy();
            }

            $query = DB::table('transactions as t')
                ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
                ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'purchase')
                ->where('t.status', 'received')
                ->where('t.transaction_date', '>=', $periodStart)
                ->where('t.transaction_date', '<', $selected);

            if ($locationId) {
                $query->where('t.location_id', $locationId);
            }
            if ($productId) {
                $query->where('pl.product_id', $productId);
            }
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $query->whereNull('t.deleted_at');
            }
            if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
                $query->whereNull('pl.deleted_at');
            }

            $prices = $this->getF16APriceExpressions('pl', 'v');
            $precision = (int) $currencyPrecision;
            $totals = $query->selectRaw(
                "COALESCE(SUM(ROUND(({$prices['purchase']}) * COALESCE(pl.quantity, 0), {$precision})), 0) AS purchase_total, "
                . "COALESCE(SUM(ROUND(({$prices['sale']}) * COALESCE(pl.quantity, 0), {$precision})), 0) AS sale_total"
            )->first();

            return [
                'purchase' => $openingPurchase + (float) ($totals->purchase_total ?? 0),
                'sale' => $openingSale + (float) ($totals->sale_total ?? 0),
                'previous_date' => $selected->copy()->subDay()->format('Y-m-d'),
            ];
        });
    }


    private function normalizeF16APageLength($requestedLength)
    {
        $requestedLength = (int) $requestedLength;
        $allowed = [25, 50, 100];

        return in_array($requestedLength, $allowed, true) ? $requestedLength : 25;
    }

    private function getF16AReportCriteriaHash(
        Request $request,
        $businessId,
        $selectedDate,
        $locationId,
        $productId,
        $currencyPrecision,
        $pageLength
    ) {
        return sha1(json_encode([
            'business_id' => (string) $businessId,
            'date' => (string) $selectedDate,
            'location_id' => (string) $locationId,
            'product_id' => (string) $productId,
            'search' => trim((string) $request->input('search.value', '')),
            'currency_precision' => (int) $currencyPrecision,
            'page_length' => (int) $pageLength,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function paginateF16ACollection(
        $rows,
        Request $request,
        $businessId,
        $selectedDate,
        $locationId,
        $productId,
        $currencyPrecision,
        array $previousTotals
    ) {
        $allRows = collect($rows)->map(function ($row) {
            return is_array($row) ? $row : (array) $row;
        })->values();

        $recordsTotal = $allRows->count();
        $search = mb_strtolower(trim((string) $request->input('search.value', '')));
        if ($search !== '') {
            $searchable = [
                'index_no',
                'reference_no',
                'invoice_no',
                'product',
                'location',
                'received_qty',
                'unit_purchase_price',
                'unit_sale_price',
                'stock_book_no',
            ];

            $allRows = $allRows->filter(function ($row) use ($searchable, $search) {
                foreach ($searchable as $key) {
                    if (mb_strpos(mb_strtolower((string) ($row[$key] ?? '')), $search) !== false) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $allRows = $allRows->sort(function ($left, $right) {
            $dateComparison = strcmp((string) ($right['_sort_date'] ?? ''), (string) ($left['_sort_date'] ?? ''));
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            $transactionComparison = ((int) ($right['_transaction_id'] ?? 0)) <=> ((int) ($left['_transaction_id'] ?? 0));
            if ($transactionComparison !== 0) {
                return $transactionComparison;
            }

            $lineComparison = ((int) ($left['_line_id'] ?? 0)) <=> ((int) ($right['_line_id'] ?? 0));
            if ($lineComparison !== 0) {
                return $lineComparison;
            }

            return strnatcasecmp((string) ($left['_stable_id'] ?? ''), (string) ($right['_stable_id'] ?? ''));
        })->values();

        $recordsFiltered = $allRows->count();
        $length = $this->normalizeF16APageLength($request->input('length', 25));
        $criteriaHash = $this->getF16AReportCriteriaHash(
            $request,
            $businessId,
            $selectedDate,
            $locationId,
            $productId,
            $currencyPrecision,
            $length
        );
        $reportVersion = $this->getF16ADataVersion($businessId);
        $requestedStart = max(0, (int) $request->input('start', 0));
        $clientVersion = (string) $request->input('report_version', '');
        $clientCriteria = (string) $request->input('report_criteria', '');

        $versionChanged = $requestedStart > 0
            && $clientVersion !== ''
            && ! hash_equals($reportVersion, $clientVersion);
        $criteriaChanged = $requestedStart > 0
            && $clientCriteria !== ''
            && ! hash_equals($criteriaHash, $clientCriteria);
        $pageOutOfRange = $requestedStart > 0 && $requestedStart >= $recordsFiltered;
        $resetToFirstPage = $versionChanged || $criteriaChanged || $pageOutOfRange;
        $effectiveStart = $resetToFirstPage ? 0 : $requestedStart;
        $endIndex = min($effectiveStart + $length, $recordsFiltered);

        $scale = 10 ** $currencyPrecision;
        $purchasePrefix = [0];
        $salePrefix = [0];
        $purchaseRunning = 0;
        $saleRunning = 0;

        foreach ($allRows as $row) {
            $purchaseRunning += (int) round(
                (float) ($row['_purchase_total'] ?? 0) * $scale,
                0,
                PHP_ROUND_HALF_UP
            );
            $saleRunning += (int) round(
                (float) ($row['_sale_total'] ?? 0) * $scale,
                0,
                PHP_ROUND_HALF_UP
            );
            $purchasePrefix[] = $purchaseRunning;
            $salePrefix[] = $saleRunning;
        }

        $basePurchase = (int) round((float) ($previousTotals['purchase'] ?? 0) * $scale, 0, PHP_ROUND_HALF_UP);
        $baseSale = (int) round((float) ($previousTotals['sale'] ?? 0) * $scale, 0, PHP_ROUND_HALF_UP);
        $purchaseBefore = (int) ($purchasePrefix[$effectiveStart] ?? 0);
        $saleBefore = (int) ($salePrefix[$effectiveStart] ?? 0);
        $purchaseThrough = (int) ($purchasePrefix[$endIndex] ?? $purchaseBefore);
        $saleThrough = (int) ($salePrefix[$endIndex] ?? $saleBefore);

        $pageRows = $allRows->slice($effectiveStart, $length)->map(function ($row) {
            unset(
                $row['_purchase_total'],
                $row['_sale_total'],
                $row['_sort_date'],
                $row['_transaction_id'],
                $row['_line_id'],
                $row['_stable_id']
            );

            return $row;
        })->values()->all();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pageRows,
            'page_index' => intdiv($effectiveStart, $length),
            'page_length' => $length,
            'page_total_purchase' => ($purchaseThrough - $purchaseBefore) / $scale,
            'page_total_sale' => ($saleThrough - $saleBefore) / $scale,
            'base_previous_purchase' => $basePurchase / $scale,
            'base_previous_sale' => $baseSale / $scale,
            'previous_page_total_purchase' => ($basePurchase + $purchaseBefore) / $scale,
            'previous_page_total_sale' => ($baseSale + $saleBefore) / $scale,
            'grand_total_purchase' => ($basePurchase + $purchaseThrough) / $scale,
            'grand_total_sale' => ($baseSale + $saleThrough) / $scale,
            'report_version' => $reportVersion,
            'report_criteria' => $criteriaHash,
            'reset_to_first_page' => $resetToFirstPage,
            'data_version_changed' => $versionChanged,
            'criteria_changed' => $criteriaChanged,
        ];
    }

    /**
     * Return a tenant-schema-safe location expression for settlement credit rows.
     *
     * Older tenant databases do not contain settlement_credit_sale_payments.location_id.
     * Cache the schema result because information_schema checks are costly on every Ajax draw.
     */
    private function getSettlementCreditLocationExpression($creditAlias = 'scsp', $transactionAlias = 't')
    {
        static $resolvedByDatabase = [];

        $database = (string) DB::connection()->getDatabaseName();
        if (! array_key_exists($database, $resolvedByDatabase)) {
            $cacheKey = 'mpcs:schema:' . sha1($database) . ':scsp_location_id';
            $resolvedByDatabase[$database] = $this->rememberMpcsValue($cacheKey, 3600, function () {
                return DB::getSchemaBuilder()->hasColumn('settlement_credit_sale_payments', 'location_id');
            });
        }

        if ($resolvedByDatabase[$database]) {
            return "COALESCE({$creditAlias}.location_id, {$transactionAlias}.location_id)";
        }

        return "{$transactionAlias}.location_id";
    }

    /**
     * Cache helper with a safe fallback for installations whose cache store is unavailable.
     */
    private function rememberMpcsValue($key, $seconds, callable $callback)
    {
        try {
            return Cache::remember($key, now()->addSeconds($seconds), $callback);
        } catch (\Throwable $exception) {
            return $callback();
        }
    }

    /**
     * Tenant-safe cache namespace. The same business id can exist in multiple tenant databases.
     */
    private function getF9CCachePrefix($businessId)
    {
        $database = (string) DB::connection()->getDatabaseName();

        return 'mpcs:f9c:' . sha1($database . '|' . (string) $businessId);
    }

    /**
     * Lightweight data signature used to invalidate cached F9C calculations automatically.
     * It changes whenever relevant sales, credit rows, products or settings are inserted/updated.
     */
    private function getF9CDataVersion($businessId)
    {
        static $requestVersions = [];

        $prefix = $this->getF9CCachePrefix($businessId);
        if (isset($requestVersions[$prefix])) {
            return $requestVersions[$prefix];
        }

        $version = $this->rememberMpcsValue($prefix . ':data-version', 2, function () use ($businessId) {
            $sources = [
                ['transactions', 'business_id'],
                ['settlements', 'business_id'],
                ['account_transactions', 'business_id'],
                ['meter_sales', 'business_id'],
                ['other_sales', 'business_id'],
                ['settlement_credit_sale_payments', 'business_id'],
                ['settlement_card_payments', 'business_id'],
                ['settlement_cash_payments', 'business_id'],
                ['settlement_cheque_payments', 'business_id'],
                ['products', 'business_id'],
                ['mpcs_9c_cash_form_settings', 'business_id'],
                ['mpcs_9c_credit_form_settings', 'business_id'],
                ['mpcs_9a_form_settings', 'business_id'],
            ];

            $parts = [];
            foreach ($sources as [$table, $businessColumn]) {
                try {
                    $row = DB::table($table)
                        ->where($businessColumn, $businessId)
                        ->selectRaw('COALESCE(MAX(id), 0) as max_id, MAX(updated_at) as max_updated_at')
                        ->first();
                    $parts[] = $table . ':' . ($row->max_id ?? 0) . ':' . ($row->max_updated_at ?? '');
                } catch (\Throwable $exception) {
                    // Some legacy tenant tables do not contain timestamps. MAX(id) is still enough
                    // to invalidate inserts; errors are isolated so the page continues to load.
                    try {
                        $maxId = DB::table($table)
                            ->where($businessColumn, $businessId)
                            ->max('id');
                        $parts[] = $table . ':' . ($maxId ?? 0);
                    } catch (\Throwable $ignored) {
                        $parts[] = $table . ':missing';
                    }
                }
            }

            return sha1(implode('|', $parts));
        });

        $requestVersions[$prefix] = $version;

        return $version;
    }

    private function getF9CFilterSignature(Request $request)
    {
        return implode(':', [
            (string) $request->input('product_category_id', ''),
            (string) $request->input('product_sub_category_id', ''),
            (string) $request->input('product_id', ''),
        ]);
    }

    private function rememberF9CCalculation($businessId, $suffix, callable $callback, $seconds = 900)
    {
        $key = $this->getF9CCachePrefix($businessId)
            . ':' . $this->getF9CDataVersion($businessId)
            . ':' . $suffix;

        return $this->rememberMpcsValue($key, $seconds, $callback);
    }

    /**
     * Resolve the settings row effective for the selected date without loading the whole table.
     */
    private function getEffectiveF9CSetting($modelClass, $businessId, $selectedDate)
    {
        $table = (new $modelClass())->getTable();
        $suffix = 'setting:' . $table . ':' . $selectedDate;

        return $this->rememberF9CCalculation($businessId, $suffix, function () use ($modelClass, $businessId, $selectedDate) {
            return $modelClass::where('business_id', $businessId)
                ->whereNotNull('date_time')
                // Legacy settings store yyyy/mm/dd while newer tenants may store yyyy-mm-dd.
                ->whereRaw("REPLACE(LEFT(date_time, 10), '/', '-') <= ?", [$selectedDate])
                ->orderByRaw("REPLACE(LEFT(date_time, 10), '/', '-') DESC")
                ->orderBy('id', 'desc')
                ->first();
        });
    }

    /**
     * Apply an index-friendly [from, to] date range to a datetime column.
     */
    private function applyTransactionDateRange($query, $column, $fromDate, $toDate)
    {
        $toExclusive = Carbon::parse($toDate)->addDay()->startOfDay()->format('Y-m-d H:i:s');
        $query->where($column, '<', $toExclusive);

        if (! empty($fromDate)) {
            $fromInclusive = Carbon::parse($fromDate)->startOfDay()->format('Y-m-d H:i:s');
            $query->where($column, '>=', $fromInclusive);
        }

        return $query;
    }

    /**
     * Apply the settlement-credit business-date rule without wrapping indexed columns in DATE().
     * Business date priority: edited linked transaction/settlement date, then legacy order_date, then created_at.
     */
    private function applySettlementCreditDateRange($query, $creditAlias, $transactionAlias, $fromDate, $toDate)
    {
        $orderColumn = $creditAlias . '.order_date';
        $transactionColumn = $transactionAlias . '.transaction_date';
        $createdColumn = $creditAlias . '.created_at';

        $fromDateOnly = ! empty($fromDate) ? Carbon::parse($fromDate)->format('Y-m-d') : null;
        $fromDateTime = ! empty($fromDate) ? Carbon::parse($fromDate)->startOfDay()->format('Y-m-d H:i:s') : null;
        $toExclusiveDate = Carbon::parse($toDate)->addDay()->format('Y-m-d');
        $toExclusiveDateTime = Carbon::parse($toDate)->addDay()->startOfDay()->format('Y-m-d H:i:s');

        return $query->where(function ($outer) use (
            $orderColumn,
            $transactionColumn,
            $createdColumn,
            $fromDateOnly,
            $fromDateTime,
            $toExclusiveDate,
            $toExclusiveDateTime
        ) {
            // IS2188: for a finalized Direct Settlement, transaction_date is synchronized
            // to settlements.transaction_date. It therefore becomes the authoritative MPCS
            // date after an edit. order_date is retained only for legacy/unlinked rows.
            $outer->where(function ($branch) use ($transactionColumn, $fromDateTime, $toExclusiveDateTime) {
                $branch->whereNotNull($transactionColumn)
                    ->where($transactionColumn, '<', $toExclusiveDateTime);
                if ($fromDateTime !== null) {
                    $branch->where($transactionColumn, '>=', $fromDateTime);
                }
            })->orWhere(function ($branch) use (
                $transactionColumn,
                $orderColumn,
                $fromDateOnly,
                $toExclusiveDate
            ) {
                $branch->whereNull($transactionColumn)
                    ->whereNotNull($orderColumn)
                    ->where($orderColumn, '!=', '')
                    ->where($orderColumn, '<', $toExclusiveDate);
                if ($fromDateOnly !== null) {
                    $branch->where($orderColumn, '>=', $fromDateOnly);
                }
            })->orWhere(function ($branch) use (
                $transactionColumn,
                $orderColumn,
                $createdColumn,
                $fromDateTime,
                $toExclusiveDateTime
            ) {
                $branch->whereNull($transactionColumn)
                    ->where(function ($missingOrder) use ($orderColumn) {
                        $missingOrder->whereNull($orderColumn)->orWhere($orderColumn, '');
                    })
                    ->where($createdColumn, '<', $toExclusiveDateTime);
                if ($fromDateTime !== null) {
                    $branch->where($createdColumn, '>=', $fromDateTime);
                }
            });
        });
    }

    /**
     * Build a deterministic signature for one visible F9C result set.
     * Paging offsets and page length are deliberately excluded: the server can
     * calculate any page directly from the same ordered result set.
     */
    private function getF9CReportCriteriaHash(
        Request $request,
        $formType,
        $businessId,
        $startDate,
        $endDate,
        $locationId,
        $currencyPrecision
    ) {
        $orders = collect((array) $request->input('order', []))
            ->map(function ($order) use ($request) {
                $columnIndex = isset($order['column']) ? (int) $order['column'] : -1;
                $columnName = $columnIndex >= 0
                    ? (string) $request->input("columns.{$columnIndex}.data", '')
                    : '';

                return [
                    'column' => $columnName,
                    'direction' => strtolower((string) ($order['dir'] ?? 'asc')) === 'desc'
                        ? 'desc'
                        : 'asc',
                ];
            })
            ->values()
            ->all();

        $payload = [
            'form_type' => (string) $formType,
            'business_id' => (string) $businessId,
            'start_date' => (string) $startDate,
            'end_date' => (string) $endDate,
            'location_id' => (string) $locationId,
            'product_category_id' => (string) $request->input('product_category_id', ''),
            'product_sub_category_id' => (string) $request->input('product_sub_category_id', ''),
            'product_id' => (string) $request->input('product_id', ''),
            'search' => trim((string) $request->input('search.value', '')),
            'orders' => $orders,
            'currency_precision' => (int) $currencyPrecision,
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Keep report responses bounded. The page-size "All" option is deliberately
     * unsupported because it can freeze the browser and makes page carries
     * unnecessarily expensive on large forms.
     */
    private function normalizeF9CPageLength($requestedLength)
    {
        $requestedLength = (int) $requestedLength;
        $allowedLengths = [25, 50, 100];

        return in_array($requestedLength, $allowedLengths, true)
            ? $requestedLength
            : 25;
    }

    private function getF9CCurrencyPrecision($businessId)
    {
        $precision = $this->rememberMpcsValue(
            $this->getF9CCachePrefix($businessId) . ':currency-precision',
            300,
            function () use ($businessId) {
                return Business::where('id', $businessId)->value('currency_precision');
            }
        );

        $precision = is_numeric($precision) ? (int) $precision : 2;

        return max(0, min(6, $precision));
    }

    /**
     * Prepare one stable ordered result set and monetary prefix totals.
     * Prefix totals are integer minor units after each row is rounded to the
     * business currency precision. Therefore page N's Previous Page Total is
     * exactly the same displayed amount as page N-1's Grand Total.
     */
    private function buildF9CPreparedReport(
        $rows,
        Request $request,
        $businessId,
        $formType,
        $startDate,
        $endDate,
        $locationId,
        $currencyPrecision
    ) {
        $allRows = collect($rows)->map(function ($row) {
            return is_array($row) ? (object) $row : $row;
        })->values();

        $criteriaHash = $this->getF9CReportCriteriaHash(
            $request,
            $formType,
            $businessId,
            $startDate,
            $endDate,
            $locationId,
            $currencyPrecision
        );

        $suffix = 'prepared-report:' . (string) $formType . ':' . $criteriaHash;

        return $this->rememberF9CCalculation(
            $businessId,
            $suffix,
            function () use ($allRows, $request, $currencyPrecision, $criteriaHash) {
                $orderedRows = $this->prepareForm9CCollectionForFooter($allRows, $request)
                    ->values()
                    ->map(function ($row) {
                        return (array) $row;
                    })
                    ->all();

                $scale = 10 ** $currencyPrecision;
                $prefixMinorUnits = [0];
                $runningMinorUnits = 0;

                foreach ($orderedRows as $row) {
                    $rowMinorUnits = (int) round(
                        (float) ($row['final_total_rs'] ?? 0) * $scale,
                        0,
                        PHP_ROUND_HALF_UP
                    );
                    $runningMinorUnits += $rowMinorUnits;
                    $prefixMinorUnits[] = $runningMinorUnits;
                }

                return [
                    'criteria_hash' => $criteriaHash,
                    'rows' => $orderedRows,
                    'prefix_minor_units' => $prefixMinorUnits,
                    'records_total' => $allRows->count(),
                    'records_filtered' => count($orderedRows),
                    'currency_precision' => $currencyPrecision,
                    'scale' => $scale,
                ];
            },
            900
        );
    }

    /**
     * Calculate every page independently on the server. No accounting carry is
     * accepted from browser memory or a page snapshot. If report data changes
     * while the user is on a later page, the response instructs DataTables to
     * restart from page 1 rather than mixing two data versions.
     */
    private function paginateF9CCollection(
        $rows,
        Request $request,
        $businessId = null,
        $formType = null,
        $previousTotal = 0.0,
        $startDate = null,
        $endDate = null,
        $locationId = null
    ) {
        $currencyPrecision = $this->getF9CCurrencyPrecision($businessId);
        $reportVersion = $this->getF9CDataVersion($businessId);

        $prepared = $this->buildF9CPreparedReport(
            $rows,
            $request,
            $businessId,
            $formType,
            $startDate,
            $endDate,
            $locationId,
            $currencyPrecision
        );

        $preparedRows = collect($prepared['rows'])->values();
        $prefixMinorUnits = $prepared['prefix_minor_units'];
        $recordsTotal = (int) $prepared['records_total'];
        $recordsFiltered = (int) $prepared['records_filtered'];
        $criteriaHash = (string) $prepared['criteria_hash'];
        $scale = (int) ($prepared['scale'] ?? (10 ** $currencyPrecision));

        $requestedStart = max(0, (int) $request->input('start', 0));
        $length = $this->normalizeF9CPageLength($request->input('length', 25));
        $clientVersion = (string) $request->input('report_version', '');
        $clientCriteria = (string) $request->input('report_criteria', '');

        $versionChanged = $requestedStart > 0
            && $clientVersion !== ''
            && ! hash_equals($reportVersion, $clientVersion);
        $criteriaChanged = $requestedStart > 0
            && $clientCriteria !== ''
            && ! hash_equals($criteriaHash, $clientCriteria);
        $pageOutOfRange = $requestedStart > 0 && $requestedStart >= $recordsFiltered;
        $resetToFirstPage = $versionChanged || $criteriaChanged || $pageOutOfRange;

        $effectiveStart = $resetToFirstPage ? 0 : $requestedStart;
        $endIndex = min($effectiveStart + $length, $recordsFiltered);

        $pageRows = $preparedRows
            ->slice($effectiveStart, $length)
            ->map(function ($row) {
                $row = (array) $row;
                unset($row['_stable_id'], $row['_sort_date'], $row['_source_id']);

                return $row;
            })
            ->values();

        $basePreviousMinorUnits = (int) round(
            (float) $previousTotal * $scale,
            0,
            PHP_ROUND_HALF_UP
        );
        $amountBeforePageMinorUnits = (int) ($prefixMinorUnits[$effectiveStart] ?? 0);
        $amountThroughPageMinorUnits = (int) ($prefixMinorUnits[$endIndex] ?? $amountBeforePageMinorUnits);
        $pageTotalMinorUnits = $amountThroughPageMinorUnits - $amountBeforePageMinorUnits;
        $previousPageMinorUnits = $basePreviousMinorUnits + $amountBeforePageMinorUnits;
        $grandTotalMinorUnits = $basePreviousMinorUnits + $amountThroughPageMinorUnits;

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pageRows->all(),
            'page_index' => intdiv($effectiveStart, $length),
            'requested_start' => $requestedStart,
            'effective_start' => $effectiveStart,
            'page_length' => $length,
            'page_total' => $pageTotalMinorUnits / $scale,
            'amount_before_page' => $amountBeforePageMinorUnits / $scale,
            'base_previous_total' => $basePreviousMinorUnits / $scale,
            'previous_page_total' => $previousPageMinorUnits / $scale,
            'grand_total_for_page' => $grandTotalMinorUnits / $scale,
            'report_version' => $reportVersion,
            'report_criteria' => $criteriaHash,
            'reset_to_first_page' => $resetToFirstPage,
            'data_version_changed' => $versionChanged,
            'criteria_changed' => $criteriaChanged,
            'max_page_length' => 100,
        ];
    }

    /**
     * Apply the common F9C category, sub-category and product filters.
     */
    private function applyF9CProductFilters($query, Request $request, $productAlias = 'products')
    {
        if ($request->filled('product_category_id')) {
            $query->where($productAlias . '.category_id', $request->input('product_category_id'));
        }

        if ($request->filled('product_sub_category_id')) {
            $query->where($productAlias . '.sub_category_id', $request->input('product_sub_category_id'));
        }

        if ($request->filled('product_id')) {
            $query->where($productAlias . '.id', $request->input('product_id'));
        }

        return $query;
    }

    /**
     * Build the same product/sub-category Sales Income source that is shown in
     * Account Books. Each Sales Income account transaction is linked to its sell
     * line through account_transactions.sell_line_id, allowing the accounting
     * amount to be grouped by settlement and product without reconstructing it
     * from transaction line totals.
     */
    private function buildF9CSalesIncomeAccountQuery(
        $businessId,
        $fromDate,
        $toDate,
        $locationId,
        Request $request
    ) {
        $operationDateExpression = 'COALESCE(at.operation_date, t.transaction_date)';

        $query = DB::table('account_transactions as at')
            ->join('transactions as t', 't.id', '=', 'at.transaction_id')
            ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'at.sell_line_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('categories as sub_cat', 'sub_cat.id', '=', 'p.sub_category_id')
            ->leftJoin('categories as main_cat', 'main_cat.id', '=', 'p.category_id')
            ->leftJoin('settlements as s', 's.id', '=', 't.petro_settlement_id')
            ->where('at.business_id', $businessId)
            ->where('t.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->whereNull('at.transaction_payment_id')
            ->whereNotNull('at.sell_line_id')
            ->where('at.type', 'credit')
            ->whereIn('t.type', ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'])
            ->where(function ($settlementQuery) {
                $settlementQuery->where('t.sub_type', 'settlement')
                    ->orWhere('t.is_settlement', 1);
            })
            ->whereRaw(
                'at.account_id = COALESCE(' .
                'NULLIF(sub_cat.sales_income_account_id, 0), ' .
                'NULLIF(main_cat.sales_income_account_id, 0)' .
                ')'
            );

        if (! empty($fromDate) && ! empty($toDate)) {
            $query->whereBetween(DB::raw($operationDateExpression), [
                Carbon::parse($fromDate)->startOfDay()->format('Y-m-d H:i:s'),
                Carbon::parse($toDate)->endOfDay()->format('Y-m-d H:i:s'),
            ]);
        } elseif (! empty($toDate)) {
            $query->where(
                DB::raw($operationDateExpression),
                '<=',
                Carbon::parse($toDate)->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if (! empty($locationId)) {
            $query->where('t.location_id', $locationId);
        }

        $this->applyF9CProductFilters($query, $request, 'p');

        return $query;
    }

    /**
     * Build F9C Cash rows once per data version. Page changes then read the cached collection.
     */
    private function getF9CCashRows($businessId, $startDate, $endDate, $locationId, Request $request)
    {
        $suffix = 'cash-rows:v5-account-books:' . implode(':', [
            $startDate,
            $endDate,
            (string) $locationId,
            $this->getF9CFilterSignature($request),
        ]);

        $rows = $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $startDate,
            $endDate,
            $locationId,
            $request
        ) {
            /*
             | IS2174: credit sales must be deducted from the cash figure.
             |
             | The sale side keyed on petro_settlement_id, falling back to
             | 'transaction:{id}'. The credit side falls back to 'credit:{id}'.
             | Where a transaction carried no settlement - 56 of 105 in August -
             | the two keys could never meet, so nothing was subtracted and the
             | full sale showed as cash.
             |
             | credit_sale_id is the link both sides already share, so it goes
             | in between: a sale tied to a credit sale now keys the same way
             | the credit row does.
            */
            $saleSettlementKey = "COALESCE(CAST(t.petro_settlement_id AS CHAR), "
                . "CAST(sale_settlement.id AS CHAR), "
                . "IF(t.credit_sale_id IS NULL OR t.credit_sale_id = 0, NULL, CONCAT('credit:', t.credit_sale_id)), "
                . "CONCAT('transaction:', t.id))";
            $saleLineAmountExpression = 'COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0) * COALESCE(tsl.quantity, 0)';
            $accountBookDateExpression = 'COALESCE(at.operation_date, t.transaction_date)';

            /*
             * Use the Sales Income credit entries shown in Account Books as the
             * Total Sale Amount. The account transaction is already linked to the
             * exact product sell line and to the Sales Income account configured
             * for that product's sub-category.
             */
            $salesQuery = $this->buildF9CSalesIncomeAccountQuery(
                $businessId,
                $startDate,
                $endDate,
                $locationId,
                $request
            );

            // IS2174: every sell transaction carries its settlement in invoice_no.
            // Joining it lets the sale key resolve to the settlement id, which is
            // what the credit side keys on - so the deduction can match.
            $salesQuery->leftJoin('settlements as sale_settlement', 'sale_settlement.settlement_no', '=', 't.invoice_no');

            $sales = $salesQuery
                ->select(
                    DB::raw("{$saleSettlementKey} as settlement_key"),
                    'p.id as product_id',
                    DB::raw('MAX(p.name) as display_product_name'),
                    DB::raw('MAX(COALESCE(sub_cat.name, main_cat.name)) as display_category_name'),
                    DB::raw('MAX(COALESCE(s.settlement_no, t.invoice_no)) as bill_no'),
                    DB::raw("MAX({$accountBookDateExpression}) as activity_date"),
                    DB::raw('SUM(COALESCE(tsl.quantity, 0)) as total_qty'),
                    DB::raw("SUM({$saleLineAmountExpression}) as gross_line_amount"),
                    DB::raw('SUM(COALESCE(at.amount, 0)) as total_amount')
                )
                ->groupBy(DB::raw($saleSettlementKey), 'p.id')
                ->get()
                ->values();

            if ($sales->isEmpty()) {
                return [];
            }

            /*
             * Use the same amount priority as the F9C Credit form itself.  Some
             * historical rows keep the value only in the linked transaction's
             * final_total, so that fallback is essential for an exact subtraction.
             */
            $directLocationExpression = $this->getSettlementCreditLocationExpression('scsp', 'linked_t');
            $directAmountExpression = 'COALESCE(NULLIF(scsp.sub_total, 0), NULLIF(scsp.amount, 0), NULLIF(COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), 0), NULLIF(linked_t.final_total, 0), 0)';
            $directSettlementKey = "COALESCE(CAST(linked_t.petro_settlement_id AS CHAR), CAST(credit_s.id AS CHAR), CONCAT('credit:', scsp.id))";
            $directQuery = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions as linked_t', function ($join) {
                    $join->on('linked_t.id', '=', 'scsp.transaction_id')
                        ->orOn('linked_t.credit_sale_id', '=', 'scsp.id');
                })
                ->leftJoin('settlements as credit_s', function ($join) {
                    $join->on('credit_s.business_id', '=', 'scsp.business_id')
                        ->where(function ($settlementJoin) {
                            $settlementJoin->whereColumn('credit_s.id', 'scsp.settlement_no')
                                ->orWhereColumn('credit_s.settlement_no', 'scsp.settlement_no');
                        });
                })
                ->join('products as p', 'scsp.product_id', '=', 'p.id')
                ->where('scsp.business_id', $businessId);
            $this->applySettlementCreditDateRange($directQuery, 'scsp', 'linked_t', $startDate, $endDate);
            if (! empty($locationId)) {
                $directQuery->whereRaw($directLocationExpression . ' = ?', [$locationId]);
            }
            $this->applyF9CProductFilters($directQuery, $request, 'p');

            $directRows = $directQuery
                ->select(
                    'scsp.id',
                    'scsp.product_id',
                    DB::raw("{$directSettlementKey} as settlement_key"),
                    DB::raw("MAX({$directAmountExpression}) as row_credit_amount")
                )
                ->groupBy('scsp.id', 'scsp.product_id', DB::raw($directSettlementKey));

            $directCreditRows = DB::query()
                ->fromSub($directRows, 'direct_rows')
                ->select(
                    'settlement_key',
                    'product_id',
                    DB::raw('SUM(row_credit_amount) as total_credit_amount')
                )
                ->groupBy('settlement_key', 'product_id')
                ->get();
            $directCredits = $directCreditRows->keyBy(function ($row) {
                return (string) $row->settlement_key . '|' . (string) $row->product_id;
            });

            /* Standalone POS credit amount must follow the F9C Credit column formula. */
            $standaloneUnitPriceExpression = 'COALESCE(NULLIF(v.sell_price_inc_tax, 0), NULLIF(tsl.unit_price_inc_tax, 0), NULLIF(tsl.unit_price, 0), 0)';
            $standaloneAmountExpression = "COALESCE(NULLIF(COALESCE(tsl.quantity, 0) * {$standaloneUnitPriceExpression}, 0), NULLIF(tsl.line_total, 0), NULLIF(t.final_total, 0), 0)";
            $posCreditQuery = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
                // IS2181: $saleSettlementKey also reads sale_settlement.id.
                // Keep the standalone POS-credit query on the same settlement-key
                // path as the sales query, otherwise selecting a date causes an
                // SQL "unknown column sale_settlement.id" AJAX failure.
                ->leftJoin('settlements as sale_settlement', 'sale_settlement.settlement_no', '=', 't.invoice_no')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where(function ($query) {
                    $query->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereColumn('tp.transaction_id', 't.id')
                                ->whereIn('tp.method', ['credit', 'credit_sale'])
                                ->whereNull('tp.deleted_at');
                        });
                })
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('settlement_credit_sale_payments as existing_credit')
                        ->where('existing_credit.business_id', $businessId)
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('existing_credit.transaction_id', 't.id')
                                ->orWhereColumn('existing_credit.id', 't.credit_sale_id');
                        });
                });
            $this->applyTransactionDateRange($posCreditQuery, 't.transaction_date', $startDate, $endDate);
            if (! empty($locationId)) {
                $posCreditQuery->where('t.location_id', $locationId);
            }
            $this->applyF9CProductFilters($posCreditQuery, $request, 'p');

            $posCreditRows = $posCreditQuery
                ->select(
                    DB::raw("{$saleSettlementKey} as settlement_key"),
                    'tsl.product_id',
                    DB::raw("SUM({$standaloneAmountExpression}) as total_pos_credit_amount")
                )
                ->groupBy(DB::raw($saleSettlementKey), 'tsl.product_id')
                ->get();
            $posCredits = $posCreditRows->keyBy(function ($row) {
                return (string) $row->settlement_key . '|' . (string) $row->product_id;
            });

            /*
             * The requested rule is product based:
             *   Cash Amount = Total Sale Amount - F9C Credit Total Amount.
             * First use the settlement key when it is available.  Any historical
             * credit row whose settlement link is missing is then allocated once,
             * by product, across that product's separate settlement rows.  This
             * preserves separate settlements without losing the credit deduction.
             */
            $creditTotalsByProduct = [];
            foreach ($directCreditRows as $credit) {
                $productKey = (string) $credit->product_id;
                $creditTotalsByProduct[$productKey] = ($creditTotalsByProduct[$productKey] ?? 0.0)
                    + (float) ($credit->total_credit_amount ?? 0);
            }
            foreach ($posCreditRows as $credit) {
                $productKey = (string) $credit->product_id;
                $creditTotalsByProduct[$productKey] = ($creditTotalsByProduct[$productKey] ?? 0.0)
                    + (float) ($credit->total_pos_credit_amount ?? 0);
            }

            $matchedCreditsByProduct = [];
            $preparedSales = [];
            foreach ($sales as $sale) {
                $productKey = (string) $sale->product_id;
                $rowKey = (string) $sale->settlement_key . '|' . $productKey;
                $direct = $directCredits->get($rowKey);
                $standalone = $posCredits->get($rowKey);

                $exactCreditAmount = (float) ($direct->total_credit_amount ?? 0)
                    + (float) ($standalone->total_pos_credit_amount ?? 0);
                $totalSaleAmount = max(0.0, (float) ($sale->total_amount ?? 0));
                $totalSaleQty = max(0.0, (float) ($sale->total_qty ?? 0));
                $grossLineAmount = max(0.0, (float) ($sale->gross_line_amount ?? 0));
                $unitPrice = $totalSaleQty > 0
                    ? $grossLineAmount / $totalSaleQty
                    : 0.0;
                $allocatedExactCredit = min($totalSaleAmount, max(0.0, $exactCreditAmount));

                $matchedCreditsByProduct[$productKey] = ($matchedCreditsByProduct[$productKey] ?? 0.0)
                    + $allocatedExactCredit;
                $preparedSales[] = [
                    'sale' => $sale,
                    'product_key' => $productKey,
                    'unit_price' => $unitPrice,
                    'cash_amount' => max(0.0, $totalSaleAmount - $allocatedExactCredit),
                ];
            }

            $remainingCreditsByProduct = [];
            foreach ($creditTotalsByProduct as $productKey => $creditTotal) {
                $remainingCreditsByProduct[$productKey] = max(
                    0.0,
                    (float) $creditTotal - (float) ($matchedCreditsByProduct[$productKey] ?? 0.0)
                );
            }

            $result = [];
            foreach ($preparedSales as $prepared) {
                $sale = $prepared['sale'];
                $productKey = $prepared['product_key'];
                $unitPrice = (float) $prepared['unit_price'];
                $amount = (float) $prepared['cash_amount'];

                $unmatchedCredit = (float) ($remainingCreditsByProduct[$productKey] ?? 0.0);
                if ($unmatchedCredit > 0 && $amount > 0) {
                    $allocatedCredit = min($amount, $unmatchedCredit);
                    $amount = max(0.0, $amount - $allocatedCredit);
                    $remainingCreditsByProduct[$productKey] = max(0.0, $unmatchedCredit - $allocatedCredit);
                }

                if ($amount <= 0) {
                    continue;
                }

                /* QTY must be derived from the corrected cash amount and sale unit price. */
                $quantity = $unitPrice > 0 ? $amount / $unitPrice : 0.0;

                $result[] = [
                    'billno' => $sale->bill_no,
                    'product' => $sale->display_product_name,
                    'category_name' => $sale->display_category_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'page' => '',
                    'final_total_rs' => $amount,
                    'goods_rs' => 0.0,
                    'loading_rs' => 0.0,
                    'empty_rs' => 0.0,
                    'transport_rs' => 0.0,
                    'other_rs' => 0.0,
                    '_sort_date' => (string) ($sale->activity_date ?? $endDate),
                    '_source_id' => (int) $sale->product_id,
                    '_stable_id' => 'cash-settlement-' . (string) $sale->settlement_key . '-product-' . (string) $sale->product_id,
                ];
            }

            usort($result, function ($left, $right) {
                $dateComparison = strcmp((string) ($left['_sort_date'] ?? ''), (string) ($right['_sort_date'] ?? ''));
                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                $billComparison = strnatcasecmp((string) ($left['billno'] ?? ''), (string) ($right['billno'] ?? ''));
                if ($billComparison !== 0) {
                    return $billComparison;
                }

                return strnatcasecmp((string) ($left['product'] ?? ''), (string) ($right['product'] ?? ''));
            });

            return $result;
        });

        return collect($rows)->map(function ($row) {
            return (object) $row;
        })->values();
    }

    /**
     * Build F9C Credit rows once per data version. The two supported credit sources are
     * merged without invoking F14 as a second full report query.
     */
    private function getF9CCreditRows($businessId, $startDate, $endDate, $locationId, Request $request)
    {
        $suffix = 'credit-rows:' . implode(':', [
            $startDate,
            $endDate,
            (string) $locationId,
            $this->getF9CFilterSignature($request),
        ]);

        $rows = $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $startDate,
            $endDate,
            $locationId,
            $request
        ) {
            $f9aSetting = $this->rememberF9CCalculation(
                $businessId,
                'credit-row-f9a-setting',
                function () use ($businessId) {
                    return Mpcs9aFormSettings::where('business_id', $businessId)
                        ->orderBy('date')
                        ->first();
                }
            );
            $f9aOpeningDate = $f9aSetting && $f9aSetting->date
                ? Carbon::parse($f9aSetting->date)->startOfDay()
                : null;
            $f9aStartingNumber = $f9aSetting ? (int) $f9aSetting->starting_number : 1;

            $creditLocationExpression = $this->getSettlementCreditLocationExpression('scsp', 't');
            $directQuery = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions as t', 't.id', '=', 'scsp.transaction_id')
                ->leftJoin('products as p', 'scsp.product_id', '=', 'p.id')
                ->leftJoin('contacts as c', 'scsp.customer_id', '=', 'c.id')
                ->where('scsp.business_id', $businessId)
                ->select(
                    DB::raw("COALESCE(t.transaction_date, NULLIF(scsp.order_date, ''), scsp.created_at) as settlement_date"),
                    't.final_total',
                    'p.name as description',
                    DB::raw('COALESCE(scsp.qty, 0) as balance_qty'),
                    DB::raw('COALESCE(scsp.price, 0) as unit_price'),
                    DB::raw('COALESCE(NULLIF(scsp.sub_total, 0), NULLIF(scsp.amount, 0), NULLIF(COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), 0), NULLIF(t.final_total, 0), 0) as line_total'),
                    't.ref_no as our_ref',
                    't.invoice_no',
                    'scsp.settlement_no as settlement_no',
                    'scsp.id as source_id',
                    DB::raw('COALESCE(t.id, 0) as transaction_id'),
                    DB::raw("'direct' as source_type")
                );
            $this->applySettlementCreditDateRange($directQuery, 'scsp', 't', $startDate, $endDate);
            if (! empty($locationId)) {
                $directQuery->whereRaw($creditLocationExpression . ' = ?', [$locationId]);
            }
            $this->applyF9CProductFilters($directQuery, $request, 'p');

            $standaloneQuery = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
                ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where(function ($query) {
                    $query->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereColumn('tp.transaction_id', 't.id')
                                ->whereIn('tp.method', ['credit', 'credit_sale'])
                                ->whereNull('tp.deleted_at');
                        });
                })
                ->whereNotNull('tsl.product_id')
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('settlement_credit_sale_payments as existing_credit')
                        ->where('existing_credit.business_id', $businessId)
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('existing_credit.transaction_id', 't.id')
                                ->orWhereColumn('existing_credit.id', 't.credit_sale_id');
                        });
                })
                ->select(
                    't.transaction_date as settlement_date',
                    't.final_total',
                    'p.name as description',
                    'tsl.quantity as balance_qty',
                    DB::raw('COALESCE(NULLIF(v.sell_price_inc_tax, 0), NULLIF(tsl.unit_price_inc_tax, 0), NULLIF(tsl.unit_price, 0), 0) as unit_price'),
                    DB::raw('COALESCE(NULLIF(COALESCE(tsl.quantity, 0) * COALESCE(NULLIF(v.sell_price_inc_tax, 0), NULLIF(tsl.unit_price_inc_tax, 0), NULLIF(tsl.unit_price, 0), 0), 0), NULLIF(tsl.line_total, 0), NULLIF(t.final_total, 0), 0) as line_total'),
                    't.ref_no as our_ref',
                    't.invoice_no',
                    't.invoice_no as settlement_no',
                    'tsl.id as source_id',
                    't.id as transaction_id',
                    DB::raw("'standalone' as source_type")
                );
            $this->applyTransactionDateRange($standaloneQuery, 't.transaction_date', $startDate, $endDate);
            if (! empty($locationId)) {
                $standaloneQuery->where('t.location_id', $locationId);
            }
            $this->applyF9CProductFilters($standaloneQuery, $request, 'p');

            $sourceRows = $directQuery->get()
                ->concat($standaloneQuery->get())
                ->sort(function ($left, $right) {
                    $leftTimestamp = ! empty($left->settlement_date)
                        ? (strtotime((string) $left->settlement_date) ?: 0)
                        : 0;
                    $rightTimestamp = ! empty($right->settlement_date)
                        ? (strtotime((string) $right->settlement_date) ?: 0)
                        : 0;

                    if ($leftTimestamp !== $rightTimestamp) {
                        return $rightTimestamp <=> $leftTimestamp;
                    }

                    $sourceTypeComparison = strnatcasecmp(
                        (string) ($left->source_type ?? ''),
                        (string) ($right->source_type ?? '')
                    );
                    if ($sourceTypeComparison !== 0) {
                        return $sourceTypeComparison;
                    }

                    $transactionComparison = (int) ($left->transaction_id ?? 0)
                        <=> (int) ($right->transaction_id ?? 0);
                    if ($transactionComparison !== 0) {
                        return $transactionComparison;
                    }

                    return (int) ($left->source_id ?? 0)
                        <=> (int) ($right->source_id ?? 0);
                })
                ->values();

            $f14StartingNumber = (int) $this->rememberF9CCalculation(
                $businessId,
                'credit-row-f14-starting-number',
                function () use ($businessId) {
                    return MpcsFormSetting::where('business_id', $businessId)
                        ->value('F14_form_sn') ?? 1;
                }
            );
            $result = [];
            foreach ($sourceRows as $index => $sale) {
                $quantity = (float) ($sale->balance_qty ?? 0);
                $unitPrice = (float) ($sale->unit_price ?? 0);
                $amount = (float) ($sale->line_total ?? 0);
                if ($amount <= 0 && $quantity > 0 && $unitPrice > 0) {
                    $amount = $quantity * $unitPrice;
                }
                if ($amount <= 0) {
                    $amount = (float) ($sale->final_total ?? 0);
                }

                $page = '';
                if ($f9aOpeningDate && ! empty($sale->settlement_date)) {
                    $saleDate = Carbon::parse($sale->settlement_date)->startOfDay();
                    $days = $f9aOpeningDate->diffInDays($saleDate, false);
                    if ($days >= 0) {
                        $page = $f9aStartingNumber + $days;
                    }
                }

                $sourceType = (string) ($sale->source_type ?? 'credit');
                $sourceId = (int) ($sale->source_id ?? 0);
                $transactionId = (int) ($sale->transaction_id ?? 0);

                $result[] = [
                    'billno' => $f14StartingNumber + $index,
                    'ourref' => $sale->settlement_no ?? $sale->our_ref,
                    'product' => $sale->description ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice > 0 ? $unitPrice : ($quantity > 0 ? $amount / $quantity : 0),
                    'page' => $page,
                    'final_total_rs' => $amount,
                    'goods_rs' => 0.0,
                    'loading_rs' => 0.0,
                    'empty_rs' => 0.0,
                    'transport_rs' => 0.0,
                    'other_rs' => 0.0,
                    '_sort_date' => (string) ($sale->settlement_date ?? ''),
                    '_source_id' => $sourceId,
                    '_stable_id' => implode(':', [
                        'credit',
                        $sourceType,
                        (string) $transactionId,
                        (string) $sourceId,
                    ]),
                ];
            }

            return $result;
        });

        return collect($rows)->map(function ($row) {
            return (object) $row;
        })->values();
    }

    private function getF9CCashFormNumber($businessId, $settingStartDate, $startingNumber, $selectedDate)
    {
        if (empty($settingStartDate) || empty($startingNumber)) {
            return 0;
        }

        $suffix = 'cash-form-number:' . $settingStartDate . ':' . $selectedDate . ':' . $startingNumber;
        return (int) $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $settingStartDate,
            $startingNumber,
            $selectedDate
        ) {
            $query = DB::table('transactions as t')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where('t.is_credit_sale', 0)
                ->whereNotIn('t.payment_status', ['due', 'partial'])
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('transaction_payments as tp')
                        ->whereColumn('tp.transaction_id', 't.id')
                        ->whereIn('tp.method', ['credit', 'credit_sale'])
                        ->whereNull('tp.deleted_at');
                });
            $this->applyTransactionDateRange($query, 't.transaction_date', $settingStartDate, $selectedDate);

            $count = (int) ($query
                ->selectRaw('COUNT(DISTINCT DATE(t.transaction_date)) as date_count')
                ->value('date_count') ?? 0);

            return (int) $startingNumber + max(0, $count - 1);
        });
    }

    private function getF9CCreditFormNumber($businessId, $settingStartDate, $startingNumber, $selectedDate)
    {
        if (empty($settingStartDate) || empty($startingNumber)) {
            return 0;
        }

        $previousDate = Carbon::parse($selectedDate)->subDay()->format('Y-m-d');
        if (Carbon::parse($previousDate)->lt(Carbon::parse($settingStartDate))) {
            return (int) $startingNumber;
        }

        $suffix = 'credit-form-number:' . $settingStartDate . ':' . $selectedDate . ':' . $startingNumber;
        return (int) $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $settingStartDate,
            $startingNumber,
            $previousDate
        ) {
            $directQuery = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions as t', 't.id', '=', 'scsp.transaction_id')
                ->where('scsp.business_id', $businessId)
                ->selectRaw("DATE(COALESCE(t.transaction_date, NULLIF(scsp.order_date, ''), scsp.created_at)) as sale_date");
            $this->applySettlementCreditDateRange($directQuery, 'scsp', 't', $settingStartDate, $previousDate);

            $standaloneQuery = DB::table('transactions as t')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where(function ($query) {
                    $query->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereColumn('tp.transaction_id', 't.id')
                                ->whereIn('tp.method', ['credit', 'credit_sale'])
                                ->whereNull('tp.deleted_at');
                        });
                })
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('settlement_credit_sale_payments as existing_credit')
                        ->where('existing_credit.business_id', $businessId)
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('existing_credit.transaction_id', 't.id')
                                ->orWhereColumn('existing_credit.id', 't.credit_sale_id');
                        });
                })
                ->selectRaw('DATE(t.transaction_date) as sale_date');
            $this->applyTransactionDateRange($standaloneQuery, 't.transaction_date', $settingStartDate, $previousDate);

            $union = $directQuery->unionAll($standaloneQuery);
            $count = (int) DB::query()
                ->fromSub($union, 'credit_dates')
                ->distinct()
                ->count('sale_date');

            return (int) $startingNumber + $count;
        });
    }


    /**
     * Total cash value for a period, using exactly the F9C Cash rule:
     * total sale value less direct-settlement and standalone POS credit value.
     *
     * The lower date is optional.  This is important for tenants that already
     * have F9C activity but have not created an F9C settings record yet: their
     * prior-form Grand Total must still carry into the selected date.
     *
     * Cash is calculated per DAY + PRODUCT before it is accumulated.  That is
     * the same rule used by each individual F9C form and avoids a period-wide
     * credit adjustment changing a previously completed day's Grand Total.
     */
    private function getF9CCashPeriodTotal($businessId, $fromDate, $toDate, $locationId, Request $request)
    {
        if (empty($toDate)) {
            return 0.0;
        }

        $toDate = Carbon::parse($toDate)->format('Y-m-d');
        $fromDate = ! empty($fromDate) ? Carbon::parse($fromDate)->format('Y-m-d') : null;
        if ($fromDate !== null && Carbon::parse($fromDate)->gt(Carbon::parse($toDate))) {
            return 0.0;
        }

        $suffix = 'cash-period-total:v4-account-books:' . implode(':', [
            (string) $fromDate,
            $toDate,
            (string) $locationId,
            $this->getF9CFilterSignature($request),
        ]);

        return (float) $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $fromDate,
            $toDate,
            $locationId,
            $request
        ) {
            $accountBookDateExpression = 'COALESCE(at.operation_date, t.transaction_date)';

            /*
             * Previous-day and carried totals must use the same Sales Income
             * account-book source as the visible F9C Cash rows.
             */
            $sales = $this->buildF9CSalesIncomeAccountQuery(
                $businessId,
                $fromDate,
                $toDate,
                $locationId,
                $request
            );
            $sales->select(
                DB::raw("DATE({$accountBookDateExpression}) as activity_date"),
                'tsl.product_id',
                DB::raw('SUM(COALESCE(at.amount, 0)) as total_amount')
            )->groupBy(DB::raw("DATE({$accountBookDateExpression})"), 'tsl.product_id');

            $directDateExpression = "DATE(COALESCE(linked_t.transaction_date, NULLIF(scsp.order_date, ''), scsp.created_at))";
            $directAmountExpression = 'COALESCE(NULLIF(scsp.sub_total, 0), NULLIF(scsp.amount, 0), NULLIF(COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), 0), NULLIF(linked_t.final_total, 0), 0)';
            $directLocationExpression = $this->getSettlementCreditLocationExpression('scsp', 'linked_t');
            $directRows = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions as linked_t', function ($join) {
                    $join->on('linked_t.id', '=', 'scsp.transaction_id')
                        ->orOn('linked_t.credit_sale_id', '=', 'scsp.id');
                })
                ->join('products as p', 'scsp.product_id', '=', 'p.id')
                ->where('scsp.business_id', $businessId);
            $this->applySettlementCreditDateRange($directRows, 'scsp', 'linked_t', $fromDate, $toDate);
            if (! empty($locationId)) {
                $directRows->whereRaw($directLocationExpression . ' = ?', [$locationId]);
            }
            $this->applyF9CProductFilters($directRows, $request, 'p');
            $directRows->select(
                'scsp.id',
                'scsp.product_id',
                DB::raw("{$directDateExpression} as activity_date"),
                DB::raw("MAX({$directAmountExpression}) as row_amount")
            )->groupBy('scsp.id', 'scsp.product_id', DB::raw($directDateExpression));

            $directCredits = DB::query()
                ->fromSub($directRows, 'direct_rows')
                ->select(
                    'activity_date',
                    'product_id',
                    DB::raw('SUM(row_amount) as total_credit_amount')
                )
                ->groupBy('activity_date', 'product_id');

            $standaloneUnitPriceExpression = 'COALESCE(NULLIF(v.sell_price_inc_tax, 0), NULLIF(tsl.unit_price_inc_tax, 0), NULLIF(tsl.unit_price, 0), 0)';
            $standaloneAmountExpression = "COALESCE(NULLIF(COALESCE(tsl.quantity, 0) * {$standaloneUnitPriceExpression}, 0), NULLIF(tsl.line_total, 0), NULLIF(t.final_total, 0), 0)";
            $posCredits = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where(function ($query) {
                    $query->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereColumn('tp.transaction_id', 't.id')
                                ->whereIn('tp.method', ['credit', 'credit_sale'])
                                ->whereNull('tp.deleted_at');
                        });
                })
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('settlement_credit_sale_payments as existing_credit')
                        ->where('existing_credit.business_id', $businessId)
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('existing_credit.transaction_id', 't.id')
                                ->orWhereColumn('existing_credit.id', 't.credit_sale_id');
                        });
                });
            $this->applyTransactionDateRange($posCredits, 't.transaction_date', $fromDate, $toDate);
            if (! empty($locationId)) {
                $posCredits->where('t.location_id', $locationId);
            }
            $this->applyF9CProductFilters($posCredits, $request, 'p');
            $posCredits->select(
                DB::raw('DATE(t.transaction_date) as activity_date'),
                'tsl.product_id',
                DB::raw("SUM({$standaloneAmountExpression}) as total_pos_credit_amount")
            )->groupBy(DB::raw('DATE(t.transaction_date)'), 'tsl.product_id');

            $total = DB::query()
                ->fromSub($sales, 'sales')
                ->leftJoinSub($directCredits, 'direct_credit', function ($join) {
                    $join->on('direct_credit.activity_date', '=', 'sales.activity_date')
                        ->on('direct_credit.product_id', '=', 'sales.product_id');
                })
                ->leftJoinSub($posCredits, 'pos_credit', function ($join) {
                    $join->on('pos_credit.activity_date', '=', 'sales.activity_date')
                        ->on('pos_credit.product_id', '=', 'sales.product_id');
                })
                ->selectRaw('COALESCE(SUM(GREATEST(0, sales.total_amount - COALESCE(direct_credit.total_credit_amount, 0) - COALESCE(pos_credit.total_pos_credit_amount, 0))), 0) as total')
                ->value('total');

            return (float) ($total ?? 0);
        });
    }

    private function getF9CCreditPeriodTotal($businessId, $fromDate, $toDate, $locationId, Request $request)
    {
        if (empty($toDate)) {
            return 0.0;
        }

        $toDate = Carbon::parse($toDate)->format('Y-m-d');
        $fromDate = ! empty($fromDate) ? Carbon::parse($fromDate)->format('Y-m-d') : null;
        if ($fromDate !== null && Carbon::parse($fromDate)->gt(Carbon::parse($toDate))) {
            return 0.0;
        }

        $suffix = 'credit-period-total:' . implode(':', [
            (string) $fromDate,
            $toDate,
            (string) $locationId,
            $this->getF9CFilterSignature($request),
        ]);

        return (float) $this->rememberF9CCalculation($businessId, $suffix, function () use (
            $businessId,
            $fromDate,
            $toDate,
            $locationId,
            $request
        ) {
            $directAmountExpression = 'COALESCE(NULLIF(scsp.sub_total, 0), NULLIF(scsp.amount, 0), COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), 0)';
            $directLocationExpression = $this->getSettlementCreditLocationExpression('scsp', 't');
            $directRows = DB::table('settlement_credit_sale_payments as scsp')
                ->leftJoin('transactions as t', function ($join) {
                    $join->on('t.id', '=', 'scsp.transaction_id')
                        ->orOn('t.credit_sale_id', '=', 'scsp.id');
                })
                ->join('products as p', 'scsp.product_id', '=', 'p.id')
                ->where('scsp.business_id', $businessId);
            $this->applySettlementCreditDateRange($directRows, 'scsp', 't', $fromDate, $toDate);
            if (! empty($locationId)) {
                $directRows->whereRaw($directLocationExpression . ' = ?', [$locationId]);
            }
            $this->applyF9CProductFilters($directRows, $request, 'p');
            $directRows->select(
                'scsp.id',
                DB::raw("MAX({$directAmountExpression}) as row_amount")
            )->groupBy('scsp.id');

            $directTotal = (float) (DB::query()
                ->fromSub($directRows, 'direct_rows')
                ->sum('row_amount') ?? 0);

            $standaloneAmountExpression = 'COALESCE(NULLIF(COALESCE(tsl.quantity, 0) * COALESCE(NULLIF(v.sell_price_inc_tax, 0), NULLIF(tsl.unit_price_inc_tax, 0), NULLIF(tsl.unit_price, 0), 0), 0), NULLIF(tsl.line_total, 0), 0)';
            $standalone = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
                ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where(function ($query) {
                    $query->where('t.is_credit_sale', 1)
                        ->orWhereIn('t.payment_status', ['due', 'partial'])
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereColumn('tp.transaction_id', 't.id')
                                ->whereIn('tp.method', ['credit', 'credit_sale'])
                                ->whereNull('tp.deleted_at');
                        });
                })
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('settlement_credit_sale_payments as existing_credit')
                        ->where('existing_credit.business_id', $businessId)
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('existing_credit.transaction_id', 't.id')
                                ->orWhereColumn('existing_credit.id', 't.credit_sale_id');
                        });
                });
            $this->applyTransactionDateRange($standalone, 't.transaction_date', $fromDate, $toDate);
            if (! empty($locationId)) {
                $standalone->where('t.location_id', $locationId);
            }
            $this->applyF9CProductFilters($standalone, $request, 'p');

            $standaloneTotal = (float) ($standalone->sum(DB::raw($standaloneAmountExpression)) ?? 0);

            return $directTotal + $standaloneTotal;
        });
    }

    ///get 9 c cash
    public function get9CCashForm(Request $request)
    {
        try {
            $businessId = $request->session()->get('user.business_id')
                ?? $request->session()->get('business.id');
            if (empty($businessId)) {
                return response()->json(['error' => 'Business context was not found.'], 422);
            }

            $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
            $endDate = Carbon::parse($request->input('end_date', $startDate))->format('Y-m-d');
            $locationId = $request->filled('location_id') ? $request->input('location_id') : null;

            $setting = $this->getEffectiveF9CSetting(
                Mpcs9cCashFormSettings::class,
                $businessId,
                $startDate
            );
            $settingStartDate = $setting && ! empty($setting->date_time)
                ? Carbon::parse($setting->date_time)->format('Y-m-d')
                : null;
            $startingNumber = $setting ? (int) $setting->starting_number : 0;

            $rows = $this->getF9CCashRows($businessId, $startDate, $endDate, $locationId, $request);
            $selectedDate = Carbon::parse($startDate);
            $monthStart = $selectedDate->copy()->startOfMonth()->format('Y-m-d');
            $previousDay = $selectedDate->copy()->subDay()->format('Y-m-d');

            /*
             * IS1726: F9C carries are month-bounded.  On the first calendar day
             * of a month Total Previous Day is always zero.  From the second day
             * onward, carry only activity from the first day of the selected
             * month (or a later in-month setting opening date).
             */
            $carryStartDate = $monthStart;
            $openingCarry = 0.0;
            if (
                $setting
                && $settingStartDate
                && $settingStartDate >= $monthStart
                && $settingStartDate <= $startDate
            ) {
                $carryStartDate = $settingStartDate;
                $openingCarry = (float) ($setting->ref_pre_form_number ?? 0);
            }

            $previousTotal = $selectedDate->isSameDay($selectedDate->copy()->startOfMonth())
                ? 0.0
                : $openingCarry + $this->getF9CCashPeriodTotal(
                    $businessId,
                    $carryStartDate,
                    $previousDay,
                    $locationId,
                    $request
                );

            $formNumber = 0;
            $customMessage = null;
            if (! $setting) {
                $customMessage = 'Please set the Form 9C Cash Setting for the form number. Previous totals are carried automatically.';
            } elseif ($settingStartDate > $startDate) {
                $customMessage = 'Please select a date on or after the Form 9C Cash Setting opening date.';
            } else {
                $formNumber = $this->getF9CCashFormNumber(
                    $businessId,
                    $settingStartDate,
                    $startingNumber,
                    $startDate
                );
            }

            $page = $this->paginateF9CCollection(
                $rows,
                $request,
                $businessId,
                'cash',
                $previousTotal,
                $startDate,
                $endDate,
                $locationId
            );
            $previousTotal = (float) $page['base_previous_total'];
            $previousPageTotal = (float) $page['previous_page_total'];
            $grandTotal = (float) $page['grand_total_for_page'];

            return response()->json([
                'draw' => $page['draw'],
                'recordsTotal' => $page['recordsTotal'],
                'recordsFiltered' => $page['recordsFiltered'],
                'data' => $page['data'],
                'previous_total' => ['rs' => (float) $previousTotal],
                'page_index' => $page['page_index'],
                'page_total' => (float) $page['page_total'],
                'total_previous_day' => (float) $previousTotal,
                'previous_page_total' => $previousPageTotal,
                'total_previous_page' => $previousPageTotal,
                'grand_total_for_page' => $grandTotal,
                'report_version' => $page['report_version'],
                'report_criteria' => $page['report_criteria'],
                'reset_to_first_page' => $page['reset_to_first_page'],
                'data_version_changed' => $page['data_version_changed'],
                'criteria_changed' => $page['criteria_changed'],
                'page_length' => $page['page_length'],
                'max_page_length' => $page['max_page_length'],
                'total_for_selected_date' => (float) $rows->sum('final_total_rs'),
                'carry_start_date' => $carryStartDate,
                'opening_carry' => $openingCarry,
                'form_9c_no' => $formNumber,
                'custom_message' => $customMessage,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error in get9CCashForm', [
                'message' => $exception->getMessage(),
                'request_data' => $request->except(['columns']),
            ]);

            return response()->json([
                'error' => 'Unable to load F9C Cash data. Please check the application log.',
            ], 500);
        }
    }

    ///get 9 c credit
    public function get9CCreditForm(Request $request)
    {
        try {
            $businessId = $request->session()->get('user.business_id')
                ?? $request->session()->get('business.id');
            if (empty($businessId)) {
                return response()->json(['error' => 'Business context was not found.'], 422);
            }

            $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
            $endDate = Carbon::parse($request->input('end_date', $startDate))->format('Y-m-d');
            $locationId = $request->filled('location_id') ? $request->input('location_id') : null;

            $setting = $this->getEffectiveF9CSetting(
                Mpcs9cCreditFormSettings::class,
                $businessId,
                $startDate
            );
            $settingStartDate = $setting && ! empty($setting->date_time)
                ? Carbon::parse($setting->date_time)->format('Y-m-d')
                : null;
            $startingNumber = $setting ? (int) $setting->starting_number : 0;

            $dateIsValid = ! $settingStartDate || $settingStartDate <= $startDate;
            $rows = $dateIsValid
                ? $this->getF9CCreditRows($businessId, $startDate, $endDate, $locationId, $request)
                : collect();

            $selectedDate = Carbon::parse($startDate);
            $monthStart = $selectedDate->copy()->startOfMonth()->format('Y-m-d');
            $previousDay = $selectedDate->copy()->subDay()->format('Y-m-d');

            /*
             * IS1726: F9C Credit uses the same month boundary as F9C Cash.
             * The first day starts with zero; later days carry only the
             * selected month's prior credit forms.
             */
            $carryStartDate = $monthStart;
            $openingCarry = 0.0;
            if (
                $setting
                && $settingStartDate
                && $settingStartDate >= $monthStart
                && $settingStartDate <= $startDate
            ) {
                $carryStartDate = $settingStartDate;
                $openingCarry = (float) ($setting->ref_pre_form_number ?? 0);
            }

            $previousTotal = $selectedDate->isSameDay($selectedDate->copy()->startOfMonth())
                ? 0.0
                : $openingCarry + $this->getF9CCreditPeriodTotal(
                    $businessId,
                    $carryStartDate,
                    $previousDay,
                    $locationId,
                    $request
                );

            $formNumber = 0;
            $customMessage = null;
            if ($setting && ! $dateIsValid) {
                $customMessage = 'Please select a date on or after the Form 9C Credit Setting opening date.';
            } elseif ($setting) {
                $formNumber = $this->getF9CCreditFormNumber(
                    $businessId,
                    $settingStartDate,
                    $startingNumber,
                    $startDate
                );
            }

            $page = $this->paginateF9CCollection(
                $rows,
                $request,
                $businessId,
                'credit',
                $previousTotal,
                $startDate,
                $endDate,
                $locationId
            );
            $previousTotal = (float) $page['base_previous_total'];
            $previousPageTotal = (float) $page['previous_page_total'];
            $grandTotal = (float) $page['grand_total_for_page'];

            return response()->json([
                'draw' => $page['draw'],
                'recordsTotal' => $page['recordsTotal'],
                'recordsFiltered' => $page['recordsFiltered'],
                'data' => $page['data'],
                'previous_total' => ['rs' => (float) $previousTotal],
                'page_index' => $page['page_index'],
                'page_total' => (float) $page['page_total'],
                'total_previous_day' => (float) $previousTotal,
                'previous_page_total' => $previousPageTotal,
                'total_previous_page' => $previousPageTotal,
                'grand_total_for_page' => $grandTotal,
                'report_version' => $page['report_version'],
                'report_criteria' => $page['report_criteria'],
                'reset_to_first_page' => $page['reset_to_first_page'],
                'data_version_changed' => $page['data_version_changed'],
                'criteria_changed' => $page['criteria_changed'],
                'page_length' => $page['page_length'],
                'max_page_length' => $page['max_page_length'],
                'carry_start_date' => $carryStartDate,
                'opening_carry' => $openingCarry,
                'form_9ccr_no' => $formNumber,
                'custom_message' => $customMessage,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error in get9CCreditForm', [
                'message' => $exception->getMessage(),
                'request_data' => $request->except(['columns']),
            ]);

            return response()->json([
                'error' => 'Unable to load F9C Credit data. Please check the application log.',
            ], 500);
        }
    }


    public function F16AQuery($business_id, $start_date, $end_date, $location_id, $product_id = null)
    {

        $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->Join('business_locations AS BS', 'transactions.location_id', '=', 'BS.id')
            ->leftJoin('transaction_payments AS TP', 'transactions.id', '=', 'TP.transaction_id')
            ->leftJoin('transactions AS PR', 'transactions.id', '=', 'PR.return_parent_id')
            ->leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftjoin('variation_prices', 'products.id', 'variation_prices.product_id')
            // Link to saved F16 forms so we can use their dates when present
            ->leftJoin('form_f16_details', 'transactions.id', '=', 'form_f16_details.transaction_id')
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
        // Include normal purchase receipts OR any transaction that has a saved F16 form
        ->where(function ($q) use ($business_id) {
            $q->where('transactions.business_id', $business_id);
            $q->where(function ($qq) {
                $qq->where('transactions.type', 'purchase')
                   ->where('transactions.status', 'received');
            });
        })
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.ref_no as reference_no',
                'purchase_lines.quantity as received_qty',
                'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                'transactions.final_total as total_purchase_price',
                'BS.name as location',
                'BS.name as location_name',
                'products.name as product',
                'products.id as product_id',
                'variation_prices.default_sell_price',
                'variation_prices.sell_price_inc_tax',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'PR.id as return_transaction_id',
                DB::raw('SUM(TP.amount) as amount_paid'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=PR.id ) as return_paid'),
                DB::raw('COUNT(PR.id) as return_exists'),
                DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                'transactions.invoice_no',
            )
            ->groupBy('transactions.id');

        // Location filter from UI (business location dropdown)
        if (! empty($location_id)) {
            $purchases->where('transactions.location_id', $location_id);
        }

        // Add product filter
        if (!empty($product_id)) {
            $purchases->where('products.id', $product_id);
        }

        if (! empty($start_date)) {
            // Normalize incoming date (supports m/d/Y or Y-m-d)
            if (Carbon::hasFormat($start_date, 'm/d/Y')) {
                $start_date = Carbon::createFromFormat('m/d/Y', $start_date)->format('Y-m-d');
            }

            // Show a transaction on a given day if EITHER:
            // - its original purchase transaction_date is that day, OR
            // - it has an F16 form whose created_at is that day.
            $purchases->where(function ($q) use ($start_date) {
                $q->whereDate('transactions.transaction_date', $start_date)
                  ->orWhereDate('form_f16_details.created_at', $start_date);
            });
        }
            $purchases->orderBy('id', 'DESC')
            ->with([
                'contact',
                'purchase_lines',
                'purchase_lines.product',
                'purchase_lines.product.unit',
                'purchase_lines.variations',
                'purchase_lines.variations.product_variation',
                'purchase_lines.variation',
                'purchase_lines.variation.product_variation',
                'purchase_lines.sub_unit',
                'location',
                'purchase_lines.product.category',
            ]);

        return $purchases;
    }

    public function getPreviousValue16AForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        $start_date  = Carbon::parse($request->start_date)->format('Y-m-d');
        $location_id = $request->location_id;

        $settings      = Mpcs16aFormSettings::where('business_id', $business_id)->first();
        // Use F22 form_date (stock-taking date) instead of created_at when
        // deciding whether to reset previous values.
        $formF22Exists = FormF22Header::whereDate('form_date', $start_date)->exists();

        // Default response
        $pre_total_purchase_price = 0;
        $pre_total_sale_price     = 0;

        // No settings → no opening
        if (empty($settings)) {
            return [
                'pre_total_purchase_price' => 0,
                'pre_total_sale_price'     => 0,
            ];
        }

        $openingDate            = Carbon::parse($settings->date)->format('Y-m-d');
        $opening_purchase_total = (float) ($settings->total_purchase_price_with_vat ?? 0);
        $opening_sale_total     = (float) ($settings->total_sale_price_with_vat ?? 0);

        // Before opening date → zero
        if ($start_date < $openingDate) {
            return [
                'pre_total_purchase_price' => 0,
                'pre_total_sale_price'     => 0,
            ];
        }

        // Opening date itself → opening balances are the "previous"
        if ($start_date === $openingDate) {
            return [
                'pre_total_purchase_price' => $opening_purchase_total,
                'pre_total_sale_price'     => $opening_sale_total,
            ];
        }

        // F22 exists → new period starts
        if ($formF22Exists) {
            return [
                'pre_total_purchase_price' => 0,
                'pre_total_sale_price'     => 0,
            ];
        }

        // Previous day (D - 1)
        $previous_end_date = Carbon::parse($start_date)->subDay()->format('Y-m-d');

        // Cumulative from opening → previous day
        $query = Transaction::join('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->leftJoin('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereDate('transactions.transaction_date', '>=', $openingDate)
            ->whereDate('transactions.transaction_date', '<=', $previous_end_date);

        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $totals = $query->select(
            DB::raw('SUM(purchase_lines.purchase_price_inc_tax * purchase_lines.quantity) as total_purchase'),
            DB::raw('SUM(
            COALESCE(
                purchase_lines.sell_price_at_purchase,
                variations.sell_price_inc_tax,
                variations.default_sell_price,
                0
            ) * purchase_lines.quantity
        ) as total_sale')
        )->first();

        $cumulative_purchase = (float) ($totals->total_purchase ?? 0);
        $cumulative_sale     = (float) ($totals->total_sale ?? 0);

        // Opening + cumulative
        $pre_total_purchase_price = $opening_purchase_total + $cumulative_purchase;
        $pre_total_sale_price     = $opening_sale_total + $cumulative_sale;

        return [
            'pre_total_purchase_price' => $pre_total_purchase_price,
            'pre_total_sale_price'     => $pre_total_sale_price,
        ];
    }

    public function F14(Request $request)
    {
        // Keep backward compatibility if any old link still reaches MPCSController@F14.
        // The F14 view requires credit_sales and filter variables, so delegate to the proper controller.
        return app(F14FormController::class)->index($request);
    }

    public function F159ABC()
    {
        $business_id = request()->session()->get('business.id');
        $request = request(); // Get the request instance

        if (request()->ajax()) {
            $start_date  = $request->start_date;
            $end_date    = $request->end_date;
            $location_id = $request->location_id;

            $settings            = MpcsFormSetting::where('business_id', $business_id)->first();
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date   = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate           = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate             = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales          = $this->F159ABCQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->F159ABCQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details        = FormF22Detail::where('business_id', $business_id)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

            return [
                'credit_sales'          => $credit_sales,
                'previous_credit_sales' => $previous_credit_sales,
                'form22_details'        => $form22_details,
            ];
        }
    }

    public function F159ABCQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select('transactions.transaction_date', 'transactions.final_total as opening_balance', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $credit_sales = $query->get();

        return $credit_sales;
    }

    public function F21()
    {
        $layout = 'layouts.app';
        return view('mpcs::forms.F21_form')->with(compact('layout'));
    }

    public function get21CForm(Request $request)
    {
        $business_id           = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations    = BusinessLocation::forDropdown($business_id);
        //  dd($business_locations);
        if (request()->ajax()) {
            $start_date  = $request->start_date;
            $end_date    = $request->end_date;
            $location_id = $request->location_id;

            $settings            = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate     = $settings->F21C_form_tdate;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date   = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate           = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate             = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales          = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details        = FormF22Detail::where('business_id', $business_id)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();
            $form17_increase       = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

            $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $previous_start_date)->orderBy('id', 'DESC')->first();

            $form17_decrease = FormF17Detail::where('select_mode', 'decrease')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

            $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
                ->whereDate('created_at', '>=', $previous_start_date)
            // ->whereDate('created_at', '<=', $previous_end_date)
                ->orderBy('id', 'DESC')
                ->first();
            $form17_decrease_previous_day = FormF17Detail::where('select_mode', 'decrease')
                ->whereDate('created_at', '>=', Carbon::now())
            // ->whereDate('created_at', '<=', $previous_end_date)
                ->orderBy('id', 'DESC')
                ->first();

            $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'cash')->orWhere('transaction_payments.method', 'cheque')->orWhere('transaction_payments.method', 'card')->orderBy('id', 'DESC')->first();

            $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')
                ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
            // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                ->orWhere('transaction_payments.method', 'cash')
                ->orWhere('transaction_payments.method', 'cheque')
                ->orWhere('transaction_payments.method', 'card')
                ->orderBy('id', 'DESC')
                ->first();

            $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

            $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

            $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();

            $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();
            $F21c_from_no                      = '';
            $sub_categories                    = $merged_sub_categories;
            $is_ajax                           = 1;
            $layout                            = 'layouts.empty';
            return view('mpcs::forms.F21_form')->with(
                compact(
                    'credit_sales',
                    'previous_credit_sales',
                    'form22_details',
                    'form17_increase',
                    'form17_decrease',
                    'transaction',
                    'own_group',
                    'credit_sales_transaction',
                    'previous_transaction',
                    'previous_own_group',
                    'previous_credit_sales_transaction',
                    'form17_increase_previous',
                    'form17_decrease_previous',
                    'merged_sub_categories',
                    'business_locations',
                    // 'F9C_sn',
                    // 'F16a_from_no',
                    'F21c_from_no',
                    // 'F15a9ab_from_no',
                    'sub_categories',
                    // 'setting'
                    'is_ajax',
                    'layout',
                ),
            );
        }
    }

    public function get_21_c_form_all_query(Request $request)
    {
        $business_id           = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();

        $start_date  = $request->start_date;
        $end_date    = $request->end_date;
        $location_id = $request->location_id;

        $settings            = MpcsFormSetting::where('business_id', $business_id)->first();
        $F21C_form_tdate     = $settings->F21C_form_tdate;
        $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $previous_end_date   = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
        $startDate           = Carbon::createFromFormat('Y-m-d', $start_date);
        $endDate             = Carbon::createFromFormat('Y-m-d', $end_date);

        $credit_sales          = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
        $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
        $form22_details        = FormF22Detail::where('business_id', $business_id)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();
        $form17_increase       = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

        $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $previous_start_date)->orderBy('id', 'DESC')->first();

        $form17_decrease = FormF17Detail::where('select_mode', 'decrease')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

        $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
            ->whereDate('created_at', '>=', $previous_start_date)
        // ->whereDate('created_at', '<=', $previous_end_date)
            ->orderBy('id', 'DESC')
            ->first();

        $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'cash')->orWhere('transaction_payments.method', 'cheque')->orWhere('transaction_payments.method', 'card')->orderBy('id', 'DESC')->first();

        $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
            ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')
            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
        // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
            ->orWhere('transaction_payments.method', 'cash')
            ->orWhere('transaction_payments.method', 'cheque')
            ->orWhere('transaction_payments.method', 'card')
            ->orderBy('id', 'DESC')
            ->first();

        $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

        $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

        $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();

        $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();

        $account_transactions = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->where('account_transactions.business_id', $business_id)->get();

        $opening_stock = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')
        //->whereDate('transactions.transaction_date', '>=', $start_date)
        //->whereDate('transactions.transaction_date', '<=', $end_date)
            ->where('account_transactions.business_id', $business_id)
            ->where('transactions.type', 'opening_stock')
            ->where('transactions.status', 'final')
            ->sum('account_transactions.amount');
        // ->get();

        $today = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')->whereDate('transactions.transaction_date', '=', Carbon::now())->where('account_transactions.business_id', $business_id)->sum('account_transactions.amount');
        // ->get();
        $previous_day = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')
            ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
            ->where('account_transactions.business_id', $business_id)
            ->sum('account_transactions.amount');
        // ->get();
        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
        $cash_sales_today   = AccountTransaction::whereDate('account_transactions.operation_date', '=', Carbon::now())->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')->where('account_transactions.business_id', $business_id)->where('account_transactions.type', 'debit')->get()->sum('amount');
        $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date', '=', Carbon::now())->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')->where('account_transactions.business_id', $business_id)->where('account_transactions.type', 'credit')->get()->sum('amount');

        return [
            'credit_sales'                      => $credit_sales,
            'previous_credit_sales'             => $previous_credit_sales,
            'form22_details'                    => $form22_details,
            'form17_increase'                   => $form17_increase,
            'form17_decrease'                   => $form17_decrease,
            'transaction'                       => $transaction,
            'own_group'                         => $own_group,
            'credit_sales_transaction'          => $credit_sales_transaction,
            'previous_transaction'              => $previous_transaction,
            'previous_own_group'                => $previous_own_group,
            'previous_credit_sales_transaction' => $previous_credit_sales_transaction,
            'form17_increase_previous'          => $form17_increase_previous,
            'form17_decrease_previous'          => $form17_decrease_previous,
            'merged_sub_categories'             => $merged_sub_categories,
            'account_transactions'              => $account_transactions,
            'opening_stock'                     => $opening_stock,
            'previous_day'                      => (int) $previous_day,
            'today'                             => (int) $today,
            'cash_sales_today'                  => (int) $cash_sales_today,
            'credit_sales_today'                => (int) $credit_sales_today,
        ];
    }

    public function Form21CQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $credit_sales = $query->get();

        return $credit_sales;
    }
    //
    public function Form21CQueryReceipts($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.type', 'purchase')

            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'purchase_lines.quantity', 'purchase_lines.purchase_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $receipts = $query->get();

        return $receipts;
    }

    public function Form21CQuerytoday($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $todays = $query->get();

        return $todays;
    }
    //Form21CQuerypreviousday
    public function Form21CQuerypreviousday($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $todays = $query->get();

        return $todays;
    }
    //opening stock
    public function Form21CQueryOpeningStock($business_id, $start_date, $end_date, $location_id)
    {
        $opening_stock = Transaction::leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')->leftjoin('products', 'purchase_lines.product_id', 'products.id')->where('transactions.business_id', $business_id)->where('transactions.type', 'opening_stock')->select('purchase_lines.quantity', 'purchase_lines.purchase_price', 'products.sub_category_id')->get();

        return $opening_stock;
    }
    public function Form21CQueryTotalReceipts($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.type', 'purchase')

            ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'purchase_lines.quantity', 'purchase_lines.purchase_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $total_receipts = $query->get();

        return $total_receipts;
    }
    //Form21CQueryIssue
    public function Form21CQueryIssue($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->where('account_transactions.type', 'debit')
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $issuess = $query->get();

        return $issuess;
    }

    public function Form21CQuerycashtoday($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->where('account_transactions.type', 'debit')
            ->whereNull('transactions.customer_group_id')
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $cash_sales_todays = $query->get();

        return $cash_sales_todays;
    }
    public function Form21CQueryCorporate($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('contact_groups', 'transactions.customer_group_id', 'contact_groups.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $corporate = $query->get();

        return $corporate;
    }
    public function Form21CQueryTotalIssue($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where('account_transactions.type', 'debit')->orWhere('account_transactions.type', 'credit');
            })
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $totalissuess = $query->get();

        return $totalissuess;
    }
    public function Form21CQueryTotalIssueLastday($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where('account_transactions.type', 'debit')->orWhere('account_transactions.type', 'credit');
            })
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $totalissuesslastday = $query->get();

        return $totalissuesslastday;
    }
    public function Form21CQueryTotalIssueOne($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->where('account_transactions.type', 'debit')->orWhere('account_transactions.type', 'credit');
            })
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $totalissuesslastday = $query->get();

        return $totalissuesslastday;
    }
    //Form21CQueryDiscountToday
    public function Form21CQueryDiscountToday($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $discount_todays = $query->get();

        return $discount_todays;
    }
    public function Form21CQueryDiscountPrevious($business_id, $prevstart_date, $prevend_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '>=', $prevstart_date)
            ->whereDate('transactions.transaction_date', '<=', $prevend_date)
            ->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $discount_previouss = $query->get();

        return $discount_previouss;
    }
    public function Form21CQueryDiscountTotal($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('account_transactions.business_id', $business_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $discount_totals = $query->get();

        return $discount_totals;
    }
    //balance
    public function Form21CQueryBalance($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $balances = $query->get();

        return $balances;
    }
    //subtotal
    public function Form21CQuerySubtotal($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', Carbon::now())
            ->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $subtotal = $query->get();

        return $subtotal;
    }
    public function Form21CQueryQuantityIssue($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')->leftjoin('business', 'transactions.business_id', 'business.id')->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')->leftjoin('account_transactions', 'transactions.id', '=', 'account_transactions.transaction_id')->whereDate('transactions.transaction_date', '=', Carbon::now())->select('transactions.transaction_date', 'transactions.final_total', 'transactions.discount_amount', 'products.name as description', 'products.sub_category_id', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $subtotal = $query->get();

        return $subtotal;
    }
    public function Form21CQueryPumperLast($business_id, $prevstart_date, $prevend_date, $location_id)
    {
        $query = Pump::leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('business_locations', 'pumps.location_id', 'business_locations.id')
            ->leftjoin('fuel_tanks', 'pumps.fuel_tank_id', 'fuel_tanks.id')
            ->where('pumps.business_id', $business_id)
            ->select(['pumps.*', 'products.sub_category_id', 'fuel_tanks.fuel_tank_number', 'products.name as product_name', 'business_locations.name as location_name']);
        $pumper_lasts = $query->get();

        return $pumper_lasts;
    }
    public function get_21_c_form_all_querys(Request $request)
    {
        $business_id           = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();

        $start_date  = $request->start_date;
        $end_date    = $request->end_date;
        $location_id = $request->location_id;

        $settings            = MpcsFormSetting::where('business_id', $business_id)->first();
        $F21C_form_tdate     = $settings->F21C_form_tdate;
        $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $previous_end_date   = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
        $startDate           = Carbon::createFromFormat('Y-m-d', $start_date);
        $endDate             = Carbon::createFromFormat('Y-m-d', $end_date);

        $credit_sales          = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
        $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
        $form22_details        = FormF22Detail::where('business_id', $business_id)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();
        $form17_increase       = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

        $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')->whereDate('created_at', '>=', $previous_start_date)->orderBy('id', 'DESC')->first();

        $form17_decrease = FormF17Detail::where('select_mode', 'decrease')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->orderBy('id', 'DESC')->first();

        $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
            ->whereDate('created_at', '>=', $previous_start_date)
        // ->whereDate('created_at', '<=', $previous_end_date)
            ->orderBy('id', 'DESC')
            ->first();

        $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'cash')->orWhere('transaction_payments.method', 'cheque')->orWhere('transaction_payments.method', 'card')->orderBy('id', 'DESC')->first();

        $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
            ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')
            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
        // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
            ->orWhere('transaction_payments.method', 'cash')
            ->orWhere('transaction_payments.method', 'cheque')
            ->orWhere('transaction_payments.method', 'card')
            ->orderBy('id', 'DESC')
            ->first();

        $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

        $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->orWhere('transaction_payments.method', 'custom_pay_1')->orWhere('transaction_payments.method', 'custom_pay_2')->orderBy('id', 'DESC')->first();

        $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();

        $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total', 'transaction_payments.method as payment_method', 'transaction_sell_lines.quantity', 'transaction_sell_lines.unit_price', 'transactions.ref_no', 'transactions.invoice_no', 'transactions.invoice_no as order_no')->whereDate('transactions.transaction_date', '>=', $previous_start_date)->where('transaction_payments.method', 'credit_sales')->orderBy('id', 'DESC')->first();

        $account_transactions = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date)->where('account_transactions.business_id', $business_id)->get();

        $opening_stock = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')
        //->whereDate('transactions.transaction_date', '>=', $start_date)
        //->whereDate('transactions.transaction_date', '<=', $end_date)
            ->where('account_transactions.business_id', $business_id)
            ->where('transactions.type', 'opening_stock')
            ->where('transactions.status', 'final')
            ->sum('account_transactions.amount');
        // ->get();

        $today = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')->whereDate('transactions.transaction_date', '=', Carbon::now())->where('account_transactions.business_id', $business_id)->sum('account_transactions.amount');
        // ->get();
        $previous_day = AccountTransaction::join('transactions', 'transactions.id', 'account_transactions.transaction_id')
            ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
            ->where('account_transactions.business_id', $business_id)
            ->sum('account_transactions.amount');
        // ->get();
        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
        $cash_sales_today   = AccountTransaction::whereDate('account_transactions.operation_date', '=', Carbon::now())->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')->where('account_transactions.business_id', $business_id)->where('account_transactions.type', 'debit')->get()->sum('amount');
        $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date', '=', Carbon::now())->join('transactions', 'transactions.id', '=', 'account_transactions.trfansaction_id')->where('account_transactions.business_id', $business_id)->where('account_transactions.type', 'credit')->get()->sum('amount');

        return [
            'credit_sales'                      => $credit_sales,
            'previous_credit_sales'             => $previous_credit_sales,
            'form22_details'                    => $form22_details,
            'form17_increase'                   => $form17_increase,
            'form17_decrease'                   => $form17_decrease,
            'transaction'                       => $transaction,
            'own_group'                         => $own_group,
            'credit_sales_transaction'          => $credit_sales_transaction,
            'previous_transaction'              => $previous_transaction,
            'previous_own_group'                => $previous_own_group,
            'previous_credit_sales_transaction' => $previous_credit_sales_transaction,
            'form17_increase_previous'          => $form17_increase_previous,
            'form17_decrease_previous'          => $form17_decrease_previous,
            'merged_sub_categories'             => $merged_sub_categories,
            'account_transactions'              => $account_transactions,
            'opening_stock'                     => $opening_stock,
            'previous_day'                      => (int) $previous_day,
            'today'                             => (int) $today,
            'cash_sales_today'                  => (int) $cash_sales_today,
            'credit_sales_today'                => (int) $credit_sales_today,
        ];
    }

    public function get9BFormData(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $start_date  = $request->input('start_date');
        $end_date    = $request->input('end_date');

        // Validate dates
        if (empty($start_date) || empty($end_date)) {
            return response()->json([
                'success' => false,
                'msg'     => 'Invalid date range',
            ]);
        }

        // Get current period data
        $currentData = $this->getSalesData($business_id, $start_date, $end_date);
        // Get the latest settings record
        $header_latest = Mpcs21cFormSettings::orderBy('created_at', 'desc')->first();
        $textDetails   = Mpcs9aFormTextDetail::orderBy('created_at', 'desc')->first() ?? '-';

        // if (!$header_latest) {
        //     // Handle case when no settings exist
        //     return response()->json([
        //         'success' => false,
        //         'msg' => 'No form settings found'
        //     ]);
        // }

        // Get previous period data
        $previous_start_date = Carbon::parse($start_date)->subDay()->format('Y-m-d');
        $previous_end_date   = Carbon::parse($end_date)->subDay()->format('Y-m-d');

        // Use the date from settings for previous data
        $previousData = $this->getSalesData(
            $business_id,
            $header_latest->date, // Using the date from settings
            $previous_end_date
        );

        // Prepare response data
        return response()->json([
            'success' => true,
            'data'    => [
                'current'      => [
                    'total_sales'   => $currentData['total_sales'],
                    'cash_sales'    => $currentData['cash_sales'],
                    'card_sales'    => $currentData['card_sales'],
                    'credit_sales'  => $currentData['credit_sales'],
                    'empty_barrels' => $currentData['empty_barrels'],
                    'other_sales'   => $currentData['other_sales'],
                    'total_amount'  => $currentData['total_sales'] + $currentData['empty_barrels'] + $currentData['other_sales'],
                ],
                'previous'     => [
                    'total_sales'   => $previousData['total_sales'],
                    'cash_sales'    => $previousData['cash_sales'],
                    'card_sales'    => $previousData['card_sales'],
                    'credit_sales'  => $previousData['credit_sales'],
                    'empty_barrels' => $previousData['empty_barrels'],
                    'other_sales'   => $previousData['other_sales'],
                    'total_amount'  => $previousData['total_sales'] + $previousData['empty_barrels'] + $previousData['other_sales'],
                ],
                'combined'     => [
                    'total_sales'   => $currentData['total_sales'] + $previousData['total_sales'],
                    'cash_sales'    => $currentData['cash_sales'] + $previousData['cash_sales'],
                    'card_sales'    => $currentData['card_sales'] + $previousData['card_sales'],
                    'credit_sales'  => $currentData['credit_sales'] + $previousData['credit_sales'],
                    'empty_barrels' => $currentData['empty_barrels'] + $previousData['empty_barrels'],
                    'other_sales'   => $currentData['other_sales'] + $previousData['other_sales'],
                    'total_amount'  => ($currentData['total_sales'] + $previousData['total_sales']) +
                    ($currentData['empty_barrels'] + $previousData['empty_barrels']) +
                    ($currentData['other_sales'] + $previousData['other_sales']),
                ],
                'text_details' => $textDetails ? $textDetails->text_content : 'No additional details available',

            ],
        ]);
    }

    public function get9AFormData(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $start_date  = $request->input('start_date');
        $end_date    = $request->input('end_date');

        // Validate dates
        if (empty($start_date) || empty($end_date)) {
            return response()->json([
                'success' => false,
                'msg'     => 'Invalid date range',
            ]);
        }

        // Get current period data
        $currentData = $this->getSalesData($business_id, $start_date, $end_date);

        // Get the latest settings record
        $header_latest = Mpcs21cFormSettings::orderBy('created_at', 'desc')->first();
        $textDetails   = Mpcs9aFormTextDetail::orderBy('created_at', 'desc')->first() ?? '-';

        // Get previous period data (previous day)
        $previous_start_date = Carbon::parse($start_date)->subDay()->format('Y-m-d');
        $previous_end_date   = Carbon::parse($end_date)->subDay()->format('Y-m-d');

        $previousData = $this->getSalesData(
            $business_id,
            $previous_start_date,
            $previous_end_date
        );

        // Prepare response data
        return response()->json([
            'success' => true,
            'data'    => [
                'current'      => [
                    'total_sales'   => $currentData['total_sales'],
                    'cash_sales'    => $currentData['cash_sales'],
                    'card_sales'    => $currentData['card_sales'],
                    'credit_sales'  => $currentData['credit_sales'],
                    'empty_barrels' => $currentData['empty_barrels'],
                    'other_sales'   => $currentData['other_sales'],
                    'total_amount'  => $currentData['total_sales'] + $currentData['empty_barrels'] + $currentData['other_sales'],
                ],
                'previous'     => [
                    'total_sales'   => $previousData['total_sales'],
                    'cash_sales'    => $previousData['cash_sales'],
                    'card_sales'    => $previousData['card_sales'],
                    'credit_sales'  => $previousData['credit_sales'],
                    'empty_barrels' => $previousData['empty_barrels'],
                    'other_sales'   => $previousData['other_sales'],
                    'total_amount'  => $previousData['total_sales'] + $previousData['empty_barrels'] + $previousData['other_sales'],
                ],
                'combined'     => [
                    'total_sales'   => $currentData['total_sales'] + $previousData['total_sales'],
                    'cash_sales'    => $currentData['cash_sales'] + $previousData['cash_sales'],
                    'card_sales'    => $currentData['card_sales'] + $previousData['card_sales'],
                    'credit_sales'  => $currentData['credit_sales'] + $previousData['credit_sales'],
                    'empty_barrels' => $currentData['empty_barrels'] + $previousData['empty_barrels'],
                    'other_sales'   => $currentData['other_sales'] + $previousData['other_sales'],
                    'total_amount'  => ($currentData['total_sales'] + $previousData['total_sales']) +
                    ($currentData['empty_barrels'] + $previousData['empty_barrels']) +
                    ($currentData['other_sales'] + $previousData['other_sales']),
                ],
                'text_details' => $textDetails ? $textDetails->text_content : 'No additional details available',
            ],
        ]);
    }

    private function getSalesData($business_id, $start_date, $end_date)
    {
        // Get cash and card sales (non-credit)
        $salesData = Transaction::join('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('transactions.is_credit_sale', 0)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select(
                DB::raw('SUM(transaction_payments.amount) as total_amount'),
                DB::raw('SUM(CASE WHEN transaction_payments.method = "cash" THEN transaction_payments.amount ELSE 0 END) as cash_sales'),
                DB::raw('SUM(CASE WHEN transaction_payments.method = "card" THEN transaction_payments.amount ELSE 0 END) as card_sales')
            )
            ->first();

        // Get credit sales separately
        $creditSales = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('is_credit_sale', 1)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->select(DB::raw('SUM(final_total) as credit_sales'))
            ->first()
            ->credit_sales ?? 0;

        // Get empty barrels data
        $emptyBarrels = Transaction::where('business_id', $business_id)
            ->where('type', 'empty_barrel')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('final_total');

        // Get other sales data (including transport if needed)
        $otherSales = Transaction::where('business_id', $business_id)
            ->where('type', 'other_sales')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('final_total');

        return [
            'total_sales'   => ($salesData->total_amount ?? 0) + $creditSales,
            'cash_sales'    => $salesData->cash_sales ?? 0,
            'card_sales'    => $salesData->card_sales ?? 0,
            'credit_sales'  => $creditSales,
            'empty_barrels' => $emptyBarrels ?? 0,
            'other_sales'   => $otherSales ?? 0,
        ];
    }
    public function getPaymentsData(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $start_date  = $request->input('start_date');
        $end_date    = $request->input('end_date');

        // Validate dates
        if (empty($start_date) || empty($end_date)) {
            return response()->json([
                'success' => false,
                'msg'     => 'Invalid date range',
            ]);
        }

        // Get current period data
        $currentData = $this->getPayments($business_id, $start_date, $end_date);

        // Get the latest settings record for previous data
        $header_latest = Mpcs21cFormSettings::orderBy('created_at', 'desc')->first();

        // if (!$header_latest) {
        //     return response()->json([
        //         'success' => false,
        //         'msg' => 'No form settings found'
        //     ]);
        // }

        // Get previous period data (previous day)
        $previous_start_date = Carbon::parse($start_date)->subDay()->format('Y-m-d');
        $previous_end_date   = Carbon::parse($end_date)->subDay()->format('Y-m-d');

        $previousData = $this->getPayments(
            $business_id,
            $previous_start_date,
            $previous_end_date
        );

        // Prepare response data
        return response()->json([
            'success' => true,
            'data'    => [
                'current'  => [
                    'cash_payments'        => $currentData['cash_payments'],
                    'cheque_card_payments' => $currentData['cheque_card_payments'],
                    'total_payments'       => $currentData['total_payments'],
                    'balance_in_hand'      => $currentData['balance_in_hand'],
                    'grand_total'          => $currentData['grand_total'],
                ],
                'previous' => [
                    'cash_payments'        => $previousData['cash_payments'],
                    'cheque_card_payments' => $previousData['cheque_card_payments'],
                    'total_payments'       => $previousData['total_payments'],
                    'balance_in_hand'      => $previousData['balance_in_hand'],
                    'grand_total'          => $previousData['grand_total'],
                ],
                'combined' => [
                    'cash_payments'        => $currentData['cash_payments'] + $previousData['cash_payments'],
                    'cheque_card_payments' => $currentData['cheque_card_payments'] + $previousData['cheque_card_payments'],
                    'total_payments'       => $currentData['total_payments'] + $previousData['total_payments'],
                    'balance_in_hand'      => $currentData['balance_in_hand'] + $previousData['balance_in_hand'],
                    'grand_total'          => $currentData['grand_total'] + $previousData['grand_total'],
                ],
            ],
        ]);
    }

    private function getPayments($business_id, $start_date, $end_date)
    {
        // Get payments data
        $paymentsData = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select(
                DB::raw('SUM(transaction_payments.amount) as total_amount'),
                DB::raw('SUM(CASE WHEN transaction_payments.method = "cash" THEN transaction_payments.amount ELSE 0 END) as cash_payments'),
                DB::raw('SUM(CASE WHEN transaction_payments.method IN ("cheque", "card") THEN transaction_payments.amount ELSE 0 END) as cheque_card_payments')
            )
            ->first();

        // Calculate totals
        $total_payments       = $paymentsData->total_amount ?? 0;
        $cash_payments        = $paymentsData->cash_payments ?? 0;
        $cheque_card_payments = $paymentsData->cheque_card_payments ?? 0;

                                            // These values should be calculated based on your business logic
        $balance_in_hand = $cash_payments;  // Adjust as needed
        $grand_total     = $total_payments; // Adjust as needed

        return [
            'cash_payments'        => $cash_payments,
            'cheque_card_payments' => $cheque_card_payments,
            'total_payments'       => $total_payments,
            'balance_in_hand'      => $balance_in_hand,
            'grand_total'          => $grand_total,
        ];
    }

    /**
     * Apply the same DataTables global search and ordering to the in-memory
     * F9C rows before calculating page totals. Yajra applies those operations
     * after the extra response fields are built, so the footer must mirror them
     * explicitly to stay aligned with the rows actually displayed.
     */
    private function prepareForm9CCollectionForFooter($rows, Request $request)
    {
        $prepared = collect($rows)->map(function ($row) {
            return is_array($row) ? (object) $row : $row;
        })->values();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $needle = function_exists('mb_strtolower')
                ? mb_strtolower($search, 'UTF-8')
                : strtolower($search);

            $prepared = $prepared->filter(function ($row) use ($needle) {
                $values = [
                    $row->billno ?? '',
                    $row->ourref ?? '',
                    $row->product ?? '',
                    $row->category_name ?? '',
                    $row->quantity ?? '',
                    $row->unit_price ?? '',
                    $row->page ?? '',
                    $row->final_total_rs ?? '',
                    $row->goods_rs ?? '',
                    $row->loading_rs ?? '',
                    $row->empty_rs ?? '',
                    $row->transport_rs ?? '',
                    $row->other_rs ?? '',
                ];

                foreach ($values as $value) {
                    $text = (string) $value;
                    $text = function_exists('mb_strtolower')
                        ? mb_strtolower($text, 'UTF-8')
                        : strtolower($text);

                    if (strpos($text, $needle) !== false) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $orderableColumns = [
            'billno', 'ourref', 'product', 'category_name', 'quantity',
            'unit_price', 'page', 'final_total_rs', 'goods_rs', 'loading_rs',
            'empty_rs', 'transport_rs', 'other_rs',
        ];

        $orders = collect((array) $request->input('order', []))
            ->map(function ($order) use ($request) {
                $columnIndex = isset($order['column']) ? (int) $order['column'] : -1;
                $columnName = $columnIndex >= 0
                    ? (string) $request->input("columns.{$columnIndex}.data", '')
                    : '';

                return [
                    'column' => $columnName,
                    'direction' => strtolower((string) ($order['dir'] ?? 'asc')) === 'desc'
                        ? 'desc'
                        : 'asc',
                ];
            })
            ->filter(function ($order) use ($orderableColumns) {
                return in_array($order['column'], $orderableColumns, true);
            })
            ->values();

        if ($orders->isEmpty()) {
            $orders = collect([
                ['column' => 'billno', 'direction' => 'asc'],
            ]);
        }

        $prepared = $prepared->values()->map(function ($row, $index) {
            return [
                'row' => $row,
                '_original_index' => $index,
            ];
        })->sort(function ($left, $right) use ($orders) {
            foreach ($orders as $order) {
                $column = $order['column'];
                $a = $left['row']->{$column} ?? null;
                $b = $right['row']->{$column} ?? null;

                if (is_numeric($a) && is_numeric($b)) {
                    $comparison = (float) $a <=> (float) $b;
                } else {
                    $comparison = strnatcasecmp((string) $a, (string) $b);
                }

                if ($comparison !== 0) {
                    return $order['direction'] === 'desc' ? -$comparison : $comparison;
                }
            }

            $stableIdComparison = strnatcasecmp(
                (string) ($left['row']->_stable_id ?? ''),
                (string) ($right['row']->_stable_id ?? '')
            );
            if ($stableIdComparison !== 0) {
                return $stableIdComparison;
            }

            $sortDateComparison = strnatcasecmp(
                (string) ($left['row']->_sort_date ?? ''),
                (string) ($right['row']->_sort_date ?? '')
            );
            if ($sortDateComparison !== 0) {
                return $sortDateComparison;
            }

            return $left['_original_index'] <=> $right['_original_index'];
        })->pluck('row')->values();

        return $prepared;
    }

    public function getProductsByCategory(Request $request)
    {
        $categoryIds = $request->input('category_ids', []);
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $productQuery = Product::query();

        if (! empty($business_id)) {
            $productQuery->where('business_id', $business_id);
        }

        if (! empty($categoryIds)) {
            $productQuery->whereIn('sub_category_id', (array) $categoryIds);
        }

        $products = $productQuery->select('id', 'name')->get();

        return response()->json([
            'products' => $products,
        ]);
    }
}
