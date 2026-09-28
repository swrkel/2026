<?php
namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\Journal;
use App\System;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Petro\Entities\PumpOperator;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Yajra\DataTables\DataTables;

class JournalController extends Controller
{
    protected $moduleUtil;
    protected $transactionUtil;

    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil      = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id    = request()->session()->get('business.id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        if (request()->ajax()) {
            try {
                // Keep the response DataTables-compatible even when the subscription is expired.
                if (! $this->moduleUtil->isSubscribed(request()->session()->get('business.id'))) {
                    return response()->json([
                        'draw' => (int) request()->get('draw', 0),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'success' => 0,
                        'msg' => __(
                            "superadmin::lang.subscription_expired_toastr",
                            [
                                'app_name' => env('app.name'),
                                'subscribe_url' => action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index'),
                            ]
                        ),
                    ]);
                }

                $journal = Journal::leftjoin('users', 'journals.added_by', 'users.id')
                    ->leftjoin('accounts', 'journals.account_id', 'accounts.id')
                    ->where('journals.business_id', $business_id)
                    ->select(
                        'journals.*',
                        'accounts.name as account_name',
                        'users.username as user'
                    );

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end   = request()->end_date;
                    $journal->whereDate('date', '>=', $start)
                        ->whereDate('date', '<=', $end);
                }
                if (! empty(request()->account_id)) {
                    $journal->where('journals.account_id', request()->account_id);
                }
                if (! empty(request()->location_id)) {
                    $journal->where('journals.location_id', request()->location_id);
                } else {
                    $allowed_locations = ModulePermissionLocation::getModulePermissionLocations($business_id, 'accounting_module');
                    if (! empty($allowed_locations) && ! empty($allowed_locations->locations)) {
                        $location_ids = array_keys($allowed_locations->locations);
                        $journal->whereIn('journals.location_id', $location_ids);
                    }
                }

            if ($account_access == 0) {
                $journal = collect([]);
            }

                return Datatables::of($journal)
                    ->addColumn('action', function ($row) {

                        $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="' . action('JournalController@edit', [$row->id]) . '" class="journal_edit"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>

                        <li><a data-href="' . action('JournalController@destroy', [$row->journal_id]) . '" class="delete_journal"><i class="glyphicon glyphicon-trash" style="color:brown; cursor: pointer;"></i> Delete</a></li>
                        ';

                        $html .= '</ul></div>';
                        return $html;
                    })
                    ->editColumn('debit_amount', '@if(!empty($debit_amount)){{@num_format($debit_amount)}}@endif')
                    ->editColumn('credit_amount', '@if(!empty($credit_amount)){{@num_format($credit_amount)}}@endif')
                    ->editColumn('date', '{{\Carbon::parse($date)->format("Y-m-d")}}')
                    ->editColumn('account_id', function ($row) {
                        $debit_account_id = Account::where('id', $row->debit_account_id)->first();
                        if ($debit_account_id) {
                            return $debit_account_id->name;
                        } else {
                            return '';
                        }
                    })
                    ->addColumn('note_btn', function ($row) {
                        if (! empty($row->note)) {
                            return '<button type="button" class="btn btn-xs btn-primary view-note"
                                    data-note="' . e($row->note) . '">
                                    <i class="fa fa-eye"></i> View
                                </button>';
                        }
                        return '';
                    })
                    ->rawColumns(['action'])
                    ->rawColumns(['action', 'note_btn'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::error('Journal list DataTable failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'draw' => (int) request()->get('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $accounts           = Account::where('business_id', $business_id)->notClosed()->whereNull('default_account_id')->pluck('name', 'id');

        return view('journals.index')->with(compact('business_locations', 'account_access', 'accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id         = request()->session()->get('business.id');
        $accounts            = Account::where('business_id', $business_id)->notClosed()->whereNull('default_account_id')->pluck('name', 'id');
        $locations           = BusinessLocation::forDropdown($business_id);
        $default_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;
        $account_types       = AccountType::forDropdown($business_id, false, false);
        $account_access      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $journal_last        = Journal::where('business_id', $business_id)->select('journal_id')->get()->last()->journal_id ?? null;
        $journal_id          = ! empty($journal_last) ? $journal_last + 1 : 1;
        $pump_operators      = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $suppliers           = Contact::suppliersDropdown($business_id, false);
        $customers           = Contact::customersDropdown($business_id, false);
        $settlement_access   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'settlement_sw_module');
        $petro_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_module');

        Log::info('create flags', [
    'settlement_access' => $settlement_access,
    'petro_access'      => $petro_access,
]);


        return view('journals.create')->with(compact('accounts', 'locations', 'account_access', 'default_location_id', 'journal_id', 'account_types', 'suppliers', 'customers', 'pump_operators', 'settlement_access', 'petro_access'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $business_id    = request()->session()->get('business.id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        if (! $account_access) {
            $output = [
                'success' => 0,
                'msg'     => System::getProperty('not_enalbed_module_user_message'),
            ];
            return redirect()->back()->with('status', $output);
        }

        $validator = Validator::make($request->all(), [
            'location_id' => 'required',
            'date' => 'required',
            'debit_total' => 'required',
            'credit_total' => 'required|same:debit_total',
            'show_in_ledger' => 'required',
            'show_in' => function ($attribute, $value, $fail) use ($request) {
                if ($this->requiresLedgerDetails($request) && empty($value)) {
                    $fail('The show in field is required.');
                }
            },
            'ledger_holder' => function ($attribute, $value, $fail) use ($request) {
                if ($this->requiresLedgerDetails($request) && empty($value)) {
                    $fail('The ledger holder field is required.');
                }
            },
        ]);


        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];
            return redirect()->back()->with('status', $output);
        }

        try {
            // ✅ prevent if date already reviewed
            $has_reviewed = $this->transactionUtil->hasReviewed($request->date);
            if (! empty($has_reviewed)) {
                return redirect()->back()->with([
                    'status' => [
                        'success' => 0,
                        'msg'     => __('lang_v1.review_first'),
                    ],
                ]);
            }

            $reviewed = $this->transactionUtil->get_review($request->date, $request->date);
            if (! empty($reviewed)) {
                return redirect()->back()->with([
                    'status' => [
                        'success' => 0,
                        'msg'     => "You can't add a journal for an already reviewed date",
                    ],
                ]);
            }

            $journals   = $request->journal;
            $total_amt  = 0;
            $all_notes  = "";

            DB::beginTransaction();

            $journal_last = Journal::where('business_id', $business_id)->lockForUpdate()->max('journal_id');
            $journal_id = ! empty($journal_last) ? $journal_last + 1 : 1;

            //collect per-row entries to create corresponding ledger items
            $per_row_for_ledger = [];

            foreach ($journals['account_type_id'] as $key => $account_type) {
                $note = ! empty($request->note) ? $request->note : null;
                $all_notes .= ! empty($request->note) ? $request->note . "\n" : "";

                $debit_amount  = ! empty($journals['debit_amount'][$key]) ? (float) $journals['debit_amount'][$key] : null;
                $credit_amount = ! empty($journals['credit_amount'][$key]) ? (float) $journals['credit_amount'][$key] : null;

            $data = [
                'business_id'        => $business_id,
                'journal_id'         => $journal_id,
                'location_id'        => $request->location_id,
                'date'               => $this->transactionUtil->uf_date($request->date),
                'debit_amount'       => !empty($journals['debit_amount'][$key]) ? (float) $journals['debit_amount'][$key] : null,
                'credit_amount'      => !empty($journals['credit_amount'][$key]) ? (float) $journals['credit_amount'][$key] : null,
                'account_type_id'    => $journals['account_type_id'][$key],
                'account_id'         => $journals['account_id'][$key],
                'note'               => $note,
                'is_opening_balance' => $request->is_opening_balance,
                'added_by'           => Auth::user()->id,
                'customer_id'        => ($request->show_in_ledger == 'customer') ? $request->ledger_holder : null,
                'supplier_id'        => ($request->show_in_ledger == 'supplier') ? $request->ledger_holder : null,
                'pump_operator'      => ($request->show_in_ledger == 'pump_operator') ? $request->ledger_holder : null,
                'show_in_ledger'     => $request->show_in_ledger,
                'show_in'            => $request->show_in_ledger === 'no' ? null : $request->show_in,
            ];

                $journal = Journal::create($data);

                // track debit/credit
                if (! empty($journal->debit_amount)) {
                    $type   = 'debit';
                    $amount = $journal->debit_amount;
                } elseif (! empty($journal->credit_amount)) {
                    $type   = 'credit';
                    $amount = $journal->credit_amount;
                } else {
                    continue;
                }

                $total_amt += $amount;

                $acc_tran = [
                    'account_id'     => $journal->account_id,
                    'type'           => $type,
                    'business_id'    => $business_id,
                    'amount'         => $amount,
                    'operation_date' => $this->transactionUtil->uf_date($request->date),
                    'created_by'     => $journal->added_by,
                    'note'           => $note,
                    'journal_entry'  => $journal->id,
                ];
                AccountTransaction::create($acc_tran);

                if ($request->is_opening_balance == 'yes') {
                    app('App\Http\Controllers\AccountController')
                        ->addAccountOpeningBalance($amount, $journal['account_id'], $this->transactionUtil->uf_date($request->date), $note);
                }

                $per_row_for_ledger[] = [
                    'journal_row_id' => $journal->id,
                    'journal_type'   => $type,
                    'amount'         => $amount,
                    'note'           => $note,
                    'cheque_number'  => ! empty($request->cheque_number) ? $request->cheque_number : null,
                ];
            }

            /**
             * ✅ Handle show_in_ledger (Customer / Supplier / Pump Operator)
             */
            $contact_id = null;

            if ($request->show_in_ledger == 'customer') {
                // $contact_id = $request->customer_show_in;
                $contact_id = $request->ledger_holder ?? $request->customer_show_in;
            } elseif ($request->show_in_ledger == 'supplier') {
                // $contact_id = $request->supplier_show_in;
                $contact_id = $request->ledger_holder ?? $request->supplier_show_in;
            } elseif ($request->show_in_ledger == 'pump_operator') {
                // $contact_id = $request->pump_operator;
                $contact_id = $request->ledger_holder ?? $request->pump_operator;
            }

            if (! empty($request->show_in_ledger) && ! empty($contact_id)) {
                // create transaction entry
                $transaction = Transaction::on('mysql')->create([
                    'business_id'      => $business_id,
                    'location_id'      => $request->location_id,
                    'type'             => 'ledger',
                    'contact_id'       => $contact_id,
                    'invoice_no'       => "Journal: " . $journal_id,
                    'total_before_tax' => $total_amt,
                    'transaction_date' => $this->transactionUtil->uf_date($request->date),
                    'final_total'      => $total_amt,
                    'additional_notes' => $all_notes,
                    'created_by'       => request()->session()->get('user.id'),
                ]);

                foreach ($per_row_for_ledger as $row) {
                    // if journal row was debit, contact ledger should be credit and vice versa
                    $ledger_type = ($row['journal_type'] == 'debit') ? 'credit' : 'debit';

                    // create ledger entry
                    $ledger_data = [
                        "created_by"     => request()->session()->get('user.id'),
                        "contact_id"     => $contact_id,
                        // "type"           => $request->show_in, // debit/credit
                        "type"           => $ledger_type,
                        "amount"         => $row['amount'],
                        "transaction_id" => $transaction->id,
                        "operation_date" => $this->transactionUtil->uf_date($request->date),
                        "note"           => $row['note'],
                        "cheque_number"  => $row['cheque_number'],
                        "journal_row_id" => $row['journal_row_id'],
                        "description"    => "Journal " . $journal_id,
                    ];
                    ContactLedger::on('mysql')->create($ledger_data);
                }
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('account.journal_add_succuss'),
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
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
        $business_id = request()->session()->get('business.id');

        // Fetch required dropdowns
        $locations = BusinessLocation::forDropdown($business_id);
        $accounts  = Account::where('business_id', $business_id)
            ->notClosed()
            ->pluck('name', 'id');

        $account_types = AccountType::forDropdown($business_id, false, false);

        // Fetch the main journal entry
        $journal    = Journal::where('business_id', $business_id)->findOrFail($id);
        $journal_id = $journal->journal_id;

        // Fetch all journals with the same journal_id (grouped journal entries)
        $journals = Journal::where('business_id', $business_id)
            ->where('journal_id', $journal_id)
            ->get();

    $customers = Contact::customersDropdown($business_id, false);
    $suppliers = Contact::suppliersDropdown($business_id, false);
    $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
    $settlement_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'settlement_sw_module');
    $petro_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_module');

    $ledger_holder = null;
    if ($journal->show_in_ledger == 'customer') {
        $ledger_holder = $journal->customer_id ?? null;
    } else if ($journal->show_in_ledger == 'supplier') {
        $ledger_holder = $journal->supplier_id ?? null;
    } else if ($journal->show_in_ledger == 'pump_operator') {
        $ledger_holder = $journal->pump_operator ?? null;
    }

    return view('journals.edit')->with(compact(
        'journals',
        'journal',
        'accounts',
        'locations',
        'account_types',
        'customers',
        'suppliers',
        'pump_operators',
        'settlement_access',
        'petro_access',
        'ledger_holder'
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
        $validator = Validator::make($request->all(), [
            'location_id' => 'required',
            'date' => 'required',
            'debit_total' => 'required',
            'credit_total' => 'required|same:debit_total',
            'show_in_ledger' => 'required',
            'show_in' => function ($attribute, $value, $fail) use ($request) {
                if ($this->requiresLedgerDetails($request) && empty($value)) {
                    $fail('The show in field is required.');
                }
            },
            'ledger_holder' => function ($attribute, $value, $fail) use ($request) {
                if ($this->requiresLedgerDetails($request) && empty($value)) {
                    $fail('The ledger holder field is required.');
                }
            },
        ]);


        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg' => $validator->errors()->all()[0]
            ];
            return redirect()->back()->with('status', $output);
        }

        try {
            $business_id = request()->session()->get('business.id');
            $journals    = $request->journal;
            $journal_id  = ! empty($request->journal_id) ? $request->journal_id : 1;

            $has_reviewed = $this->transactionUtil->hasReviewed($request->date);

            if (! empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->date, $request->date);

            if (! empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't add a journal for an already reviewed date",
                ];

                return redirect()->back()->with('status', $output);
            }

            DB::beginTransaction();
            $total_amt = 0;
            foreach ($journals as $journal) {
                $note = ! empty($request->note) ? $request->note : null;
                $data = [
                    'business_id'     => $business_id,
                    'journal_id'      => $journal_id,
                    'location_id'     => $request->location_id,
                    'date'            => $this->transactionUtil->uf_date($request->date),
                    'debit_amount'    => ! empty($journal['debit_amount']) ? $journal['debit_amount'] : null,
                    'credit_amount'   => ! empty($journal['credit_amount']) ? $journal['credit_amount'] : null,
                    'account_type_id' => $journal['account_type_id'],
                    'account_id'      => $journal['account_id'],
                    'note'            => $note,
                    'is_opening_balance' => $request->is_opening_balance,
                    'added_by'        => Auth::user()->id,
                    'customer_id' => ($request->show_in_ledger == 'customer') ? $request->ledger_holder : null,
                    'supplier_id' => ($request->show_in_ledger == 'supplier') ? $request->ledger_holder : null,
                    'pump_operator' => ($request->show_in_ledger == 'pump_operator') ? $request->ledger_holder : null,
                    'show_in_ledger' => $request->show_in_ledger,
                    'show_in' => $request->show_in_ledger === 'no' ? null : $request->show_in,
                ];
                if (! empty($journal['id'])) {
                    Journal::where('id', $journal['id'])->update($data);
                } else {
                    $new_journal = Journal::create($data);
                }

                if (! empty($journal['debit_amount'])) {
                    $type   = 'debit';
                    $amount = $journal['debit_amount'];
                } else {
                    $type   = 'credit';
                    $amount = $journal['credit_amount'];
                }

                $total_amt += $amount;

                $acc_tran = [
                    'account_id'     => $journal['account_id'],
                    'type'           => $type,
                    'amount'         => $amount,
                    'operation_date' => $this->transactionUtil->uf_date($request->date),
                    'created_by'     => Auth::user()->id,
                    'note'           => $note,
                ];
                if (! empty($journal['id'])) {
                    AccountTransaction::where('journal_entry', $journal['id'])->update($acc_tran);
                } else {
                    $acc_tran['journal_entry'] = $new_journal->id;
                    AccountTransaction::create($acc_tran);
                }

                if ($request->is_opening_balance == 'yes') {
                    $openign_balance = AccountTransaction::where('sub_type', 'opening_balance')->where('account_id', $journal['account_id'])->first();
                    if (! empty($openign_balance)) {
                        $openign_balance->note           = $note;
                        $openign_balance->amount         = $amount;
                        $openign_balance->operation_date = $this->transactionUtil->uf_date($request->date);
                        $openign_balance->save();

                    } else {
                        app('App\Http\Controllers\AccountController')->addAccountOpeningBalance($amount, $journal['account_id'], $this->transactionUtil->uf_date($request->date), $note);
                    }
                }
            }
            
            $all_notes = $request->note;
            $contact_id = null;
            if ($request->show_in_ledger !== 'no') {
                $contact_id = $request->ledger_holder;
            }

            if (!empty($request->show_in_ledger) && !empty($contact_id)) {
                $transaction = Transaction::where('invoice_no', "Journal: " . $journal_id)
                    ->where('business_id', $business_id)
                    ->first();

                if ($transaction) {
                    $transaction->location_id = $request->location_id;
                    $transaction->total_before_tax = $total_amt;
                    $transaction->transaction_date = $this->transactionUtil->uf_date($request->date);
                    $transaction->final_total = $total_amt;
                    $transaction->additional_notes = $all_notes;
                    $transaction->save();
                } else {
                    $transaction = Transaction::create([
                        'business_id'       => $business_id,
                        'location_id'       => $request->location_id,
                        'type'              => 'ledger',
                        'contact_id'        => $contact_id,
                        'invoice_no'        => "Journal: " . $journal_id,
                        'total_before_tax'  => $total_amt,
                        'transaction_date'  => $this->transactionUtil->uf_date($request->date),
                        'final_total'       => $total_amt,
                        'additional_notes'  => $all_notes,
                        'created_by'        => request()->session()->get('user.id'),
                    ]);
                }

                $ledger_data = [
                    "created_by"     => request()->session()->get('user.id'),
                    "contact_id"     => $contact_id,
                    "type"           => $request->show_in,
                    "amount"         => $total_amt,
                    "transaction_id" => $transaction->id,
                    "operation_date" => $this->transactionUtil->uf_date($request->date)
                ];

                $ledger = ContactLedger::where('transaction_id', $transaction->id)->first();
                if ($ledger) {
                    $ledger->update($ledger_data);
                } else {
                    ContactLedger::create($ledger_data);
                }
            } else {
                $transaction = Transaction::where('invoice_no', "Journal: " . $journal_id)->first();
                if ($transaction) {
                    ContactLedger::where('transaction_id', $transaction->id)->delete();
                    $transaction->delete();
                }
            }
            
            DB::commit();
            $output = [
                'success' => 1,
                'msg'     => __('account.journal_update_succuss'),
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
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
        try {

            $journals = Journal::where('journal_id', $id)->get();

            if (! empty($journals)) {

                $has_reviewed = $this->transactionUtil->hasReviewed($journals[0]->date);

                if (! empty($has_reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => __('lang_v1.review_first'),
                    ];

                    return redirect()->back()->with(['status' => $output]);
                }

                $reviewed = $this->transactionUtil->get_review($journals[0]->date, $journals[0]->date);

                if (! empty($reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => "You can't delete a journal for an already reviewed date",
                    ];

                    return $output;
                }
            }

            /*
             |------------------------------------------------------------------
             | LA-1214: deleting a journal must remove its accounting effect.
             |------------------------------------------------------------------
             | This used to take ONE account transaction per journal row
             | (->first()), FLIP its type from debit to credit, set
             | journal_deleted = 1, and leave the row live.
             |
             | Flipping does not remove the effect - it reverses it. A deleted
             | debit of 1,000 went on contributing MINUS 1,000 to the account
             | instead of 0, so every balance in the system was understated by
             | twice the value of every deleted journal. The ledger and the
             | dashboard both read it that way, so they agreed with each other
             | and the error was invisible.
             |
             | AccountTransaction uses SoftDeletes. Deleting the rows removes
             | them from every balance while keeping the history, and
             | journal_deleted stays as the audit LABEL on a deleted row - which
             | is what FinanceReportsDataService already expects when it reports
             | "Journal Deleted".
             |
             | This is the implementation already running and reviewed in
             | Modules/Finance/Http/Controllers/Journal/JournalController.php,
             | brought back to core so both copies agree.
             |
             | Also fixed here:
             |   - ALL account transactions per journal row, not just the first
             |     (an edited journal can have more than one)
             |   - scoped to the current business
             |   - wrapped in a database transaction, so a failure part-way
             |     cannot leave a journal half-deleted
             |   - a missing account transaction no longer throws
             */
            $business_id = request()->session()->get('user.business_id');

            DB::beginTransaction();

            $journalRowIds = $journals->pluck('id')
                ->map(static fn ($value) => (int) $value)
                ->filter()
                ->values()
                ->all();

            if ($journalRowIds !== []) {
                $accountTransactions = AccountTransaction::whereIn('journal_entry', $journalRowIds)
                    ->when(! empty($business_id), function ($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    })
                    ->get();

                foreach ($accountTransactions as $accountTransaction) {
                    if (Schema::hasColumn('account_transactions', 'journal_deleted')) {
                        $accountTransaction->journal_deleted = 1;
                        $accountTransaction->save();
                    }

                    $accountTransaction->delete();   // soft delete
                }
            }

            foreach ($journals as $journal) {
                $journal->delete();
            }

            $transaction = Transaction::where('invoice_no', "Journal: " . $id)
                ->when(! empty($business_id), function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                })
                ->first();

            if (! empty($transaction)) {
                $ledger = ContactLedger::where('transaction_id', $transaction->id)->first();
                if (! empty($ledger)) {
                    $ledger->delete();
                }
                $transaction->delete();
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('account.journal_delete_succuss'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
        return $output;
    }

    /**
     * Get row for journals enteries
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function getRow(Request $request)
    {
        $index         = $request->index;
        $business_id   = request()->session()->get('business.id');
        $accounts      = Account::where('business_id', $business_id)->notClosed()->whereNull('default_account_id')->pluck('name', 'id');
        $account_types = AccountType::forDropdown($business_id, false, false);

        return view('journals.get_row')->with(compact('accounts', 'index', 'account_types'));
    }
    public function getAccountDropdownByAccountType($account_type_id)
    {
        $accounts = Account::getAccountByAccountTypeId($account_type_id);

        return $this->transactionUtil->createDropdownHtml($accounts, 'Please select');
    }

    protected function requiresLedgerDetails(Request $request): bool
    {
        return ! empty($request->show_in_ledger) && $request->show_in_ledger !== 'no';
    }
}
