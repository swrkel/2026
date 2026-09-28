<?php
namespace Modules\MPCS\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\Account;
use App\Utils\BusinessUtil;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\Mpcs9aFormSettings;
use Modules\MPCS\Entities\Mpcs9aFormTextDetail;
use Yajra\DataTables\Facades\DataTables;

class Form9ASettingsController extends Controller
{

    /**
     * Sum direct/Petro settlement credit sales for F9A by the actual report date.
     *
     * Previous-date direct settlements may be saved later, so created_at cannot be
     * used for F9A.  Match the selected F9A date against the credit order date
     * and the parent settlement transaction date.
     */
    private function getF9ADirectSettlementCreditTotal($business_id, $date, $sub_category_id, $location_id = null)
    {
        $hasSettlementNoColumn = Schema::hasColumn('settlements', 'settlement_no');
        $hasScspLocationColumn = Schema::hasColumn('settlement_credit_sale_payments', 'location_id');
        $hasSettlementLocationColumn = Schema::hasColumn('settlements', 'location_id');

        return (float) DB::table('settlement_credit_sale_payments as scsp')
            ->join('products as p', 'scsp.product_id', '=', 'p.id')
            ->leftJoin('settlements as st', function ($join) use ($hasSettlementNoColumn) {
                $join->on('st.id', '=', 'scsp.settlement_no');
                if ($hasSettlementNoColumn) {
                    $join->orOn('st.settlement_no', '=', 'scsp.settlement_no');
                }
            })
            ->where('scsp.business_id', $business_id)
            ->where('p.sub_category_id', $sub_category_id)
            ->when(!empty($location_id) && ($hasScspLocationColumn || $hasSettlementLocationColumn), function ($q) use ($location_id, $hasScspLocationColumn, $hasSettlementLocationColumn) {
                $q->where(function ($qq) use ($location_id, $hasScspLocationColumn, $hasSettlementLocationColumn) {
                    if ($hasSettlementLocationColumn) {
                        $qq->orWhere('st.location_id', $location_id);
                    }
                    if ($hasScspLocationColumn) {
                        $qq->orWhere('scsp.location_id', $location_id);
                    }
                });
            })
            ->where(function ($q) use ($date) {
                $q->whereDate('scsp.order_date', $date)
                  ->orWhereDate('st.transaction_date', $date);
            })
            ->sum(DB::raw('COALESCE(scsp.amount, scsp.qty * scsp.price, 0)'));
    }

    private function getF9ADirectSettlementCreditTotalBefore($business_id, $from_date, $to_date, $sub_category_id, $location_id = null)
    {
        $hasSettlementNoColumn = Schema::hasColumn('settlements', 'settlement_no');
        $hasScspLocationColumn = Schema::hasColumn('settlement_credit_sale_payments', 'location_id');
        $hasSettlementLocationColumn = Schema::hasColumn('settlements', 'location_id');

        return (float) DB::table('settlement_credit_sale_payments as scsp')
            ->join('products as p', 'scsp.product_id', '=', 'p.id')
            ->leftJoin('settlements as st', function ($join) use ($hasSettlementNoColumn) {
                $join->on('st.id', '=', 'scsp.settlement_no');
                if ($hasSettlementNoColumn) {
                    $join->orOn('st.settlement_no', '=', 'scsp.settlement_no');
                }
            })
            ->where('scsp.business_id', $business_id)
            ->where('p.sub_category_id', $sub_category_id)
            ->when(!empty($location_id) && ($hasScspLocationColumn || $hasSettlementLocationColumn), function ($q) use ($location_id, $hasScspLocationColumn, $hasSettlementLocationColumn) {
                $q->where(function ($qq) use ($location_id, $hasScspLocationColumn, $hasSettlementLocationColumn) {
                    if ($hasSettlementLocationColumn) {
                        $qq->orWhere('st.location_id', $location_id);
                    }
                    if ($hasScspLocationColumn) {
                        $qq->orWhere('scsp.location_id', $location_id);
                    }
                });
            })
            ->where(function ($q) use ($from_date, $to_date) {
                $rangeStart = $from_date.' 00:00:00';
                $rangeEnd = $to_date.' 23:59:59';

                $q->whereBetween('scsp.order_date', [$rangeStart, $rangeEnd])
                  ->orWhereBetween('st.transaction_date', [$rangeStart, $rangeEnd]);
            })
            ->sum(DB::raw('COALESCE(scsp.amount, scsp.qty * scsp.price, 0)'));
    }

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->middleware('web');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? request()->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');

