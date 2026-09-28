<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountSetting;
use App\AccountTransaction;
use App\AccountType;
use App\Contact;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\TransactionPayment;
use App\Transaction;
use App\BusinessLocation;
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
        // If not AJAX request, return the view with necessary data
        if (!request()->ajax()) {
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

            return view('account_settings.index')
                ->with(compact('account_types_opts', 'sub_acn_arr', 'account_groups', 'customers', 'chequeId', 'can_edit_ob'));
        }

        if (request()->ajax()) {

            // update the previous AS entries
            $business_id = request()->session()->get('user.business_id');

            $toUpdate = AccountSetting::where('business_id', $business_id)->get();
            foreach ($toUpdate as $one) {
                $amount = $one->amount;
                $account_type_id = Account::where('id', $one->account_id)->first() ? Account::where('id', $one->account_id)->first()->account_type_id : 0;
                $account_type = AccountType::where('id', $account_type_id)->first();
                $account_type_name = !empty($account_type) ? $account_type->name : "";
                $account = Account::where('id', $one->account_id)->first();
                if (is_null($account)) {
                    continue;
                }

                $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
                $loans_taken_id = AccountGroup::getGroupByName('Loans Taken', true);
                $account_id = $one->account_id;

                if ($chequeId == $account_id) {
                    if ($amount > 0) {
                        $type = 'credit';
                    } else {
                        $type = 'debit';
                    }
                } else {
                    if ($account_type_name == "Assets" || $account_type_name == "Expenses" || $account_type_name == "Current Assets" || $account_type_name == "Fixed Assets" || $account->asset_type == $loans_taken_id) {
                        if ($amount > 0) {
                            $type = 'credit';
                        } else {
                            $type = 'debit';
                        }
                    } else if ($account_type_name == "Liabilities" || $account_type_name == "Equity" || $account_type_name == "Income" || $account_type_name == "Long Term Liabilities" || $account_type_name == "Current Liabilities") {
                        if ($amount > 0) {
                            $type = 'debit';
                        } else {
                            $type = 'credit';
                        }
                    }
                }


                AccountTransaction::where('id', $one->at_obe_id)->update(['type' => $type]);
            }



            $brands = AccountSetting::leftjoin('account_groups', 'account_settings.group_id', 'account_groups.id')
                ->leftjoin('accounts', 'account_settings.account_id', 'accounts.id')
                ->leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')
                ->leftjoin(
                    'account_types as pat',
                    'account_types.parent_account_type_id',
                    '=',
                    'pat.id'
                )

                ->leftjoin('users', 'account_settings.created_by', 'users.id')
                ->where('account_settings.business_id', $business_id)
                ->where(function ($q) use ($request) {
                    if (isset($request->date) && !empty($request->date)) {
                        $request->date = date('Y-m-d', strtotime($request->date));
                        $q->where('account_settings.date', $request->date);
                    }
                    if (isset($request->account_type) && !empty($request->account_type)) {
                        $q->where('account_types.parent_account_type_id', $request->account_type);
                    }
                    if (isset($request->account_sub_type) && !empty($request->account_sub_type)) {
                        $q->where('accounts.account_type_id', $request->account_sub_type);
                    }
                    if (isset($request->account_id) && !empty($request->account_id)) {
                        $q->where('account_settings.account_id', $request->account_id);
                    }
                    if (isset($request->group_id) && !empty($request->group_id)) {
                        $q->where('account_settings.group_id', $request->group_id);
                    }
                })
                ->select(
                    DB::raw('COALESCE(account_settings.date, DATE(account_settings.created_at)) as date'),
                    'account_groups.name as account_group',
                    'accounts.name',
                    'account_settings.amount',
                    'users.username as created_by',
                    'account_settings.id',
                    'account_types.name as account_type_name',
                    'pat.name as parent_account_type_name'
                );

            return datatables()::of($brands)
                ->addColumn(
                    'action',
                    function ($row) use ($business_id) {
                        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_ob')) {
                            return '<button data-href="' . action('AccountSettingController@edit', [$row->id]) . '" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i>' . __("messages.edit") . '</button>
                        &nbsp;';
                        }
                    }

                )
                ->editColumn('parent_account_type_name', function ($row) {
                    $parent_account_type_name = empty($row->parent_account_type_name) ? $row->account_type_name : $row->parent_account_type_name;
                    return $parent_account_type_name;
                })
                ->editColumn('account_type_name', function ($row) {
                    $account_type_name = empty($row->parent_account_type_name) ? '' : $row->account_type_name;
                    return $account_type_name;
                })
                ->editColumn('amount', '{{@num_format($amount)}}')
                ->editColumn('date', function ($row) {
                    return !empty($row->date) ? $this->transactionUtil->format_date($row->date) : '';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }
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

        return view('account_settings.edit')->with(compact(
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