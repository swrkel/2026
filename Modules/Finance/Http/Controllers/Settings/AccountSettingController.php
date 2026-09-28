<?php

namespace Modules\Finance\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountSetting;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\AccountType;
use Modules\Finance\Entities\Contact;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\TransactionPayment;
use Modules\Finance\Entities\Transaction;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Services\FinanceAccountNumberService;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AccountSettingController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // S723 #2: some production proxies do not preserve Laravel's
        // X-Requested-With header. DataTables always sends a `draw` parameter,
        // so treat that as authoritative instead of returning the full HTML view
        // to a DataTables request (which leaves the UI stuck on Processing...).
        $isDataTableRequest = $request->ajax() || $request->has('draw') || $request->expectsJson();

        // If this is the page request, return the view with necessary data.
        if (! $isDataTableRequest) {
            $business_id = request()->session()->get('user.business_id');

            // Get account types
            $account_types = AccountType::where('business_id', $business_id)
                ->whereNull('parent_account_type_id')
                ->with('sub_types')
                ->get();

            $account_types_opts = [];
            foreach ($account_types as $account_type) {
                $account_types_opts[$account_type->id] = $account_type->name;
            }

            // Get sub account types
            $sub_acn_arr = [];
            foreach ($account_types as $acunts) {
                foreach ($acunts->sub_types as $sub_Acn) {
                    $sub_acn_arr[$sub_Acn->id] = $sub_Acn->name;
                }
            }

            // Get account groups
            $account_groups = AccountGroup::where('business_id', $business_id)->pluck('name', 'id');

            // Get customers
            $customers = Contact::customersDropdown($business_id, false);

            // Get cheque ID
            $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');

            // Get can edit permission
            $can_edit_ob = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_ob');

            return view('finance::account_settings.index')
                ->with(compact('account_types_opts', 'sub_acn_arr', 'account_groups', 'customers', 'chequeId', 'can_edit_ob'));
        }

        if ($isDataTableRequest) {
            return $this->data($request);
        }
    }

    /**
     * Return a deterministic, read-only DataTables response for List Accounts.
     *
     * This endpoint intentionally does not use Yajra's automatic SQL rewriting.
     * The table contains computed parent/sub-type columns and older versions of
     * the package generated an invalid count/order query for those aliases on
     * some MySQL versions, leaving the browser on "Processing..." indefinitely.
     */
    public function data(Request $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $draw = (int) $request->input('draw', 0);

        if ($business_id <= 0) {
            return $this->emptyDataTableResponse($draw);
        }

        try {
            $total = DB::table('account_settings')
                ->where('business_id', $business_id)
                ->count();

            $settings = DB::table('account_settings')
                ->leftJoin('account_groups', 'account_settings.group_id', '=', 'account_groups.id')
                ->leftJoin('accounts', 'account_settings.account_id', '=', 'accounts.id')
                ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
                ->leftJoin('account_types as pat', 'account_types.parent_account_type_id', '=', 'pat.id')
                ->leftJoin('users', 'account_settings.created_by', '=', 'users.id')
                ->where('account_settings.business_id', $business_id);

            if (! empty($request->date)) {
                try {
                    $date = Carbon::parse((string) $request->date)->format('Y-m-d');
                    $settings->whereDate(
                        DB::raw('COALESCE(account_settings.date, account_settings.created_at)'),
                        $date
                    );
                } catch (\Throwable $e) {
                    // Ignore an invalid optional filter; never break the table.
                }
            }

            $accountType = (int) $request->input('account_type', 0);
            if ($accountType > 0) {
                $settings->where(function ($typeQuery) use ($accountType): void {
                    $typeQuery->where('account_types.id', $accountType)
                        ->orWhere('account_types.parent_account_type_id', $accountType);
                });
            }

            $accountSubType = (int) $request->input('account_sub_type', 0);
            if ($accountSubType > 0) {
                $settings->where('accounts.account_type_id', $accountSubType);
            }

            $accountId = (int) $request->input('account_id', 0);
            if ($accountId > 0) {
                $settings->where('account_settings.account_id', $accountId);
            }

            $groupId = (int) $request->input('group_id', 0);
            if ($groupId > 0) {
                $settings->where('account_settings.group_id', $groupId);
            }

            $search = trim((string) $request->input('search.value', ''));
            if ($search !== '') {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
                $settings->where(function ($searchQuery) use ($like): void {
                    $searchQuery
                        ->where('accounts.name', 'like', $like)
                        ->orWhere('account_groups.name', 'like', $like)
                        ->orWhere('account_types.name', 'like', $like)
                        ->orWhere('pat.name', 'like', $like)
                        ->orWhere('users.username', 'like', $like);
                });
            }

            $filtered = (clone $settings)->count('account_settings.id');

            $orderColumns = [
                0 => 'account_settings.date',
                1 => 'pat.name',
                2 => 'account_types.name',
                3 => 'account_groups.name',
                4 => 'accounts.name',
                5 => 'account_settings.amount',
                6 => 'users.username',
                7 => 'account_settings.id',
            ];
            $orderIndex = (int) $request->input('order.0.column', 0);
            $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
                ? 'asc'
                : 'desc';
            $settings->orderBy($orderColumns[$orderIndex] ?? 'account_settings.id', $orderDirection)
                ->orderBy('account_settings.id', 'desc');

            $start = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 25);
            if ($length !== -1) {
                $settings->skip($start)->take(max(1, min($length, 500)));
            }

            $canEditOpeningBalance = $this->moduleUtil
                ->hasThePermissionInSubscription($business_id, 'edit_ob');

            $rows = $settings->get([
                DB::raw('COALESCE(account_settings.date, DATE(account_settings.created_at)) as date'),
                'account_groups.name as account_group',
                'accounts.name',
                'account_settings.amount',
                'users.username as created_by',
                'account_settings.id',
                'account_types.name as account_type_name',
                'pat.name as parent_account_type_name',
            ])->map(function ($row) use ($canEditOpeningBalance): array {
                $hasParentType = ! empty($row->parent_account_type_name);
                $amount = function_exists('num_format')
                    ? num_format($row->amount)
                    : number_format((float) $row->amount, 2, '.', ',');

                return [
                    'date' => ! empty($row->date) ? $this->transactionUtil->format_date($row->date) : '',
                    'parent_account_type_name' => $hasParentType
                        ? (string) $row->parent_account_type_name
                        : (string) ($row->account_type_name ?? ''),
                    'account_type_name' => $hasParentType ? (string) $row->account_type_name : '',
                    'account_group' => (string) ($row->account_group ?? ''),
                    'name' => (string) ($row->name ?? ''),
                    'amount' => $amount,
                    'created_by' => (string) ($row->created_by ?? ''),
                    'action' => $canEditOpeningBalance
                        ? '<button data-href="' . route('finance.list-accounts.live.settings.edit', ['id' => $row->id]) . '" class="btn btn-xs btn-primary finance-account-modal-trigger" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</button>'
                        : '',
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $filtered,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance Account Settings DataTable failed', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->emptyDataTableResponse(
                $draw,
                'Unable to load Account Settings. Please check the Laravel log entry: Finance Account Settings DataTable failed.'
            );
        }
    }

    private function emptyDataTableResponse(int $draw, ?string $error = null)
    {
        $payload = [
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];

        if ($error !== null) {
            $payload['error'] = $error;
        }

        return response()->json($payload, 200);
    }

    // Modified by Engr. Alex -- task 7889: get current default date range setting and history
    public function getDefaultDateRange()
    {
        $business_id = request()->session()->get('user.business_id');

        $current = DB::table('default_date_range_history')
            ->where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->first();

        $history = DB::table('default_date_range_history')
            ->join('users', 'default_date_range_history.created_by', '=', 'users.id')
            ->where('default_date_range_history.business_id', $business_id)
            ->orderBy('default_date_range_history.id', 'desc')
            ->select(
                'default_date_range_history.id',
                'default_date_range_history.date_range_label',
                'default_date_range_history.start_date',
                'default_date_range_history.end_date',
                'default_date_range_history.created_at',
                'users.username as added_user'
            )
            ->get();

        return response()->json([
            'success' => true,
            'current' => $current,
            'history' => $history,
        ]);
    }

    // Modified by Engr. Alex -- task 7889: save default date range selection (start_date, end_date, label)
    public function saveDefaultDateRange(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $start_date       = $request->input('start_date');
        $end_date         = $request->input('end_date');
        $date_range_label = $request->input('date_range_label', $start_date . ' ~ ' . $end_date);

        if (empty($start_date) || empty($end_date)) {
            return response()->json(['success' => false, 'msg' => 'Start date and end date are required.']);
        }

        DB::table('default_date_range_history')->insert([
            'business_id'      => $business_id,
            'date_range_label' => $date_range_label,
            'start_date'       => $start_date,
            'end_date'         => $end_date,
            'created_by'       => auth()->id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json([
            'success'          => true,
            'date_range_label' => $date_range_label,
            'start_date'       => $start_date,
            'end_date'         => $end_date,
            'msg'              => 'Default date range saved successfully.',
        ]);
    }


    /**
     * Task 8061: Finance-owned Account Number starting map.
     */
    public function getAccountNumbers(FinanceAccountNumberService $numbering)
    {
        if (! auth()->user() || ! auth()->user()->can('account.settings')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');

        return response()->json([
            'success' => true,
            'rows' => $numbering->mappingForBusiness($businessId),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Task 8061: save starting numbers and renumber all mapped Accounts in one
     * transaction. Account Numbers themselves are not user-editable.
     */
    public function saveAccountNumbers(Request $request, FinanceAccountNumberService $numbering)
    {
        if (! auth()->user() || ! auth()->user()->can('account.settings')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');

        try {
            $result = $numbering->saveAndRenumber(
                $businessId,
                (array) $request->input('starts', []),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'msg' => 'Account starting numbers saved. ' . (int) $result['updated_accounts'] . ' Account(s) were renumbered automatically.',
                'updated_accounts' => (int) $result['updated_accounts'],
                'rows' => $numbering->mappingForBusiness($businessId),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Finance Account Numbers save failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            DB::beginTransaction();

            $account_id = $request->account_id;
            $amount = $this->commonUtil->num_uf($request->amount);
            $date = $this->transactionUtil->uf_date($request->date) ?: Carbon::now()->format('Y-m-d');

            $cheque_data = $request->only([
                'cheque_number',
                'cheque_date',
                'bank_name',
                'customer_id',
                'cheque_amount'
            ]);

            $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
            if ((int) $account_id === (int) $chequeId) {
                $total_cheque_amount = collect($this->normaliseChequeBreakdownField($cheque_data['cheque_amount'] ?? []))
                    ->sum(function ($value) {
                        return (float) $this->commonUtil->num_uf($value);
                    });

                if (round((float) $amount, 2) !== round((float) $total_cheque_amount, 2)) {
                    throw new \Exception('006_S354: Cheque opening balance breakdown does not match the entered opening balance.');
                }
            }

            if (!empty($account_id) && !empty($amount)) {
                $this->addAccountOpeningBalance($amount, $account_id, $date, null, true, $cheque_data);
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return redirect()->back()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $account_settings = AccountSetting::find($id);

        $account_groups = AccountGroup::where('business_id', $business_id)->pluck('name', 'id');

        $accounts = Account::where('business_id', $business_id)->where('asset_type', $account_settings->group_id)->pluck('name', 'id');

        return view('finance::account_settings.edit')->with(compact(
            'account_settings',
            'account_groups',
            'accounts'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $account_settings = AccountSetting::find($id);
            $account_settings->date = !empty($request->date) ? $this->transactionUtil->uf_date($request->date) : date('Y-m-d');
            $account_settings->amount = $request->amount;
            $account_settings->account_id = $request->account_id;
            $account_settings->created_by = Auth::user()->id;
            $account_settings->save();



            $account_id = $request->account_id;
            $amount = $request->amount;
            $date = $this->transactionUtil->uf_date($request->date);
            if (!empty($account_id) && !empty($amount)) {
                if (!empty($account_id)) {
                    $this->updateAccountOpeningBalance($account_settings, $amount, $account_id, $date, null, true);
                }
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function addAccountOpeningBalance($amount, $account_id, $date = null, $note = null, $is_setting = false, $cheques_data = array())
    {

        $account_type_id = Account::where('id', $account_id)->first() ? Account::where('id', $account_id)->first()->account_type_id : 0;
        $account_type = AccountType::where('id', $account_type_id)->first();
        $account_type_name = !empty($account_type) ? $account_type->name : "";


        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $prefix_type = "sell_payment";


        $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
        $account = Account::findOrFail($account_id);
        $loans_taken_id = AccountGroup::getGroupByName('Loans Taken', true);


        if ($chequeId == $account_id) {

            $customerIds = $this->normaliseChequeBreakdownField($cheques_data['customer_id'] ?? []);
            $chequeAmounts = $this->normaliseChequeBreakdownField($cheques_data['cheque_amount'] ?? []);
            $chequeDates = $this->normaliseChequeBreakdownField($cheques_data['cheque_date'] ?? []);
            $chequeBanks = $this->normaliseChequeBreakdownField($cheques_data['bank_name'] ?? []);
            $chequeNos = $this->normaliseChequeBreakdownField($cheques_data['cheque_number'] ?? []);

            $i = 0;
            foreach ($chequeAmounts as $one) {
                if ($one === '' || $one === null) {
                    $i++;
                    continue;
                }
                $customersID = $customerIds[$i] ?? ($customerIds[0] ?? null);
                if (empty($customersID)) {
                    throw new \Exception('Customer is required for cheque opening balance breakdown.');
                }

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
                $c_amount = ($chequeAmounts[$i]);

                if ($amount > 0) {
                    $type = 'credit';
                    $main_type = 'debit';
                } else {
                    $type = 'debit';
                    $main_type = 'credit';
                }


                $ob_transaction_data = [
                    'amount' => abs($this->commonUtil->num_uf($c_amount)),
                    'account_id' => $account_id,
                    'type' => $main_type,
                    'sub_type' => 'opening_balance',
                    'operation_date' => !empty($date) ? $date : Carbon::now(),
                    'note' => !empty($note) ? $note : null,
                    'created_by' => Auth::user()->id
                ];

                $business_location = BusinessLocation::where('business_id', $business_id)
                    ->first();

                $ob_data = [

                    'business_id' => $business_id,

                    'location_id' => $business_location->id,

                    'type' => 'cheque_opening_balance',

                    'status' => 'final',

                    'payment_status' => 'paid',

                    'contact_id' => $customersID,

                    'transaction_date' => !empty($date) ? $date : Carbon::now(),

                    'total_before_tax' => $c_amount,

                    'final_total' => $c_amount,

                    'created_by' => request()->session()->get('user.id')

                ];

                $transaction = Transaction::create($ob_data);

                $cheque_TP = array(
                    "account_id" => $this->transactionUtil->account_exist_return_id('Cheques in Hand'),
                    "payment_ref_no" => $payment_ref_no,
                    "created_by" => Auth::user()->id,
                    "payment_for" => $customersID,
                    "bank_name" => $chequeBanks[$i],
                    "cheque_date" => $chequeDates[$i],
                    "cheque_number" => $chequeNos[$i],
                    "card_type" => "credit",
                    "transaction_no" => 'cheque',
                    "method" => 'cheque',
                    "amount" => $c_amount,
                    "business_id" => $business_id,
                    "transaction_id" => $transaction->id,
                    "paid_on" => !empty($date) ? $date : Carbon::now()
                );



                $tp = TransactionPayment::create($cheque_TP);


                $ob_transaction_data['transaction_payment_id'] = $tp->id;
                $ob_transaction_data['transaction_id'] = $transaction->id;
                $ob_transaction_data['sub_type'] = "ledger_show";

                $at_asset_transaction = AccountTransaction::createAccountTransaction($ob_transaction_data);

                unset($ob_transaction_data['sub_type']);

                $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');

                $ob_transaction_data['account_id'] = $opening_balance_equity_id;
                $ob_transaction_data['type'] = $type;
                $ob_transaction_data['amount'] = abs($this->commonUtil->num_uf($c_amount));
                $ob_transaction_data['pair_at_id'] = $at_asset_transaction->id;
                $at_obe_transaction = AccountTransaction::createAccountTransaction($ob_transaction_data);


                $i++;

            }


        } else {

            $amount = ($amount);


            $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');

            if ($account_type_name == "Assets" || $account_type_name == "Expenses" || $account_type_name == "Current Assets" || $account_type_name == "Fixed Assets" || $account->asset_type == $loans_taken_id) {
                if ($amount > 0) {
                    $type = 'credit';
                    $main_type = 'debit';
                } else {
                    $type = 'debit';
                    $main_type = 'credit';
                }
            } else if ($account_type_name == "Liabilities" || $account_type_name == "Equity" || $account_type_name == "Income" || $account_type_name == "Long Term Liabilities" || $account_type_name == "Current Liabilities") {
                if ($amount > 0) {
                    $type = 'debit';
                    $main_type = 'credit';
                } else {
                    $type = 'credit';
                    $main_type = 'debit';
                }
            }

            $ob_transaction_data = [
                'amount' => abs($this->commonUtil->num_uf($amount)),
                'account_id' => $account_id,
                'type' => $main_type,
                'sub_type' => 'opening_balance',
                'operation_date' => !empty($date) ? $date : Carbon::now(),
                'note' => !empty($note) ? $note : null,
                'created_by' => Auth::user()->id
            ];


            $at_asset_transaction = AccountTransaction::createAccountTransaction($ob_transaction_data);


            $ob_transaction_data['account_id'] = $opening_balance_equity_id;
            $ob_transaction_data['type'] = $type;
            $ob_transaction_data['amount'] = abs($this->commonUtil->num_uf($amount));
            $ob_transaction_data['pair_at_id'] = $at_asset_transaction->id;
            $at_obe_transaction = AccountTransaction::createAccountTransaction($ob_transaction_data);

        }




        if ($is_setting) {
            $setting_date = [
                'business_id' => $business_id,
                'date' => $date,
                'account_id' => $account_id,
                'amount' => $amount,
                'group_id' => request()->group_id,
                'at_asset_id' => $at_asset_transaction->id,
                'at_obe_id' => !empty($at_obe_transaction) ? $at_obe_transaction->id : 0,
                'created_by' => Auth::user()->id
            ];

            AccountSetting::create($setting_date);
        }

        return true;
    }

    private function normaliseChequeBreakdownField($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        return array_map('trim', explode(',', (string) $value));
    }

    public function updateAccountOpeningBalance($account_settings, $amount, $account_id, $date = null, $note = null, $is_setting = false)
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');

        $account_type_id = Account::where('id', $account_id)->first() ? Account::where('id', $account_id)->first()->account_type_id : 0;
        $account_type = AccountType::where('id', $account_type_id)->first();
        $account_type_name = !empty($account_type) ? $account_type->name : "";

        $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
        $account = Account::findOrFail($account_id);
        $loans_taken_id = AccountGroup::getGroupByName('Loans Taken', true);


        if ($chequeId == $account_id) {
            if ($amount > 0) {
                $type = 'credit';
                $main_type = 'debit';
            } else {
                $type = 'debit';
                $main_type = 'credit';
            }
        } else {
            if ($account_type_name == "Assets" || $account_type_name == "Expenses" || $account_type_name == "Current Assets" || $account_type_name == "Fixed Assets" || $account->asset_type == $loans_taken_id) {
                if ($amount > 0) {
                    $type = 'credit';
                    $main_type = 'debit';
                } else {
                    $type = 'debit';
                    $main_type = 'credit';
                }
            } else if ($account_type_name == "Liabilities" || $account_type_name == "Equity" || $account_type_name == "Income" || $account_type_name == "Long Term Liabilities" || $account_type_name == "Current Liabilities") {
                if ($amount > 0) {
                    $type = 'debit';
                    $main_type = 'credit';
                } else {
                    $type = 'credit';
                    $main_type = 'debit';
                }
            }
        }




        $amount = ($amount);

        $ob_transaction_data = [
            'amount' => abs($this->commonUtil->num_uf($amount)),
            'account_id' => $account_id,
            'type' => $main_type,
            'sub_type' => 'opening_balance',
            'operation_date' => !empty($date) ? $date : Carbon::now(),
            'note' => !empty($note) ? $note : null,
            'created_by' => Auth::user()->id
        ];

        $main_at = AccountTransaction::where('id', $account_settings->at_asset_id)->update($ob_transaction_data);

        $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');
        $ob_transaction_data['account_id'] = $opening_balance_equity_id;
        $ob_transaction_data['amount'] = abs($this->commonUtil->num_uf($amount));
        $ob_transaction_data['type'] = $type;
        $ob_transaction_data['pair_at_id'] = $account_settings->at_asset_id;
        AccountTransaction::where('id', $account_settings->at_obe_id)->update($ob_transaction_data);

        return true;
    }
}