        $header = Mpcs9aFormSettings::where('business_id', $business_id)->select('*');
        if (request()->ajax()) {
            return Datatables::of($header)
                ->removeColumn('id')
                ->removeColumn('business_id')
                ->removeColumn('date')
                ->removeColumn('ref_pre_form_number')
                ->removeColumn('created_at')
                ->removeColumn('updated_at')
                ->editColumn('action', function ($row) {
                    /*
                     * IS2038 (third report of IS2016 / IS2025): Edit is disabled
                     * once the settings have been saved.
                     *
                     * THIS is the controller the F16A settings table actually
                     * uses. form_9a.blade.php points the datatable at
                     * /mpcs/get-form-9a-settings, which routes to
                     * Form9ASettingsController@index - not to
                     * Form16ASettingsController, which is where the two previous
                     * attempts at this ticket were applied. That code was never
                     * executed by this screen, which is why the issue kept coming
                     * back after each "fix".
                     *
                     * The superadmin exception is also removed. The ticket asks
                     * for the button to be disabled after saving, without
                     * qualification, and the person testing is a superadmin - so
                     * the exception was the visible failure both times.
                     *
                     * The row existing IS the saved state. These settings drive
                     * F16A form numbering, so editing them after forms are issued
                     * would renumber or collide with documents already sent out;
                     * a correction should be a considered database action, not a
                     * stray click.
                     */
                    return '<button type="button" class="btn btn-primary btn-xs" disabled
                            title="' . e(__("messages.edit")) . ' is disabled once the settings have been saved."><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</button>';
                })
                ->editColumn('total_sale_to_pre', fn($row) => number_format((float)$row->total_sale_to_pre, 2))
                ->editColumn('pre_day_cash_sale', fn($row) => number_format((float)$row->pre_day_cash_sale, 2))
                ->editColumn('pre_day_card_sale', fn($row) => number_format((float)$row->pre_day_card_sale, 2))
                ->editColumn('pre_day_credit_sale', fn($row) => number_format((float)$row->pre_day_credit_sale, 2))
                ->editColumn('pre_day_cash', fn($row) => number_format((float)$row->pre_day_cash, 2))
                ->editColumn('pre_day_cheques', fn($row) => number_format((float)$row->pre_day_cheques, 2))
                ->editColumn('pre_day_total', fn($row) => number_format((float)$row->pre_day_total, 2))
                ->editColumn('pre_day_balance', fn($row) => number_format((float)$row->pre_day_balance, 2))
                ->editColumn('pre_day_grand_total', fn($row) => number_format((float)$row->pre_day_grand_total, 2))
                ->rawColumns(['action'])
                ->make(true);
        }
        $settings = Mpcs9aFormSettings::where('business_id', $business_id)->first();
        $business_locations = BusinessLocation::forDropdown($business_id);

        Log::info('business locations in form 9A: ' . $business_locations);

        $location_id = request()->get('form_9a_location_id') ?? $business_locations->keys()->first();

        Log::info('location_id in form 9A: ' . $location_id);

        $location = BusinessLocation::find($location_id);

        Log::info('locations in form 9A: ' . $location);

        $form_number = $settings->starting_number ?? 0;

        // Load Business Bank Accounts (flagged on account as Business Bank Account)
        $business_bank_accounts = Account::where('business_id', $business_id)
            ->where('is_business_bank_account', 1)
            ->orderBy('name')
            ->get();

