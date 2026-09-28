<?php

namespace Modules\MPCS\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\Mpcs9aFormSettings;
use Modules\MPCS\Entities\MpcsFormSetting;
use Modules\MPCS\Services\FormHelper;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Modules\MPCS\Entities\Mpcs15FormDetails;
use Modules\MPCS\Entities\FormF15TransactionData; 
use Modules\MPCS\Entities\FormF15Header; 
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF22Header;
use App\Contact;
use App\Transaction;
use Modules\MPCS\Entities\MpcsF15CategorySelection;
class F15FormController extends Controller
{ 
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;
 
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $this->resolveBusinessId($request);
        abort_if(empty($business_id), 403, 'Business context is missing.');

        // Other Data
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $business_name = BusinessLocation::where('business_id', $business_id)->value('name');
        $currency_precision = Business::where('id', $business_id)->value('currency_precision');
        $form15Header = FormF15Header::where('business_id', $business_id)->first();
        $name = $openingDate = $refPreviousFormNumber = $next_form_number = '';
        if (!empty($form15Header)) {
            $openingDate = $form15Header->dated_at;
            $name = User::where('id', $form15Header->created_by)->value('username');
            $settings = Mpcs15FormDetails::where('f15_form_id', $form15Header->id)->get();
            $refPreviousFormNumber = $settings->where('form15_label_id', 2)->first()?->rupees;
            $next_form_number = $settings->where('form15_label_id', 1)->first()?->rupees;
        }

        // F15 Daily Report - New is integrated into this existing, proven F15 page.
        // This prevents route/view visibility problems on servers using published
        // module views or an older module route cache.
        $dailyLocationQuery = BusinessLocation::query()
            ->where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('name');

        $permittedLocations = auth()->user()->permitted_locations();
        if ($permittedLocations !== 'all') {
            $dailyLocationQuery->whereIn('id', array_map('intval', (array) $permittedLocations));
        }

        $locations = $dailyLocationQuery->pluck('name', 'id');
        $selectedLocationId = (int) $request->input('location_id', $locations->keys()->first());
        if ($locations->isNotEmpty() && ! $locations->has($selectedLocationId)) {
            $selectedLocationId = (int) $locations->keys()->first();
        }
        $selectedDate = Carbon::parse($request->input('date', now()->toDateString()))->toDateString();
        $currencyPrecision = (int) ($currency_precision ?? 2);
        $openDailyReport = $request->boolean('open_daily_report')
            || in_array(strtolower((string) $request->segment(2)), [
                'f15-new', 'f15new', 'f15-daily-report'
            ], true);

        return view('mpcs::forms.F15')->with(compact(
            'business_locations',
            'suppliers',
            'openingDate',
            'business_name',
            'currency_precision',
            'name',
            'refPreviousFormNumber',
            'next_form_number',
            'form15Header',
            'locations',
            'selectedLocationId',
            'selectedDate',
            'currencyPrecision',
            'openDailyReport'
        ));
    }

    /**
     * Open the new report through the existing F15 controller/page.
     * This route works without relying on a separate page controller being
     * discovered by an old route/autoload cache.
     */
    public function dailyReportIndex(Request $request)
    {
        $request->merge(['open_daily_report' => 1]);

        return $this->index($request);
    }


    public function getFormF15Data(Request $request)
    {
        $startDate = $this->resolveF15Date($request->input('start_date'));

        $businessId = $this->resolveBusinessId($request);
        abort_if(empty($businessId), 403, 'Business context is missing.');
        $locationId = $request->input('location_id');
        if ($locationId === '' || $locationId === '0') {
            $locationId = null;
        }
        $f15Opening = FormHelper::getF15OpeningStockRowFromF22($businessId, $startDate->toDateString(), $locationId);

        // Load F15 rows
        $rows = DB::table('form_f15_transaction_data')->get();

        $totalRow = [
            'previous' => 0,
            'today' => 0,
            'as_of' => 0
        ];

        $final = [];

        foreach ($rows as $row) {
            $row->previous_date_rupees = 0;
            $row->today_rupees = 0;
            $row->as_of_today_rupees = 0;

            // Opening Stock (client §18): shared with get15SettingData via FormHelper
            if ($row->description === 'Opening Stock') {
                $row->previous_date_rupees = $f15Opening['opening_stock_previous'];
                $row->today_rupees = $f15Opening['opening_stock_today'];
                // Client rule: No 18 "As of Today" must always mirror "Up to Previous Date".
                $row->as_of_today_rupees = $row->previous_date_rupees;
            }

            // Grand Total = Total + Opening Stock
            if ($row->description === 'Grand Total') {
                $openingStock = collect($final)->firstWhere('description', 'Opening Stock');

                $row->previous_date_rupees = $totalRow['previous'] + ($openingStock->previous_date_rupees ?? 0);
                $row->today_rupees = $totalRow['today'] + ($openingStock->today_rupees ?? 0);
                $row->as_of_today_rupees = $totalRow['as_of'] + ($openingStock->as_of_today_rupees ?? 0);
            }

            // Track row values for Total row (m)
            if (!in_array($row->description, ['Total', 'Opening Stock', 'Grand Total'])) {
                $totalRow['previous'] += floatval($row->previous_date_rupees);
                $totalRow['today'] += floatval($row->today_rupees);
                $totalRow['as_of'] += floatval($row->as_of_today_rupees);
            }

            $final[] = $row;
        }

        // Now override Total row values
        foreach ($final as &$row) {
            if ($row->description === 'Total') {
                $row->previous_date_rupees = $totalRow['previous'];
                $row->today_rupees = $totalRow['today'];
                $row->as_of_today_rupees = $totalRow['as_of'];
            }
        }

        return response()->json(['data' => $final]);
    }