        // Load Card Accounts (children of Cards (Credit Debit) Account, if present)
        $card_group_rec = DB::table('account_groups')->where('business_id', $business_id)->where('name', 'Card')->first();
        $card_asset_type = $card_group_rec ? $card_group_rec->id : 7;
        $card_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_asset_type)
            ->where('is_main_account', 0)
            ->orderBy('name')
            ->get();

        $bank_manual = [];
        $card_manual = [];
        if (!empty($settings)) {
            $bank_manual = json_decode($settings->pre_day_bank_manual ?? '[]', true) ?: [];
            $card_manual = json_decode($settings->pre_day_card_manual ?? '[]', true) ?: [];
        }

        $date = date('Y-m-d');
        $name = auth()->user()->first_name;
        $form_settings = $settings;

        return view('mpcs::forms.form_9a')->with(compact(
            'business_locations',
            'settings',
            'form_settings',
            'date',
            'name',
            'location',
            'form_number',
            'business_bank_accounts',
            'card_accounts',
            'bank_manual',
            'card_manual'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->orderBy('name')
            ->get();

        $latestForm = Mpcs9aFormSettings::latest()->first();
        $business_bank_accounts = Account::where('business_id', $business_id)
            ->where('is_business_bank_account', 1)
            ->orderBy('name')
            ->get();

        $card_group_rec = DB::table('account_groups')->where('business_id', $business_id)->where('name', 'Card')->first();
        $card_asset_type = $card_group_rec ? $card_group_rec->id : 7;
        $card_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_asset_type)
            ->where('is_main_account', 0)
            ->orderBy('name')
            ->get();

        $bank_manual = [];
        $card_manual = [];

        return view('mpcs::forms.partials.create_9a_form_settings', compact(
            'latestForm',
            'sub_categories',
            'business_bank_accounts',
            'card_accounts',
            'bank_manual',
            'card_manual'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        $data = [
            'business_id' => $business_id,
            'date' => $request->input('datepicker'),
            'starting_number' => $request->input('form_starting_number'),
            'ref_pre_form_number' => $request->input('ref_previous_form_number'),
            'total_sale_to_pre' => $request->input('total_sale_up_to_previous_day'),
            'pre_day_cash_sale' => $request->input('previous_day_cash_sale'),
            'pre_day_card_sale' => $request->input('previous_day_card_sale'),
            'pre_day_credit_sale' => $request->input('previous_day_credit_sale'),
            'pre_day_cash' => $request->input('previous_day_cash'),
            'pre_day_cheques' => $request->input('previous_day_cheques_cards'),
            'pre_day_bank_manual' => json_encode($request->input('pre_day_bank_accounts', [])),
            'pre_day_card_manual' => json_encode($request->input('pre_day_card_accounts', [])),
            'pre_day_total' => $request->input('previous_day_total'),
            'pre_day_balance' => $request->input('previous_day_balance_in_hand'),
            'pre_day_grand_total' => $request->input('previous_day_grand_total'),
            'no_of_rows_per_page' => $request->input('no_of_rows_to_show_per_page'),
            'sub_categories_data' => json_encode($request->input('sub_categories', [])),
            'created_at' => date('Y-m-d H:i'),
            'updated_at' => date('Y-m-d H:i'),
        ];

        Mpcs9aFormSettings::insertGetId($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_9a_settings_add_success'),
        ];

        return response()->json($output);
    }
    public function TextDetailstore(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        // Validate the request data
        $validated = $request->validate([
            'text_content' => 'required|string',
            'form' => 'required|string', // For updates
            'id' => 'sometimes|integer', // For updates
        ]);

        // Prepare the data for create/update
        $textData = [
            'business_id' => $business_id,
            'text_content' => $validated['text_content'],
            'form' => $validated['form'],
        ];

        try {
            if ($request->has('id')) {
                // Update existing record
                $textDetail = Mpcs9aFormTextDetail::findOrFail($validated['id']);
                $textDetail->update($textData);
            }
            else {
                // Create new record
                $textDetail = Mpcs9aFormTextDetail::create($textData);
            }

            return response()->json([
                'success' => true,
                'message' => $request->has('id') ? 'Text updated successfully' : 'Text saved successfully',
                'data' => $textDetail,
            ]);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving text: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function TextDetailget()
    {
        $textDetails = Mpcs9aFormTextDetail::all();
        return response()->json([
            'success' => true,
            'data' => $textDetails,
        ]);
    }
    public function TextDetailedit(Request $request)
    {

        $textDetail = Mpcs9aFormTextDetail::find($request->id);
        return response()->json($textDetail);
    }

    public function delete(Request $request)
    {
        Mpcs9aFormTextDetail::find($request->id)->delete();
        return response()->json(['success' => true]);
    }
    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $settings = Mpcs9aFormSettings::where('business_id', $business_id)->where('id', $id)->first();
        $business_bank_accounts = Account::where('business_id', $business_id)
            ->where('is_business_bank_account', 1)
            ->orderBy('name')
            ->get();

        $card_group_rec = DB::table('account_groups')->where('business_id', $business_id)->where('name', 'Card')->first();
        $card_asset_type = $card_group_rec ? $card_group_rec->id : 7;
        $card_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_asset_type)
            ->where('is_main_account', 0)
            ->orderBy('name')
            ->get();

        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->orderBy('name')
            ->get();
        $sub_categories_data = json_decode($settings->sub_categories_data ?? '[]', true) ?: [];

        $bank_manual = json_decode($settings->pre_day_bank_manual ?? '[]', true) ?: [];
        $card_manual = json_decode($settings->pre_day_card_manual ?? '[]', true) ?: [];

        return view('mpcs::forms.partials.edit_9a_form_settings')->with(compact(
            'settings',
            'business_bank_accounts',
            'card_accounts',
            'bank_manual',
            'card_manual',
            'sub_categories',
            'sub_categories_data'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $data = [
            'business_id' => $business_id,
            'date' => $request->input('datepicker'),
            'starting_number' => $request->input('form_starting_number'),
            'ref_pre_form_number' => $request->input('ref_previous_form_number'),
            'total_sale_to_pre' => $request->input('total_sale_up_to_previous_day'),
            'pre_day_cash_sale' => $request->input('previous_day_cash_sale'),
            'pre_day_card_sale' => $request->input('previous_day_card_sale'),
            'pre_day_credit_sale' => $request->input('previous_day_credit_sale'),
            'pre_day_cash' => $request->input('previous_day_cash'),
            'pre_day_cheques' => $request->input('previous_day_cheques_cards'),
            'pre_day_bank_manual' => json_encode($request->input('pre_day_bank_accounts', [])),
            'pre_day_card_manual' => json_encode($request->input('pre_day_card_accounts', [])),
            'pre_day_total' => $request->input('previous_day_total'),
            'pre_day_balance' => $request->input('previous_day_balance_in_hand'),
            'pre_day_grand_total' => $request->input('previous_day_grand_total'),
            'no_of_rows_per_page' => $request->input('no_of_rows_to_show_per_page'),
            'sub_categories_data' => json_encode($request->input('sub_categories', [])),
            'updated_at' => date('Y-m-d H:i'),
        ];

        Mpcs9aFormSettings::where('id', $id)->update($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_9a_settings_update_success'),
        ];

        return response()->json($output);
    }
    public function get9AForm(Request $request)
    {
        $business_id = $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? $request->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');
        $location_id = $request->input('form_9a_location_id');
        $date = $request->input('start_date', $request->input('selected_date'));
        $date_obj = Carbon::parse($date);

        // 1. Get location and settings
        $location = BusinessLocation::find($location_id);
        $settings = Mpcs9aFormSettings::where('business_id', $business_id)
            ->orderBy('date', 'desc')
            ->first();

        // 2. Check for F22 Reset
        $is_f22_date = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->when(!empty($location_id), fn($q) => $q->where('location_id', $location_id))
            ->whereDate('form_date', $date)
            ->exists();

        $is_opening_date = $settings && $date_obj->isSameDay(Carbon::parse($settings->date));

        // 3. Get all sub-categories
        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->orderBy('name')
            ->get();

        // 4. Get Reset Date and Type
        $opening_date = $settings ? $settings->date : null;
        $reset_info = $this->getLastResetInfo($business_id, $location_id, $date, $opening_date);
        $reset_date = $reset_info['date'];
        $is_opening_reset = $reset_info['type'] == 'opening';

        // 5. Build Sub-Categories Data
        $sub_categories_data = [];
        $total_row1 = 0;
        $total_row2 = 0;
        $total_row3 = 0;
        $total_row4 = 0;
        $total_row5 = 0;
        $total_row6 = 0;
        $total_row7 = 0;

        $settings_subcats = [];
        if ($settings && $settings->sub_categories_data) {
            $settings_subcats = json_decode($settings->sub_categories_data, true) ?: [];
        }

        foreach ($sub_categories as $index => $cat) {
            // Get today's income and credit
            $today_sales = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('t.deleted_at')
                ->whereNull('tsl.deleted_at')
                ->where('p.sub_category_id', $cat->id)
                ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
                ->whereBetween('t.transaction_date', [$date_obj->copy()->startOfDay(), $date_obj->copy()->endOfDay()])
                ->selectRaw("
                    SUM(tsl.unit_price_inc_tax * tsl.quantity) as income,
                    SUM(CASE
                        WHEN (t.is_credit_sale = 1
                              OR t.payment_status IN ('due','partial')
                              OR EXISTS (
                                  SELECT 1 FROM transaction_payments tp2
                                  WHERE tp2.transaction_id = t.id
                                    AND tp2.method = 'credit_sale'
                                    AND tp2.deleted_at IS NULL
                              ))
                        THEN tsl.unit_price_inc_tax * tsl.quantity
                        ELSE 0
                    END) as credit
                ")
                ->first();

            // Row 1: Cash Today = Income - Credit
            $row1 = (float)($today_sales->income ?? 0) - (float)($today_sales->credit ?? 0);
            // Row 2: Credit Today = transaction credit + direct/Petro settlement credit for selected F9A date.
            $petro_credit_today = $this->getF9ADirectSettlementCreditTotal($business_id, $date, $cat->id, $location_id);
            $row2 = (float)($today_sales->credit ?? 0) + $petro_credit_today;

            // Row 3 & 4 (Previous Day)
            $row3 = 0;
            $row4 = 0;

            if ($is_f22_date) {
                $row3 = 0;
                $row4 = 0;
            }
            elseif ($is_opening_date) {
                // Find matching setting
                foreach ($settings_subcats as $s_cat) {
                    if (isset($s_cat['id']) && $s_cat['id'] == $cat->id) {
                        $row3 = (float)($s_cat['cash_previous_day'] ?? ($s_cat['sales_previous_day'] ?? 0));
                        $row4 = (float)($s_cat['credit_previous_day'] ?? ($s_cat['receipts_previous_day'] ?? 0));
                        break;
                    }
                }
            }
            else {
                // Normal dates: Cumulative from most recent reset up to yesterday
                $cumulative_prev = DB::table('transactions as t')
                    ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                    ->join('products as p', 'tsl.product_id', '=', 'p.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'sell')
                    ->where('t.status', 'final')
                    ->whereNull('t.deleted_at')
                    ->whereNull('tsl.deleted_at')
                    ->where('p.sub_category_id', $cat->id)
                    ->when(!empty($location_id), fn($q) => $q->where('t.location_id', $location_id))
                    ->whereDate('t.transaction_date', '>=', $reset_date)
                    ->whereDate('t.transaction_date', '<', $date)
                    ->selectRaw("
                        SUM(tsl.unit_price_inc_tax * tsl.quantity) AS total_income,
                        SUM(CASE
                            WHEN (t.is_credit_sale = 1
                                  OR t.payment_status IN ('due','partial')
                                  OR EXISTS (
                                      SELECT 1 FROM transaction_payments tp2
                                      WHERE tp2.transaction_id = t.id
                                        AND tp2.method = 'credit_sale'
                                        AND tp2.deleted_at IS NULL
                                  ))
                            THEN tsl.unit_price_inc_tax * tsl.quantity
                            ELSE 0
                        END) AS total_credit
                    ")
                    ->first();

                $sum_income = (float)($cumulative_prev->total_income ?? 0);
                $sum_credit = (float)($cumulative_prev->total_credit ?? 0);
                $sum_cash = $sum_income - $sum_credit;

                // Add unlinked Petro credit sales for the cumulative previous period
                $petro_credit_prev = (float) DB::table('settlement_credit_sale_payments as scsp')
                    ->join('products as p', 'scsp.product_id', '=', 'p.id')
                    ->where('scsp.business_id', $business_id)
                    ->where('p.sub_category_id', $cat->id)
                    ->whereNull('scsp.transaction_id')
                    ->whereDate('scsp.order_date', '>=', $reset_date)
                    ->whereDate('scsp.order_date', '<', $date)
                    ->sum(DB::raw('scsp.qty * scsp.price'));

                if ($is_opening_reset) {
                    $setting_row3 = 0;
                    $setting_row4 = 0;
                    foreach ($settings_subcats as $s_cat) {
                        if (isset($s_cat['id']) && $s_cat['id'] == $cat->id) {
                            $setting_row3 = (float)($s_cat['cash_previous_day'] ?? ($s_cat['sales_previous_day'] ?? 0));
                            $setting_row4 = (float)($s_cat['credit_previous_day'] ?? ($s_cat['receipts_previous_day'] ?? 0));
                            break;
                        }
                    }
                    $row3 = $setting_row3 + $sum_cash;
                    $row4 = $setting_row4 + $sum_credit + $petro_credit_prev;
                }
                else {
                    $row3 = $sum_cash;
                    $row4 = $sum_credit + $petro_credit_prev;
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
            $total_row1 += $row1;
            $total_row2 += $row2;
            $total_row3 += $row3;
            $total_row4 += $row4;
            $total_row5 += $row5;
            $total_row6 += $row6;
            $total_row7 += $row7;
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

        // 7. Manual and Extra Payments Logic
        $payments = [
            'cash' => [
                'prev' => (float)($settings->pre_day_cash ?? 0),
                'today' => 0,
                'total' => (float)($settings->pre_day_cash ?? 0)
            ],
            'cheques' => [
                'prev' => (float)($settings->pre_day_cheques ?? 0),
                'today' => 0,
                'total' => (float)($settings->pre_day_cheques ?? 0)
            ],
            'cards' => [],
            'banks' => [],
            'other' => ['prev' => 0, 'today' => 0, 'total' => 0],
            'pre_day_balance' => (float)($settings->pre_day_balance ?? 0),
            'pre_day_grand_total' => (float)($settings->pre_day_grand_total ?? 0),
        ];

        // Add Card Accounts
        $card_group_rec = DB::table('account_groups')->where('business_id', $business_id)->where('name', 'Card')->first();
        $card_asset_type = $card_group_rec ? $card_group_rec->id : 7;
        $card_accounts = Account::where('business_id', $business_id)->where('asset_type', $card_asset_type)->where('is_main_account', 0)->get();
        $card_manual = $settings ? json_decode($settings->pre_day_card_manual, true) : [];
        foreach ($card_accounts as $acc) {
            $prev = (float)($card_manual[$acc->id] ?? 0);
            $payments['cards'][] = ['name' => $acc->name, 'prev' => $prev, 'today' => 0, 'total' => $prev];
        }

        // Add Bank Accounts
        $bank_accounts = Account::where('business_id', $business_id)->where('is_business_bank_account', 1)->get();
        $bank_manual = $settings ? json_decode($settings->pre_day_bank_manual, true) : [];
        foreach ($bank_accounts as $acc) {
            $prev = (float)($bank_manual[$acc->id] ?? 0);
            $payments['banks'][] = ['name' => $acc->name, 'prev' => $prev, 'today' => 0, 'total' => $prev];
        }

        return response()->json([
            'form_number' => $this->getFormNumber($business_id, $date),
            'location' => [
                'name' => $location->name ?? '',
            ],
            'sub_categories_data' => $sub_categories_data,
            'total_row1' => $total_row1,
            'total_row2' => $total_row2,
            'total_row3' => $total_row3,
            'total_row4' => $total_row4,
            'total_row5' => $total_row5,
            'total_row6' => $total_row6,
            'total_row7' => $total_row7,
            'receipts_data' => $receipts_data,
            'payments' => $payments,
            'text_details' => Mpcs9aFormTextDetail::where('business_id', $business_id)->where('form', $this->getFormNumber($business_id, $date))->first()
        ]);
    }

    private function getFormNumber($business_id, $date)
    {
        $date = Carbon::parse($date);

        $setting = Mpcs9aFormSettings::where('business_id', $business_id)
            ->orderBy('date')
            ->first();

        if (!$setting) {
            return null;
        }

        // Simple form number calculation: starting number + days difference from opening date
        $opening_date = Carbon::parse($setting->date);
        $days_diff = $opening_date->diffInDays($date, false);

        return (int)$setting->starting_number + (int)$days_diff;
    }

    private function getPreviousDayData($business_id, $date)
    {
        $date = Carbon::parse($date);

        // First day of month → everything zero
        if ($date->day === 1) {
            return (object)[
                'cash_sale' => 0,
                'card_sale' => 0,
                'credit_sale' => 0,
                'total_sale_as_of_today' => 0,
                'cash_receipt' => 0,
                'card_receipt' => 0,
                'cash_payment' => 0,
                'card_payment' => 0,
                'cheque_payment' => 0,
            ];
        }

        return DB::table('mpcs_9a_forms')
            ->where('business_id', $business_id)
            ->whereDate('date', '<', $date)
            ->orderBy('date', 'desc')
            ->first();
    }

    private function getTodaySales($business_id, $date)
    {
        // Cash and Card sales come from transaction_payments.method
        $cash_card_sales = DB::table('transactions')
            ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->whereDate('transactions.transaction_date', $date)
            ->selectRaw("
                SUM(CASE WHEN transaction_payments.method = 'cash' THEN transaction_payments.amount ELSE 0 END) AS cash_sale,
                SUM(CASE WHEN transaction_payments.method = 'card' THEN transaction_payments.amount ELSE 0 END) AS card_sale
            ")
            ->first();

        // Credit sales use is_credit_sale flag
        $credit_sale = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('is_credit_sale', 1)
            ->whereDate('transaction_date', $date)
            ->sum('final_total');

        return (object)[
            'cash_sale' => $cash_card_sales->cash_sale ?? 0,
            'card_sale' => $cash_card_sales->card_sale ?? 0,
            'credit_sale' => $credit_sale ?? 0,
        ];
    }

    private function getReceiptsPayments($business_id, $date)
    {
        $previous = $this->getPreviousDayData($business_id, $date);

        $manual_cash = request('manual_cash', 0);
        $manual_card = request('manual_card', 0);
        $manual_other = request('manual_other', 0);

        // Image 30
        $manual_total = $manual_cash + $manual_card;

        return [

            // RECEIPTS
            'receipt_cash_previous' => $previous->cash_receipt ?? 0, // 11
            'receipt_card_previous' => $previous->card_receipt ?? 0, // 12
            'receipt_cash_today' => $manual_cash, // 13
            'receipt_card_today' => $manual_card, // 14
            'receipt_cash_total' => ($previous->cash_receipt ?? 0) + $manual_cash, // 15
            'receipt_card_total' => ($previous->card_receipt ?? 0) + $manual_card, // 16
            'receipt_prev_total' => $previous->total_sale_as_of_today ?? 0, // 17
            'receipt_credit_today' => $previous->credit_sale ?? 0, // 18
            'receipt_as_of_today' =>
            ($previous->total_sale_as_of_today ?? 0) +
            ($previous->credit_sale ?? 0), // 19

            // PAYMENTS
            'payment_cash_previous' => $previous->cash_payment ?? 0, // 22
            'payment_card_previous' => $previous->card_payment ?? 0, // 23
            'payment_cheque_previous' => $previous->cheque_payment ?? 0, // 24
            'payment_manual_cash' => $manual_cash, // 27
            'payment_manual_card' => $manual_card, // 28
            'payment_manual_other' => $manual_other, // 29
            'payment_manual_total' => $manual_total, // 30
            'payment_cash_total' => ($previous->cash_payment ?? 0) + $manual_cash, // 31
            'payment_card_total' => ($previous->card_payment ?? 0) + $manual_card, // 32
            'payment_grand_total' =>
            (($previous->cash_payment ?? 0) + $manual_cash) +
            (($previous->card_payment ?? 0) + $manual_card), // 33
            'payment_balance' =>
            ($previous->total_sale_as_of_today ?? 0) -
            (
            (($previous->cash_payment ?? 0) + $manual_cash) +
            (($previous->card_payment ?? 0) + $manual_card)
        ), // 34
        ];
    }

    /**
     * Get today's sales breakdown by sub-category
     */
    private function getSubcategorySales($business_id, $date, $sub_categories)
    {
        $sales_breakdown = [];

        foreach ($sub_categories as $category) {
            // Get cash sales: sum line totals for products in this sub-category
            // where transaction has cash payment
            $cash_sale = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->join('transaction_payments as tp', function ($join) {
                $join->on('t.id', '=', 'tp.transaction_id')
                    ->where('tp.method', '=', 'cash');
            })
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.is_credit_sale', 0)
                ->where('p.sub_category_id', $category->id)
                ->whereDate('t.transaction_date', $date)
                ->selectRaw('SUM(
                    (tsl.unit_price_inc_tax * tsl.quantity) * 
                    (tp.amount / t.final_total)
                ) as total')
                ->value('total');

            // Get card sales: sum line totals for products in this sub-category
            // where transaction has card payment
            $card_sale = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->join('transaction_payments as tp', function ($join) {
                $join->on('t.id', '=', 'tp.transaction_id')
                    ->where('tp.method', '=', 'card');
            })
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.is_credit_sale', 0)
                ->where('p.sub_category_id', $category->id)
                ->whereDate('t.transaction_date', $date)
                ->selectRaw('SUM(
                    (tsl.unit_price_inc_tax * tsl.quantity) * 
                    (tp.amount / t.final_total)
                ) as total')
                ->value('total');

            // Get credit sales using is_credit_sale flag
            $credit_sale = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.is_credit_sale', 1)
                ->where('p.sub_category_id', $category->id)
                ->whereDate('t.transaction_date', $date)
                ->selectRaw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as total')
                ->value('total');

            $sales_breakdown[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'cash_sale' => (float)($cash_sale ?? 0),
                'card_sale' => (float)($card_sale ?? 0),
                'credit_sale' => (float)($credit_sale ?? 0),
            ];
        }

        return $sales_breakdown;
    }

    /**
     * Get previous day's cumulative sales by sub-category
     */
    private function getSubcategoryPreviousDaySales($business_id, $date, $sub_categories, $settings = null)
    {
        $date = Carbon::parse($date);

        // First day of month OR opening date → use settings values
        if ($date->day === 1 || ($settings && $date->isSameDay(Carbon::parse($settings->date)))) {
            // Parse settings sub_categories_data
            $settingsData = [];
            if ($settings && $settings->sub_categories_data) {
                $settingsData = json_decode($settings->sub_categories_data, true) ?? [];
            }

            return collect($sub_categories)->map(function ($cat, $index) use ($settingsData) {
                $salesPrevDay = 0;

                // Find matching settings data by category ID
                foreach ($settingsData as $settingsCat) {
                    if (isset($settingsCat['id']) && $settingsCat['id'] == $cat->id) {
                        $salesPrevDay = $settingsCat['sales_previous_day'] ?? 0;
                        break;
                    }
                }

                return [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'total_sale' => $salesPrevDay,
                ];
            })->toArray();
        }

        $sales_breakdown = [];

        foreach ($sub_categories as $category) {
            $sales = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('p.sub_category_id', $category->id)
                ->whereDate('t.transaction_date', '<', $date)
                ->selectRaw("
                    SUM(tsl.unit_price_inc_tax * tsl.quantity) AS total_sale
                ")
                ->first();

            // Handle null result when there are no transactions for this sub-category
            $total_sale = 0;
            if ($sales) {
                $total_sale = (float)($sales->total_sale ?? 0);
            }

            $sales_breakdown[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'total_sale' => $total_sale,
            ];
        }

        return $sales_breakdown;
    }

    /**
     * Get cumulative sales (as of today) by sub-category
     */
    private function getSubcategoryCumulativeSales($business_id, $date, $sub_categories)
    {
        $date = Carbon::parse($date);

        $sales_breakdown = [];

        foreach ($sub_categories as $category) {
            $sales = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('p.sub_category_id', $category->id)
                ->whereDate('t.transaction_date', '<=', $date)
                ->selectRaw("
                    SUM(tsl.unit_price_inc_tax * tsl.quantity) AS total_sale
                ")
                ->first();

            // Handle null result when there are no transactions for this sub-category
            $total_sale = 0;
            if ($sales) {
                $total_sale = (float)($sales->total_sale ?? 0);
            }

            $sales_breakdown[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'total_sale' => $total_sale,
            ];
        }

        return $sales_breakdown;
    }

    /**
     * Get the most recent reset point (F22 date, Opening Date, or 1st of month)
     * preceding the given date.
     */
    private function getLastResetInfo($business_id, $location_id, $date, $opening_date)
    {
        $date_obj = Carbon::parse($date);
        $start_of_month = $date_obj->copy()->startOfMonth();

        $reset_dates = collect();

        // 1. Monthly Reset
        $reset_dates->push(['date' => $start_of_month, 'type' => 'monthly', 'priority' => 1]);

        // 2. F22 Resets - Restrict to current month
        $f22_dates = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->whereDate('form_date', '<', $date)
            ->whereDate('form_date', '>=', $start_of_month)
            ->pluck('form_date');
        
        foreach ($f22_dates as $fd) {
            $reset_dates->push(['date' => Carbon::parse($fd), 'type' => 'f22', 'priority' => 2]);
        }

        // 3. Opening Date Reset - Restrict to current month
        if ($opening_date) {
            $opening_carbon = Carbon::parse($opening_date);
            if ($opening_carbon->isSameMonth($date_obj, true)) {
                $reset_dates->push(['date' => $opening_carbon, 'type' => 'opening', 'priority' => 3]);
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

        // Sort by date DESC, then by priority DESC (Opening > F22 > Monthly)
        $winner = $valid_resets->sort(function($a, $b) {
            if ($a['date']->eq($b['date'])) {
                return $b['priority'] <=> $a['priority'];
            }
            return $b['date'] <=> $a['date'];
        })->first();

        return ['date' => $winner['date'], 'type' => $winner['type']];
    }

}