// TAB 2
    //By Zamaluddin : Time 09:00 AM : 29 January 2025
     public function get15FormSetting() {

        return view('mpcs::forms.partials.create_15_form_settings');

    }


    public function store15FormSetting(Request $request)
    {
        DB::beginTransaction();
        try {
            /*
             * IS2009: saved settings did not appear in the list afterwards.
             *
             * This read session('user.business_id') while mpcs15FormSettings()
             * - the endpoint that fills the table - filtered on
             * session('business.id'). Those are two DIFFERENT session keys, and
             * on installs where they hold different values (or where only one is
             * populated) the header was stored against a business the list never
             * queried. The rows were saved correctly; the list simply asked the
             * wrong question, which is why the page showed "No data available in
             * table" rather than any error.
             *
             * resolveBusinessId() is the resolver the newer methods in this same
             * controller already use. Routing both sides through it means the
             * write and the read can no longer disagree.
             */
            $business_id = $this->resolveBusinessId($request);

            if (empty($business_id)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'msg' => __('mpcs::lang.form_15_settings_add_failed'),
                    'error' => 'No active business could be resolved for this session.',
                ], 422);
            }

            $id_form_labels = $request->input('form15_label_id', []);
            $rupees = $request->input('rupees', []);

            $header = FormF15Header::create([
                'business_id' => $business_id,
                'dated_at' => $request->input('dated_at', date('Y-m-d')),
                'created_by' => auth()->user()->id,
            ]);

            $data_to_insert_settings = [];
            foreach ($id_form_labels as $key => $id_form_label) {
                $data_to_insert_settings[] = [
                    'f15_form_id' => $header->id,
                    'form15_label_id' => $id_form_label,
                    'rupees' => $rupees[$key] ?? 0,
                ];
            }
            Mpcs15FormDetails::insert($data_to_insert_settings);

            DB::commit();
            return response()->json([
                'success' => true,
                'msg' => __('mpcs::lang.form_15_settings_add_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'msg' => __('mpcs::lang.form_15_settings_add_failed'), 'error' => $e->getMessage()], 500);
        }
    }

    public function mpcs15FormSettings()
{
    if (request()->ajax()) {
        // IS2009: same resolver the save path now uses, so a header written by
        // store15FormSetting() is always visible to this list. Previously this
        // read only session('business.id') while the save used
        // session('user.business_id').
        $business_id = $this->resolveBusinessId(request());

        $header = Mpcs15FormDetails::with(['fheader'])
            ->whereHas('fheader', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })
            ->orderBy('id', 'ASC')
            ->get();
            
    
        return DataTables::of($header)
            ->addColumn('action', function ($row) {
                if (auth()->user()->can('superadmin')) {
                return '
                    <button type="button" 
                        data-href="' . url('/mpcs/edit-15-form-settings/' . $row->id) . '" 
                        class="btn-modal btn btn-primary btn-xs" 
                        data-container=".update_form_15_settings_modal">
                        <i class="fa fa-edit"></i> Edit
                    </button>';
                } else {
                    return '';
                }
            })
            ->editColumn('dated_at', function ($row) {
                return !empty($row->fheader->dated_at) ? date('Y-m-d', strtotime($row->fheader->dated_at)) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    $business_id = session()->get('business.id');
    $business_locations = BusinessLocation::forDropdown($business_id);
    return view('mpcs::forms.form_15', compact('business_locations'));
}



public function edit15FormSetting($id)
{
    if (request()->ajax()) {
        $business_id = session()->get('business.id');
        
        $settings = Mpcs15FormDetails::where('id', $id)
                                   ->first();

        return view('mpcs::forms.partials.edit_15_form_settings')
               ->with(compact('settings'));
    }
}
    
    public function mpcs15Update($id, Request $request)
    {
        DB::beginTransaction();
    
        try {
            $setting = Mpcs15FormDetails::find($id);
            $setting->rupees = $request->input('rupees');
            $setting->save();
            DB::commit();
    
            return response()->json([
                'success' => 1,
                'msg' => __('mpcs::lang.form_15_settings_update_success'),
                'setting' => $setting,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile() . " Line: " . $e->getLine() . " Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function delete15FormSetting($id)
    {
        try {
            $business_id = session()->get('business.id');
            $formSettings = Mpcs15FormDetails::where('id', $id)
                ->delete();

            if ($formSettings) {
                $output = [
                    'success' => true,
                    'msg' => __('Delete Success')
                ];
            } else {
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    public function get15SettingData(Request $request)
    {
        $businessId = $this->resolveBusinessId($request);
        abort_if(empty($businessId), 403, 'Business context is missing.');

        $startDate = $this->resolveF15Date($request->input('start_date'));
        $startOfMonth = $startDate->copy()->startOfMonth()->toDateString();
        $selectedDate = $startDate->toDateString();
        $isFirstDayOfMonth = $selectedDate === $startOfMonth;
        $previousDate = $startDate->copy()->subDay()->toDateString();
        $nextDate = \Carbon\Carbon::parse($startDate)->addDay()->format('Y-m-d');
        $locationId = $request->input('location_id');
        if ($locationId === '' || $locationId === '0') {
            $locationId = null;
        }
        $mpcsForm15 = FormF15Header::where('business_id', $businessId)->first();
        $mpcsForm15Settings =[];
        if (!empty($mpcsForm15)) {
            $mpcsForm15Settings = Mpcs15FormDetails::where('f15_form_id', $mpcsForm15->id)->get()->keyBy('form15_label_id');
        }
        $openingStockF22 = FormHelper::getF15OpeningStockRowFromF22(
            $businessId,
            $startDate->toDateString(),
            $locationId
        );
        $openingStockLegacy = FormHelper::getOpeningStockAsToDay($businessId, $startDate);

        $isF22StockTakingToday = FormHelper::isF22StockTakingDate(
            $businessId,
            $selectedDate,
            $locationId
        );

        // The first day is a zero-reset only when no F22 stock-taking form exists
        // for that same date. A saved F22 is always the authoritative opening stock.
        $isMonthStartWithoutF22 = $isFirstDayOfMonth && !$isF22StockTakingToday;

        $purchaseCycleStart = FormHelper::getF15CycleStartDate(
            $businessId,
            $selectedDate,
            $locationId
        );
        $isPurchaseCarryResetToday = $isFirstDayOfMonth
            || $isF22StockTakingToday
            || $purchaseCycleStart === $selectedDate;

        // No. 15/17 "Up to Previous Date" starts again on both reset boundaries:
        // 1) the first day of the month; and 2) an F22 stock-taking date.
        // From the following date onward, only purchases from the latest reset date
        // are carried, so pre-F22 or prior-month purchases can never reappear.
        $f16aPurchaseUpToPreviousDay = 0.0;
        $f18PurchaseUpToPreviousDay = 0.0;

        if ($isPurchaseCarryResetToday) {
            $purchaseUpToPreviousDay = 0.0;
            $totalPurchaseUpToPreviousDay = 0.0;
            $totalNo17UpToPreviousDay = 0.0;
            $openingStockToPreviousDay = $isMonthStartWithoutF22
                ? 0.0
                : (float) ($openingStockF22['opening_stock_previous'] ?? 0.0);
            $grandTotal1UpToPreviousDay = $openingStockToPreviousDay;
        } elseif (!empty($mpcsForm15) && $selectedDate == $mpcsForm15->dated_at) {
            $f18PurchaseUpToPreviousDay = (float) ($mpcsForm15Settings[3]->rupees ?? 0);
            $purchaseUpToPreviousDay = $f18PurchaseUpToPreviousDay;
            // Label 4: "Total (No 17) Up to Previous Day" — may differ from store purchases (label 3).
            $totalNo17UpToPreviousDay = (float) ($mpcsForm15Settings[4]->rupees ?? 0);
            $openingStockToPreviousDay = (float) ($mpcsForm15Settings[5]->rupees ?? 0);
            $grandTotal1UpToPreviousDay = (float) ($mpcsForm15Settings[6]->rupees ?? 0);
            $totalPurchaseUpToPreviousDay = (float) ($mpcsForm15Settings[4]->rupees ?? 0);
        } else {
            $f16aPurchaseUpToPreviousDay = FormHelper::getF16aSaleTotalForDateRange(
                $businessId,
                $purchaseCycleStart,
                $previousDate,
                $locationId
            );
            $f18PurchaseUpToPreviousDay = FormHelper::getF18ReceivedSaleTotalForDateRange(
                $businessId,
                $purchaseCycleStart,
                $previousDate,
                $locationId
            );
            $purchaseUpToPreviousDay = $f16aPurchaseUpToPreviousDay + $f18PurchaseUpToPreviousDay;
            $totalPurchaseUpToPreviousDay = $purchaseUpToPreviousDay;
            $totalNo17UpToPreviousDay = $purchaseUpToPreviousDay;
            $openingStockToPreviousDay = (float) ($openingStockF22['opening_stock_previous'] ?? 0.0);
            $grandTotal1UpToPreviousDay = $totalNo17UpToPreviousDay + $openingStockToPreviousDay;
        }

        $selectedDay = $selectedDate;

        // §20–§22: "Up to Previous Date" = previous calendar day "As of Today" (cash / card / credit); all zero on an F22 date.
        if ($isF22StockTakingToday) {
            $cashUpToPreviousDay = 0.0;
            $cardUpToPreviousDay = 0.0;
            $creditUpToPreviousDay = 0.0;
        } else {
            $prevCalendarDay = $startDate->copy()->subDay()->toDateString();
            $cashUpToPreviousDay = FormHelper::getF15CashSaleAsOfTodayForDate(
                $businessId,
                $prevCalendarDay,
                $locationId
            );
            $cardUpToPreviousDay = FormHelper::getF15CardSaleAsOfTodayForDate(
                $businessId,
                $prevCalendarDay,
                $locationId
            );
            $creditUpToPreviousDay = FormHelper::getF15CreditSaleAsOfTodayForDate(
                $businessId,
                $prevCalendarDay,
                $locationId
            );
        }

        // On the first day of a month without an F22 stock count, only carried
        // values reset. Selected-day cash/card/credit sales must still be shown.
        if ($isMonthStartWithoutF22) {
            $cashUpToPreviousDay = 0.0;
            $cardUpToPreviousDay = 0.0;
            $creditUpToPreviousDay = 0.0;
        }

        $totalSaleUpToPreviousDay = $cardUpToPreviousDay + $cashUpToPreviousDay + $creditUpToPreviousDay;
        $balanceStockUpToPreviousDay = $grandTotal1UpToPreviousDay - $totalSaleUpToPreviousDay;
        // Bottom Grand Total (No 33) must mirror No 19 for each column.
        $grandTotal2UpToPreviousDay = $grandTotal1UpToPreviousDay;

        // No. 15 Purchases / Today: selected calendar date only.
        $f16aPurchaseToday = FormHelper::getF16aSaleTotalForDateRange(
            $businessId,
            $selectedDay,
            $selectedDay,
            $locationId
        );
        $f18PurchaseToday = FormHelper::getF18ReceivedSaleTotalForDateRange(
            $businessId,
            $selectedDay,
            $selectedDay,
            $locationId
        );
        $purchaseToday = $f16aPurchaseToday + $f18PurchaseToday;
        $totalPurchaseToday = $purchaseToday;

        // Sales entered/finalized on an F22 stock-taking date still belong to that
        // calendar day's F15.  Stock taking resets only the Up to Previous Date
        // carry; it must never suppress the Cash, Card or Credit values for Today.
        $cashToday = FormHelper::getCashTodayExcludingFuel($businessId, $selectedDay, $locationId);
        $cardToday = FormHelper::getCardAsToDay($businessId, $selectedDay, $selectedDay, $locationId);
        $creditToday = FormHelper::getCreditAsToDay($businessId, $selectedDay, $selectedDay, $locationId);
        // F22 saved on the selected date always has priority over the F15 opening
        // settings date, including when both fall on the first day of the month.
        if ($isF22StockTakingToday) {
            $openingStockToday = (float) ($openingStockF22['opening_stock_today'] ?? 0.0);
        } elseif (!empty($mpcsForm15) && $startDate->toDateString() == $mpcsForm15->dated_at) {
            $openingStockToday = (float) ($openingStockLegacy['opening_stock_today'] ?? 0.0);
        } else {
            $openingStockToday = (float) ($openingStockF22['opening_stock_today'] ?? 0.0);
        }

        // If no F22 was saved on the first day of the month, only the opening
        // stock row resets to zero. Purchases and sales entered for that date remain visible.
        if ($isMonthStartWithoutF22) {
            $openingStockToPreviousDay = 0.0;
            $openingStockToday = 0.0;
        }

        // No. 18 Opening Stock / As of Today:
        // - on an F22 date, use that day's F22 stock value at sale price;
        // - otherwise carry forward the previous day's Balance Stock in Sale Price.
        $openingStockAsOfDisplay = (float) $openingStockToday;

        // Store Purchase row cumulative (label 3 path); Total No 17 "As of Today" uses totalNo17UpToday when [4] differs.
        $f16aPurchaseUpToday = $f16aPurchaseUpToPreviousDay + $f16aPurchaseToday;
        $f18PurchaseUpToday = $f18PurchaseUpToPreviousDay + $f18PurchaseToday;
        $purchaseUpToday = $purchaseUpToPreviousDay + $purchaseToday;
        $totalNo17UpToday = $totalNo17UpToPreviousDay + $purchaseToday;
        $totalPurchaseUpToday = $totalNo17UpToday;
        $totalPurchaseToday = $purchaseToday;

        $cardUpToday = $cardUpToPreviousDay + $cardToday;
        $cashUpToday = $cashUpToPreviousDay + $cashToday;
        $creditUpToday = $creditUpToPreviousDay + $creditToday;
        $openingStockUpToday = $openingStockAsOfDisplay;

        // §19: Grand Total each column = No 17 + No 18 for that column.
        $grandTotal1Today = $purchaseToday + $openingStockToday;
        $grandTotal1UpToday = $totalNo17UpToday + $openingStockAsOfDisplay;

        $totalSaleToday = $cardToday + $cashToday + $creditToday;
        $totalSaleUpToday = $cardUpToday + $cashUpToday + $creditUpToday;

        $balanceStockToday = $grandTotal1Today - $totalSaleToday;
        $balanceStockUpToday = $grandTotal1UpToday - $totalSaleUpToday;

        $grandTotal2Today = $grandTotal1Today;
        $grandTotal2UpToday = $grandTotal1UpToday;

        $currency_precision = (int) (Business::where('id', $businessId)->value('currency_precision') ?? 2);
        $formatAmount = function ($amount) use ($currency_precision) {
            return number_format($amount, $currency_precision, '.', ',');
        };

        if ($mpcsForm15) {
            $diffDays = $startDate->diffInDays($mpcsForm15->dated_at);
            $formNumber = $mpcsForm15Settings[1]->rupees ?? 0;

            if ($startDate->greaterThan($mpcsForm15->dated_at)) {
                $formNumber += $diffDays;
            } elseif ($startDate->lessThan($mpcsForm15->dated_at)) {
                $formNumber = max(0, $formNumber - $diffDays);
            }
        } else {
            $formNumber = $mpcsForm15Settings[1]->rupees ?? 0;
        }

        $form9ASettings = Mpcs9aFormSettings::where('business_id', $businessId)->first();
        if ($form9ASettings) {
            $form9ANumber = $form9ASettings->starting_number ?? 0;
            $diff9ADays = $startDate->diffInDays($form9ASettings->date);

            if ($startDate->greaterThan($form9ASettings->date)) {
                $form9ANumber += $diff9ADays;
            } elseif ($startDate->lessThan($form9ASettings->date)) {
                $form9ANumber = max(0, $form9ANumber - $diff9ADays);
            }
        } else {
            $form9ANumber = 0;
        }

        // Month-start without F22 no longer returns a completely empty report.
        // The normal calculations below continue so today's purchases and sales display.

        // Ref Book No: all F16A and F18 form numbers for purchases on the selected date (client F15 §15).
        $form16aBookRefsRaw = FormHelper::getF16aBookReferenceListForDateRange(
            $businessId,
            $selectedDay,
            $selectedDay,
            $locationId
        );
        $form18BookRefsRaw = FormHelper::getF18BookReferenceListForDateRange(
            $businessId,
            $selectedDay,
            $selectedDay,
            $locationId
        );
        $form16aBookRefsArr = array_filter([$form16aBookRefsRaw, $form18BookRefsRaw]);
        $form16aBookRefs = implode(', ', $form16aBookRefsArr);

        $category_ids = FormHelper::getActiveCategoryIds($businessId);
        $resolved_ids = FormHelper::getResolvedCategoryIdsForFiltering($businessId, $category_ids);

        $todayF17IncreaseExists = false;
        $todayF17DecreaseExists = false;

        $lubricantGasIds = FormHelper::getLubricantAndGasCategoryIds($businessId);
        $priceChangeCategoryIds = array_values(array_intersect($resolved_ids, $lubricantGasIds));

        if (!empty($priceChangeCategoryIds)) {
            $todayF17IncreaseExists = FormF17Header::where('business_id', $businessId)
                ->where('date', $selectedDate)
                ->when(!empty($locationId), function ($query) use ($locationId) {
                    $query->where('location_id', $locationId);
                })
                ->whereExists(function ($query) use ($priceChangeCategoryIds) {
                    $query->select(DB::raw(1))
                        ->from('form_f17_details as fd')
                        ->join('products as p', 'fd.product_id', '=', 'p.id')
                        ->whereColumn('fd.header_id', 'form_f17_headers.id')
                        ->where('fd.select_mode', 'increase')
                        ->where(function($sub) use ($priceChangeCategoryIds) {
                            $sub->whereIn('p.category_id', $priceChangeCategoryIds)
                                ->orWhereIn('p.sub_category_id', $priceChangeCategoryIds);
                        });
                })
                ->exists();

            $todayF17DecreaseExists = FormF17Header::where('business_id', $businessId)
                ->where('date', $selectedDate)
                ->when(!empty($locationId), function ($query) use ($locationId) {
                    $query->where('location_id', $locationId);
                })
                ->whereExists(function ($query) use ($priceChangeCategoryIds) {
                    $query->select(DB::raw(1))
                        ->from('form_f17_details as fd')
                        ->join('products as p', 'fd.product_id', '=', 'p.id')
                        ->whereColumn('fd.header_id', 'form_f17_headers.id')
                        ->where('fd.select_mode', 'decrease')
                        ->where(function($sub) use ($priceChangeCategoryIds) {
                            $sub->whereIn('p.category_id', $priceChangeCategoryIds)
                                ->orWhereIn('p.sub_category_id', $priceChangeCategoryIds);
                        });
                })
                ->exists();
        }

        $priceIncrementPrevious = $this->getActivePriceChangeForDate($businessId, $previousDate, 'increase', $locationId);
        $priceIncrementToday = $todayF17IncreaseExists
            ? $this->getActivePriceChangeForDate($businessId, $selectedDate, 'increase', $locationId)
            : ['amount' => 0.0, 'form_numbers' => ''];

        $priceReductionPrevious = $this->getActivePriceChangeForDate($businessId, $previousDate, 'decrease', $locationId);
        $priceReductionToday = $todayF17DecreaseExists
            ? $this->getActivePriceChangeForDate($businessId, $selectedDate, 'decrease', $locationId)
            : ['amount' => 0.0, 'form_numbers' => ''];

        return response()->json([
            'cash_today' => $formatAmount($cashToday),
            'cash_previous' => $formatAmount($cashUpToPreviousDay),
            'cash_total' => $formatAmount($cashUpToday),

            'card_today' => $formatAmount($cardToday),
            'card_previous' => $formatAmount($cardUpToPreviousDay),
            'card_total' => $formatAmount($cardUpToday),

            'credit_today' => $formatAmount($creditToday),
            'credit_previous' => $formatAmount($creditUpToPreviousDay),
            'credit_total' => $formatAmount($creditUpToday),

            'purchase_today' => $formatAmount($f18PurchaseToday),
            'purchase_previous' => $formatAmount($f18PurchaseUpToPreviousDay),
            'purchase_total' => $formatAmount($f18PurchaseUpToday),

            'store_purchase_today' => $formatAmount($f18PurchaseToday),
            'store_purchase_previous' => $formatAmount($f18PurchaseUpToPreviousDay),
            'store_purchase_total' => $formatAmount($f18PurchaseUpToday),
            'store_purchase_book_no' => $form18BookRefsRaw,

            'direct_purchase_today' => $formatAmount($f16aPurchaseToday),
            'direct_purchase_previous' => $formatAmount($f16aPurchaseUpToPreviousDay),
            'direct_purchase_total' => $formatAmount($f16aPurchaseUpToday),
            'direct_purchase_book_no' => $form16aBookRefsRaw,

            'sub_total_today' => $formatAmount($purchaseToday),
            'sub_total_previous' => $formatAmount($purchaseUpToPreviousDay),
            'sub_total_total' => $formatAmount($purchaseUpToday),

            'total_purchase_today' => $formatAmount($totalPurchaseToday),
            'total_purchase_previous' => $formatAmount($totalPurchaseUpToPreviousDay),
            'total_purchase_total' => $formatAmount($totalPurchaseUpToday),

            'opening_stock_today' => $formatAmount($openingStockToday),
            'opening_stock_previous' => $formatAmount($openingStockToPreviousDay),
            'opening_stock_total' => $formatAmount($openingStockAsOfDisplay),

            'opening_f22_book_refs' => $openingStockF22['opening_f22_book_refs'],

            'grand_total1_today'    => $formatAmount($grandTotal1Today),
            'grand_total1_previous' => $formatAmount($grandTotal1UpToPreviousDay),
            'grand_total1_total'    => $formatAmount($grandTotal1UpToday),

            'total_sale_today'    => $formatAmount($totalSaleToday),
            'total_sale_previous' => $formatAmount($totalSaleUpToPreviousDay),
            'total_sale_total'    => $formatAmount($totalSaleUpToday),

            'balance_stock_today'    => $formatAmount($balanceStockToday),
            'balance_stock_previous' => $formatAmount($balanceStockUpToPreviousDay),
            'balance_stock_total'    => $formatAmount($balanceStockUpToday),

            'grand_total2_today'    => $formatAmount($grandTotal2Today),
            'grand_total2_previous' => $formatAmount($grandTotal2UpToPreviousDay),
            'grand_total2_total'    => $formatAmount($grandTotal2UpToday),

            'form_number' => $formNumber,
            'form_9a_number' => 'F9A/' . $form9ANumber,
            'form_16a_number' => $form16aBookRefs,
            'price_increment_previous' => $formatAmount($priceIncrementPrevious['amount']),
            'price_increment_today' => $formatAmount($priceIncrementToday['amount']),
            'price_increment_total' => $formatAmount($priceIncrementPrevious['amount'] + $priceIncrementToday['amount']),
            'price_increment_form_numbers' => $priceIncrementToday['form_numbers'],
            'price_reduction_previous' => $formatAmount($priceReductionPrevious['amount']),
            'price_reduction_today' => $formatAmount($priceReductionToday['amount']),
            'price_reduction_total' => $formatAmount($priceReductionPrevious['amount'] + $priceReductionToday['amount']),
            'price_reduction_form_numbers' => $priceReductionToday['form_numbers'],
            'is_monthly_reset' => $isMonthStartWithoutF22,
            'is_f22_reset' => $isF22StockTakingToday,
            'purchase_cycle_start' => $purchaseCycleStart,
        ]);
    }

    private function getActivePriceChangeForDate(int $businessId, string $date, string $selectMode, $locationId = null): array
    {
        $category_ids = FormHelper::getActiveCategoryIds($businessId);
        if (empty($category_ids)) {
            return [
                'amount' => 0.0,
                'form_numbers' => '',
            ];
        }

        $resolved_ids = FormHelper::getResolvedCategoryIdsForFiltering($businessId, $category_ids);
        $lubricantGasIds = FormHelper::getLubricantAndGasCategoryIds($businessId);
        $priceChangeCategoryIds = array_values(array_intersect($resolved_ids, $lubricantGasIds));

        if (empty($priceChangeCategoryIds)) {
            return [
                'amount' => 0.0,
                'form_numbers' => '',
            ];
        }

        $lastF17 = FormF17Header::where('business_id', $businessId)
            ->where('date', '<=', $date)
            ->when(!empty($locationId), function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->whereExists(function ($query) use ($selectMode, $priceChangeCategoryIds) {
                $query->select(DB::raw(1))
                    ->from('form_f17_details as fd')
                    ->join('products as p', 'fd.product_id', '=', 'p.id')
                    ->whereColumn('fd.header_id', 'form_f17_headers.id')
                    ->where('fd.select_mode', $selectMode)
                    ->where(function($sub) use ($priceChangeCategoryIds) {
                        $sub->whereIn('p.category_id', $priceChangeCategoryIds)
                            ->orWhereIn('p.sub_category_id', $priceChangeCategoryIds);
                    });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastF17) {
            return [
                'amount' => 0.0,
                'form_numbers' => '',
            ];
        }

        $amountColumn = $selectMode === 'increase'
            ? 'price_changed_gain'
            : 'price_changed_loss';

        $amount = (float) DB::table('form_f17_details as fd')
            ->join('products as p', 'fd.product_id', '=', 'p.id')
            ->where('fd.header_id', $lastF17->id)
            ->where('fd.select_mode', $selectMode)
            ->where(function($sub) use ($priceChangeCategoryIds) {
                $sub->whereIn('p.category_id', $priceChangeCategoryIds)
                    ->orWhereIn('p.sub_category_id', $priceChangeCategoryIds);
            })
            ->sum('fd.' . $amountColumn);

        return [
            'amount' => $amount,
            'form_numbers' => 'F17-' . $lastF17->form_no,
        ];
    }

    private function getF17HeaderSummary(int $businessId, string $fromDate, string $toDate, string $selectMode, $locationId = null): array
    {
        if (empty($fromDate) || empty($toDate) || $fromDate > $toDate) {
            return [
                'amount' => 0.0,
                'form_numbers' => '',
            ];
        }

        $amountColumn = $selectMode === 'increase'
            ? 'form_f17_headers.total_price_change_gain'
            : 'form_f17_headers.total_price_change_loss';

        $headers = FormF17Header::query()
            ->where('business_id', $businessId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->when(!empty($locationId), function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->whereExists(function ($query) use ($selectMode) {
                $query->select(DB::raw(1))
                    ->from('form_f17_details')
                    ->whereColumn('form_f17_details.header_id', 'form_f17_headers.id')
                    ->where('form_f17_details.select_mode', $selectMode);
            });

        $amount = (float) (clone $headers)->sum($amountColumn);

        $formNumbers = (clone $headers)
            ->orderBy('form_no')
            ->pluck('form_no')
            ->unique()
            ->map(function ($formNo) {
                return 'F17-' . $formNo;
            })
            ->implode(', ');

        return [
            'amount' => $amount,
            'form_numbers' => $formNumbers,
        ];
    }

    public function getF15Categories(Request $request)
    {
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        
        $categories = Category::where('business_id', $business_id)->orderBy('name', 'asc')->get();
        $categories_formatted = [];
        foreach ($categories as $cat) {
            if ($cat->parent_id != 0) {
                $parent = $categories->firstWhere('id', $cat->parent_id);
                $name = ($parent ? $parent->name : 'Parent') . ' > ' . $cat->name;
            } else {
                $name = $cat->name;
            }
            $categories_formatted[] = [
                'id' => $cat->id,
                'name' => $name
            ];
        }
        
        usort($categories_formatted, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $selection_id = $request->input('id');
        if (!empty($selection_id)) {
            $selection = MpcsF15CategorySelection::where('business_id', $business_id)->find($selection_id);
        } else {
            $selection = MpcsF15CategorySelection::where('business_id', $business_id)->latest()->first();
        }

        $selected_ids = $selection ? ($selection->category_ids ?? []) : [];

        return response()->json([
            'all_categories' => $categories_formatted,
            'selected_ids' => $selected_ids,
            'selection_id' => $selection ? $selection->id : null
        ]);
    }

    public function saveF15Categories(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
            $category_ids = $request->input('category_ids', []);
            $selection_id = $request->input('selection_id');

            if (!empty($selection_id)) {
                $selection = MpcsF15CategorySelection::where('business_id', $business_id)->find($selection_id);
                if ($selection) {
                    $selection->category_ids = $category_ids;
                    $selection->save();
                } else {
                    $selection = MpcsF15CategorySelection::create([
                        'business_id' => $business_id,
                        'category_ids' => $category_ids,
                        'created_by' => auth()->user()->id
                    ]);
                }
            } else {
                $selection = MpcsF15CategorySelection::create([
                    'business_id' => $business_id,
                    'category_ids' => $category_ids,
                    'created_by' => auth()->user()->id
                ]);
            }

            return response()->json([
                'success' => true,
                'msg' => 'Category selections saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to save category selections: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getF15CategorySelections(Request $request)
    {
        if ($request->ajax()) {
            $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

            $selections = MpcsF15CategorySelection::with(['creator'])
                ->where('business_id', $business_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return DataTables::of($selections)
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-primary btn-xs edit-category-selection" data-id="' . $row->id . '"><i class="fa fa-edit"></i> Edit</button>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('selected_categories', function ($row) {
                    $category_ids = $row->category_ids ?? [];
                    if (empty($category_ids)) {
                        return 'None';
                    }
                    $categories = Category::whereIn('id', $category_ids)->pluck('name')->toArray();
                    return implode(', ', $categories);
                })
                ->addColumn('username', function ($row) {
                    return $row->creator ? $row->creator->username : 'N/A';
                })
                ->rawColumns(['action', 'selected_categories'])
                ->make(true);
        }
    }
    /**
     * Resolve the active business consistently for tenant-scoped requests.
     */
    private function resolveBusinessId(Request $request)
    {
        return $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? $request->session()->get('business.id');
    }

    /**
     * F15 is a single-date report. Accept the system date format and also
     * tolerate an old date-range value by taking its first date.
     */
    private function resolveF15Date($value): Carbon
    {
        $raw = trim((string) ($value ?: date('Y-m-d')));

        if (preg_match('/^\s*(\d{4}-\d{2}-\d{2})/', $raw, $match)) {
            return Carbon::createFromFormat('Y-m-d', $match[1])->startOfDay();
        }

        $parts = preg_split('/\s*(?:~|\bto\b|\s+-\s+)\s*/i', $raw);
        $candidate = trim((string) ($parts[0] ?? $raw));

        foreach (['m/d/Y', 'd/m/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $candidate);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable $e) {
                // Try the next supported format.
            }
        }

        try {
            return Carbon::parse($candidate)->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

}
