<?php

namespace Modules\Finance\Http\Controllers\Journal;

use Carbon\Carbon;

use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\AccountType;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\Contact;
use Modules\Finance\Entities\ContactLedger;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Journal;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Finance\Http\Controllers\AccountController as FinanceAccountController;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Yajra\DataTables\Facades\DataTables;

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
        $request = request();
        $business_id = $this->businessId($request);
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $permitted_location_ids = $this->permittedLocationIds($business_id);

        if ($request->ajax()) {
            try {
                if (! $this->moduleUtil->isSubscribed($business_id)) {
                    return response()->json([
                        'draw' => (int) $request->get('draw', 0),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'success' => 0,
                        'msg' => __('superadmin::lang.subscription_expired_toastr', [
                            'app_name' => env('app.name'),
                            'subscribe_url' => action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index'),
                        ]),
                    ]);
                }

                /*
                 * IS1992: "DataTables warning ... Unknown column
                 * 'journals.show_in_ledger'".
                 *
                 * `journals` is a CORE table (Entities\Journal extends
                 * App\Journal) and this module ships no migration for it - see
                 * the empty Database/Migrations folder. The ledger-linking
                 * columns are therefore present on some tenants and absent on
                 * others, and naming one unconditionally in the SELECT is what
                 * broke the list outright on a tenant without it.
                 *
                 * store() already guards exactly these five columns against
                 * Schema::getColumnListing('journals') before writing. The same
                 * check is applied here, so the list degrades to "no ledger
                 * link" instead of failing.
                 */
                // IS226x plug-and-play schema self-heal: older tenant databases
                // can be missing one or more journal ledger-link columns. Repair
                // only the missing columns on the active tenant before building
                // the Journal list, so non-technical users never have to run a
                // tenant SQL script manually.
                $this->ensureJournalLedgerSchema();
                $journal_table_columns = array_flip(Schema::getColumnListing('journals'));

                $journal_select = [
                    'journals.id',
                    'journals.journal_id',
                    'journals.business_id',
                    'journals.location_id',
                    'journals.account_type_id',
                    'journals.account_id',
                    'journals.date',
                    'journals.debit_amount',
                    'journals.credit_amount',
                    'journals.note',
                    'journals.is_opening_balance',
                    'journals.added_by',
                ];

                foreach (['show_in_ledger', 'show_in', 'customer_id', 'supplier_id', 'pump_operator'] as $optional_column) {
                    if (isset($journal_table_columns[$optional_column])) {
                        $journal_select[] = 'journals.' . $optional_column;
                    }
                }

                $journal_select[] = 'journal_accounts.name as account_name';
                $journal_select[] = 'journal_users.username as user';

                $journal = Journal::query()
                    ->leftJoin('users as journal_users', 'journals.added_by', '=', 'journal_users.id')
                    ->leftJoin('accounts as journal_accounts', 'journals.account_id', '=', 'journal_accounts.id')
                    ->where('journals.business_id', $business_id)
                    ->select($journal_select);

                /*
                 * S-666 #2: names for the Ledger Holder column, fetched ONCE for the
                 * whole draw. Looking each holder up inside the row callback would
                 * mean one query per row on every page of the list.
                 */
                $ledger_holder_names = $this->ledgerHolderNameMap($business_id, $journal_table_columns);

                $this->applyJournalFilters($journal, $request, $permitted_location_ids, $journal_table_columns);

                if (! $account_access) {
                    $journal->whereRaw('1 = 0');
                }

                /*
                 * IS2055 #4: which journals already have a payment against them.
                 *
                 * Resolved once for the page rather than per row - see
                 * paidJournalIds(). The ids come from the query the datatable is
                 * about to run, so only the visible page is considered.
                 */
                $paid_journal_ids = $this->paidJournalIds(
                    (int) $business_id,
                    (clone $journal)->pluck('journals.journal_id')->all()
                );

                return DataTables::of($journal)
                    /*
                     * IS2055 #3: show the Journal Entry No. with a JOUR prefix.
                     *
                     * The list showed the raw journal_id - 1, 2, 3 - in a column
                     * headed "Journal No", right beside the row counter in the
                     * "#" column. Two plain sequences side by side is what the
                     * ticket means by the system number and the entry number
                     * being mixed up: at a glance there was nothing to say which
                     * was which.
                     *
                     * Formatting it as JOUR0001 separates them unambiguously -
                     * the "#" column stays a plain row counter, and this one is
                     * now visibly a document reference.
                     *
                     * Only the DISPLAY changes. journals.journal_id keeps its
                     * integer value, so ordering, searching by number and every
                     * existing lookup behave exactly as before.
                     */
                    ->editColumn('journal_id', function ($row) {
                        if ($row->journal_id === null || $row->journal_id === '') {
                            return '';
                        }

                        return 'JOUR' . str_pad((string) $row->journal_id, 4, '0', STR_PAD_LEFT);
                    })
                    ->addColumn('action', function ($row) use ($paid_journal_ids) {
                        /*
                         * IS2034 #2: View / Edit / Delete.
                         *
                         * View was missing entirely - show() was an empty stub - so the
                         * menu offered only two of the three actions the ticket asks for.
                         *
                         * The markup follows the pattern already used by
                         * CustomerPaymentController and DisabledAccountController
                         * (btn-group > btn-info btn-xs dropdown-toggle > dropdown-menu
                         * dropdown-menu-right, Font Awesome icons) so the control matches
                         * the rest of the system. The two glyphicon icons used here before
                         * were the odd ones out.
                         */
                        /*
                         * IS2055 #4: Edit and Delete are withdrawn once a payment
                         * has been made against this journal.
                         *
                         * Editing it would leave the payment pointing at an amount
                         * that no longer matches, and deleting it would leave the
                         * payment attached to nothing. View stays available so the
                         * entry can still be inspected.
                         */
                        $is_paid = isset($paid_journal_ids[(int) $row->journal_id]);

                        $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' .
                            __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="#" data-href="' . route('finance.accounting.journal.show', [$row->id]) . '" class="journal_view btn-modal" data-container=".view_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __('messages.view') . '</a></li>';

                        if ($is_paid) {
                            $reason = e(__('A payment has been made against this journal.'));

                            $html .= '<li class="disabled"><a href="#" onclick="return false;" style="cursor:not-allowed; opacity:.55;" title="' . $reason . '"><i class="fa fa-edit" aria-hidden="true"></i> ' . __('messages.edit') . '</a></li>'
                                . '<li class="disabled"><a href="#" onclick="return false;" style="cursor:not-allowed; opacity:.55;" title="' . $reason . '"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('messages.delete') . '</a></li>'
                                . '<li role="separator" class="divider"></li>'
                                . '<li><a href="#" onclick="return false;" style="cursor:default; white-space:normal; font-size:11px; color:#999;">' . $reason . '</a></li>';
                        } else {
                            $html .= '<li><a href="' . route('finance.accounting.journal.edit', [$row->id]) . '" class="journal_edit"><i class="fa fa-edit" aria-hidden="true"></i> ' . __('messages.edit') . '</a></li>'
                                . '<li><a data-href="' . route('finance.accounting.journal.destroy', [$row->journal_id]) . '" class="delete_journal" style="cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('messages.delete') . '</a></li>';
                        }

                        return $html . '</ul></div>';
                    })
                    ->editColumn('debit_amount', '@if(!empty($debit_amount)){{@num_format($debit_amount)}}@endif')
                    ->editColumn('credit_amount', '@if(!empty($credit_amount)){{@num_format($credit_amount)}}@endif')
                    ->editColumn('date', function ($row) {
                        try {
                            return Carbon::parse($row->date)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            return (string) $row->date;
                        }
                    })
                    ->editColumn('account_name', function ($row) {
                        return ! empty($row->account_name)
                            ? e($row->account_name)
                            : __('account.account') . ' #' . (int) $row->account_id;
                    })
                    ->filterColumn('account_name', function ($query, $keyword) {
                        $query->where('journal_accounts.name', 'like', '%' . $keyword . '%');
                    })
                    ->orderColumn('account_name', 'journal_accounts.name $1')
                    ->addColumn('note_display', function ($row) {
                        $note = trim((string) $row->note);
                        if ($note === '') {
                            return '';
                        }

                        $short_note = \Illuminate\Support\Str::limit($note, 90);

                        return '<button type="button" class="btn btn-link btn-xs view-note journal-note-link" data-note="' .
                            e($note) . '" title="' . e($note) . '">' . e($short_note) . '</button>';
                    })
                    ->addColumn('ledger_holder_display', function ($row) use ($ledger_holder_names, $journal_table_columns) {
                        /*
                         * S-666 #2: the Ledger Holder column from the reference layout.
                         *
                         * Resolved from names looked up ONCE for the whole page rather
                         * than per row - a query per row here would be 25 extra queries
                         * on every draw.
                         *
                         * The ledger columns are optional on some tenants (see
                         * Database/SQL/IS1992_Journals_Add_Ledger_Columns.sql), so a
                         * database without them shows "-" instead of failing.
                         */
                        if (! isset($journal_table_columns['show_in_ledger'])) {
                            return '-';
                        }

                        $type = $row->show_in_ledger ?? null;
                        if (empty($type) || $type === 'no') {
                            return '-';
                        }

                        $holderId = null;
                        if ($type === 'customer' && isset($journal_table_columns['customer_id'])) {
                            $holderId = $row->customer_id;
                        } elseif ($type === 'supplier' && isset($journal_table_columns['supplier_id'])) {
                            $holderId = $row->supplier_id;
                        } elseif ($type === 'pump_operator' && isset($journal_table_columns['pump_operator'])) {
                            $holderId = $row->pump_operator;
                        }

                        $name = $holderId !== null
                            ? ($ledger_holder_names[$type][$holderId] ?? null)
                            : null;

                        return $name !== null ? e($name) : '-';
                    })
                    ->rawColumns(['action', 'note_display'])
                    ->make(true);
            } catch (\Throwable $e) {
                Log::error('Finance journal list failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'draw' => (int) $request->get('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }

        $business_location_query = BusinessLocation::where('business_id', $business_id);
        if (is_array($permitted_location_ids)) {
            $business_location_query->whereIn('id', $permitted_location_ids);
        }
        $business_locations = $business_location_query->orderBy('name')->pluck('name', 'id');

        $accounts = Account::where('business_id', $business_id)
            ->notClosed()
            ->orderBy('name')
            ->pluck('name', 'id');
        $cash_account_id = Account::where('business_id', $business_id)
            ->where('name', 'Cash')
            ->value('id');

        $disabled_message_color = System::getProperty('not_enalbed_module_user_color');
        $disabled_message_font_size = System::getProperty('not_enalbed_module_user_font_size');
        $disabled_message = System::getProperty('not_enalbed_module_user_message');

        /*
         * S-666 #2: options for the Ledger Holder filter.
         *
         * Grouped by ledger type so one dropdown can offer customers, suppliers
         * and pump operators together, and keyed "type:id" so the filter knows
         * WHICH column to match without a second control. Empty on a tenant
         * without the ledger columns, which renders as an empty dropdown rather
         * than an error.
         */
        $ledger_holder_options = $this->ledgerHolderFilterOptions($business_id);

        /*
         | The whole account-type tree, shipped with the page.
         |
         | Choosing an Account Type used to cost TWO sequential HTTP round trips
         | - one for its sub types, then another for its accounts - each booting
         | Laravel, the session and the middleware stack before returning a
         | handful of names. That is the delay: the answer was never slow, the
         | asking was.
         |
         | The data is small and does not change while a form is open, so it is
         | sent once with the page and both dropdowns fill from memory. No
         | request, no wait.
         */
        $journal_account_lookup = $this->journalAccountLookup($business_id);

        return view('finance::journal.index')->with(compact(
            'journal_account_lookup',
            'business_locations',
            'account_access',
            'accounts',
            'cash_account_id',
            'ledger_holder_options',
            'disabled_message_color',
            'disabled_message_font_size',
            'disabled_message'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = $this->businessId(request());
        $accounts = Account::where('business_id', $business_id)
            ->notClosed()
            ->pluck('name', 'id');
        $locations = BusinessLocation::forDropdown($business_id);
        $permitted_location_ids = $this->permittedLocationIds($business_id);
        if (is_array($permitted_location_ids)) {
            $locations = collect($locations)->only($permitted_location_ids);
        }
        $default_location_id = collect($locations)->keys()->first();
        $account_types = $this->parentAccountTypesForDropdown($business_id);
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $journal_id = ((int) Journal::where('business_id', $business_id)->max('journal_id')) + 1;
        $pump_operators = $this->pumpOperators($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $settlement_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'settlement_sw_module');
        $petro_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_module');
        $cash_account_id = Account::where('business_id', $business_id)
            ->where('name', 'Cash')
            ->value('id');

        return view('finance::journal.create')->with(compact(
            'accounts',
            'locations',
            'account_access',
            'default_location_id',
            'journal_id',
            'account_types',
            'suppliers',
            'customers',
            'pump_operators',
            'settlement_access',
            'petro_access',
            'cash_account_id'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $business_id = $this->businessId($request);
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        if (! $account_access) {
            $output = [
                'success' => 0,
                'msg'     => System::getProperty('not_enalbed_module_user_message'),
            ];
            return $this->journalStoreResponse($request, $output, 403);
        }

        $journal_rows = $this->normaliseJournalRows((array) $request->input('journal', []));
        $journal_columns = $this->journalRowsToColumns($journal_rows);
        $debit_total = array_sum($journal_columns['debit_amount']);
        $credit_total = array_sum($journal_columns['credit_amount']);

        // Calculate and normalise the journal payload on the server. The create
        // form previously depended on hidden preview inputs, and disabled amount
        // fields were omitted by the browser, resulting in incomplete arrays.
        $request->merge([
            'note' => trim((string) $request->input('note', '')),
            'is_opening_balance' => $request->input('is_opening_balance', 'no') ?: 'no',
            'show_in_ledger' => $request->input('show_in_ledger', 'no') ?: 'no',
            'journal' => $journal_columns,
            'debit_total' => $debit_total,
            'credit_total' => $credit_total,
        ]);

        /*
         * IS1992 (follow-up): resolve the date ONCE, here, before validation.
         *
         * uf_date($request->date) is called at twelve separate points across
         * store() and update() - the journal row, the account transactions, the
         * opening balance, the contact ledger. Every one of them silently wrote
         * null when the picker's format did not match the business format, and
         * the first NOT NULL column to be hit raised a 500 with no indication of
         * why. Converting once and putting the result back on the request means
         * all twelve read the same resolved value.
         *
         * The date_valid flag lets the validator below report a real message
         * instead of letting the insert fail on a null column.
         */
        $resolved_date = $this->resolveJournalDate($request->input('date'));

        $request->merge([
            'date' => $resolved_date ?? $request->input('date'),
            'date_valid' => $resolved_date !== null ? 1 : 0,
        ]);

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|integer',
            'date' => 'required',
            // IS1992 (follow-up): set above by resolveJournalDate(). Catches a
            // date that was submitted but could not be understood, so the user
            // is told why instead of getting "Something went wrong, please try
            // again later" from a null-column insert failure.
            'date_valid' => 'accepted',
            'note' => 'required|string|max:2000',
            'is_opening_balance' => 'required|in:yes,no',
            'debit_total' => 'required|numeric|gt:0',
            'credit_total' => 'required|numeric|gt:0',
            'show_in_ledger' => 'required|in:no,customer,supplier,pump_operator',
            'journal.account_type_id' => 'required|array|min:2',
            'journal.account_type_id.*' => 'required|integer',
            'journal.account_id' => 'required|array|min:2',
            'journal.account_id.*' => 'required|integer',
            'journal.debit_amount' => 'required|array|min:2',
            'journal.debit_amount.*' => 'nullable|numeric|min:0',
            'journal.credit_amount' => 'required|array|min:2',
            'journal.credit_amount.*' => 'nullable|numeric|min:0',
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
        ], [
            'note.required' => 'The Note field is mandatory for every journal entry.',
            'credit_total.gt' => 'The credit total must be greater than zero.',
            // IS1992 (follow-up): the default wording for 'accepted' would read
            // "The date valid must be accepted", which means nothing to the user.
            'date_valid.accepted' => 'The date could not be read. Please pick the date again using the date field.',
        ]);

        $validator->after(function ($validator) use ($request, $business_id) {
            foreach ($this->journalLedgerSchemaIssues((string) $request->input('show_in_ledger', 'no')) as $schemaIssue) {
                $validator->errors()->add('show_in_ledger', $schemaIssue);
            }

            $location_id = (int) $request->input('location_id');
            $location_exists = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->exists();

            if (! $location_exists) {
                $validator->errors()->add('location_id', 'The selected location is invalid for this business.');
            }

            $permitted_location_ids = $this->permittedLocationIds($business_id);
            if (is_array($permitted_location_ids) &&
                ! in_array($location_id, $permitted_location_ids, true)) {
                $validator->errors()->add('location_id', 'You do not have access to the selected location.');
            }

            $journal = (array) $request->input('journal', []);
            $account_ids = array_values((array) ($journal['account_id'] ?? []));
            $account_type_ids = array_values((array) ($journal['account_type_id'] ?? []));
            $debits = array_values((array) ($journal['debit_amount'] ?? []));
            $credits = array_values((array) ($journal['credit_amount'] ?? []));

            $row_count = count($account_ids);
            if ($row_count === 0 ||
                count($account_type_ids) !== $row_count ||
                count($debits) !== $row_count ||
                count($credits) !== $row_count) {
                $validator->errors()->add('journal', 'The journal entry rows are incomplete.');
                return;
            }

            $debit_total = array_sum(array_map('floatval', $debits));
            $credit_total = array_sum(array_map('floatval', $credits));
            if ($debit_total <= 0 || $credit_total <= 0 || abs($debit_total - $credit_total) > 0.00001) {
                $validator->errors()->add('credit_total', 'The debit and credit totals must be equal and greater than zero.');
            }

            $valid_account_count = Account::where('business_id', $business_id)
                ->whereIn('id', array_map('intval', $account_ids))
                ->count();
            if ($valid_account_count !== count(array_unique(array_map('intval', $account_ids)))) {
                $validator->errors()->add('journal', 'One or more selected accounts are invalid for this business.');
            }

            $valid_account_type_count = AccountType::where('business_id', $business_id)
                ->whereIn('id', array_map('intval', $account_type_ids))
                ->count();
            if ($valid_account_type_count !== count(array_unique(array_map('intval', $account_type_ids)))) {
                $validator->errors()->add('journal', 'One or more selected account types are invalid for this business.');
            }

            $account_type_map = Account::where('business_id', $business_id)
                ->whereIn('id', array_map('intval', $account_ids))
                ->pluck('account_type_id', 'id');

            /*
             | An account may sit under a SUB TYPE of the selected type.
             |
             | This compared the two ids for equality, which was right while the
             | Account Type dropdown listed sub types as though they were types.
             | It is not right now: selecting "Assets" offers the accounts of
             | "Current Assets" and "Fixed Assets", so a perfectly valid row
             | posts type 10 with an account whose own type is 15 - and this
             | rejected it.
             |
             | The check now accepts the account's type being the selected type
             | OR a child of it, which is exactly what the form offers. Anything
             | further away is still refused, so a genuine mismatch - an Income
             | account posted under Liabilities - is still caught.
            */
            $selected_type_ids = array_unique(array_map('intval', $account_type_ids));

            $child_type_map = [];
            if (! empty($selected_type_ids)) {
                $child_rows = \DB::table('account_types')
                    ->where('business_id', $business_id)
                    ->whereIn('parent_account_type_id', $selected_type_ids)
                    ->get(['id', 'parent_account_type_id']);

                foreach ($child_rows as $child_row) {
                    $child_type_map[(int) $child_row->parent_account_type_id][] = (int) $child_row->id;
                }
            }

            foreach ($account_ids as $key => $account_id) {
                $selected_type_id = (int) ($account_type_ids[$key] ?? 0);
                $actual_type_id = (int) ($account_type_map[(int) $account_id] ?? 0);

                $allowed_type_ids = array_merge(
                    [$selected_type_id],
                    $child_type_map[$selected_type_id] ?? []
                );

                if ($actual_type_id > 0 && ! in_array($actual_type_id, $allowed_type_ids, true)) {
                    $validator->errors()->add(
                        'journal.' . $key,
                        'The selected account does not belong to the selected account type.'
                    );
                }
                $debit = isset($debits[$key]) && $debits[$key] !== '' ? (float) $debits[$key] : 0.0;
                $credit = isset($credits[$key]) && $credits[$key] !== '' ? (float) $credits[$key] : 0.0;

                if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
                    $validator->errors()->add(
                        'journal.' . $key,
                        'Each journal row must contain either a debit amount or a credit amount, but not both.'
                    );
                }
            }
        });


        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];
            return $this->journalStoreResponse($request, $output, 422);
        }

        try {
            // Resolve the user once. Some tenant installations keep the logged-in
            // user ID in the ERP session even when the default Auth guard is not
            // the active guard for an AJAX modal request.
            $created_by = (int) (Auth::id() ?: $request->session()->get('user.id'));
            if ($created_by <= 0) {
                return $this->journalStoreResponse($request, [
                    'success' => 0,
                    'msg' => 'Your login session could not be verified. Please refresh the page and try again.',
                ], 419);
            }

            // ✅ prevent if date already reviewed
            $has_reviewed = $this->transactionUtil->hasReviewed($request->date);
            if (! empty($has_reviewed)) {
                return $this->journalStoreResponse($request, [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ], 422);
            }

            $reviewed = $this->transactionUtil->get_review($request->date, $request->date);
            if (! empty($reviewed)) {
                return $this->journalStoreResponse($request, [
                    'success' => 0,
                    'msg'     => "You can't add a journal for an already reviewed date",
                ], 422);
            }

            $journals   = $request->journal;
            $total_amt  = 0;
            $all_notes  = "";

            DB::beginTransaction();

            $journal_last = Journal::where('business_id', $business_id)->lockForUpdate()->max('journal_id');
            $journal_id = ! empty($journal_last) ? $journal_last + 1 : 1;

            //collect per-row entries to create corresponding ledger items
            $per_row_for_ledger = [];
            $journal_table_columns = array_flip(Schema::getColumnListing('journals'));
            $account_transaction_columns = array_flip(Schema::getColumnListing('account_transactions'));

            foreach ($journals['account_type_id'] as $key => $account_type) {
                $note = ! empty($request->note) ? $request->note : null;
                $all_notes .= ! empty($request->note) ? $request->note . "\n" : "";

                $debit_amount  = ! empty($journals['debit_amount'][$key]) ? (float) $journals['debit_amount'][$key] : null;
                $credit_amount = ! empty($journals['credit_amount'][$key]) ? (float) $journals['credit_amount'][$key] : null;

                $data = [
                    'business_id'        => $business_id,
                    'journal_id'         => $journal_id,
                    'location_id'        => (int) $request->location_id,
                    'date'               => $this->canonicalJournalDate($request),
                    'debit_amount'       => $debit_amount ?: null,
                    'credit_amount'      => $credit_amount ?: null,
                    'account_type_id'    => (int) $journals['account_type_id'][$key],
                    'account_id'         => (int) $journals['account_id'][$key],
                    'note'               => $note,
                    'is_opening_balance' => $request->is_opening_balance,
                    'added_by'           => $created_by,
                ];

                $optional_journal_data = [
                    'customer_id'    => ($request->show_in_ledger === 'customer') ? $request->ledger_holder : null,
                    'supplier_id'    => ($request->show_in_ledger === 'supplier') ? $request->ledger_holder : null,
                    'pump_operator'  => ($request->show_in_ledger === 'pump_operator') ? $request->ledger_holder : null,
                    'show_in_ledger' => $request->show_in_ledger,
                    'show_in'        => $request->show_in_ledger === 'no' ? null : $request->show_in,
                ];

                foreach ($optional_journal_data as $column => $value) {
                    if (isset($journal_table_columns[$column])) {
                        $data[$column] = $value;
                    }
                }

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

                /*
                 |------------------------------------------------------------------
                 | IS2055 #2: the ledger showed DOUBLE the journal amount.
                 |------------------------------------------------------------------
                 |
                 | Reported: a journal of 5 appeared as 10 in the customer and
                 | supplier ledgers.
                 |
                 | This loop runs once per journal LINE, and a balanced journal has at
                 | least two - one debit and one credit, each 5. Adding every line to
                 | $total_amt gave 10, and that figure is written to the ledger
                 | transaction's final_total further down.
                 |
                 | The value of a journal is ONE side of it: a 5 debit with a matching
                 | 5 credit is a journal worth 5. Only the debit side is accumulated
                 | now.
                 |
                 | $total_amt is used ONLY for the ledger transaction. The individual
                 | account_transactions rows below are untouched - each still carries
                 | its own debit or credit amount, so the accounting stays balanced.
                 */
                if ($type === 'debit') {
                    $total_amt += $amount;
                }

                $acc_tran = [
                    'account_id'     => $journal->account_id,
                    'type'           => $type,
                    'amount'         => $amount,
                    'operation_date' => $this->canonicalJournalDate($request),
                    'created_by'     => $journal->added_by,
                    'note'           => $note,
                ];

                if (isset($account_transaction_columns['business_id'])) {
                    $acc_tran['business_id'] = $business_id;
                }
                if (isset($account_transaction_columns['journal_entry'])) {
                    $acc_tran['journal_entry'] = $journal->id;
                }

                AccountTransaction::create($acc_tran);

                if ($request->is_opening_balance == 'yes') {
                    app(FinanceAccountController::class)
                        ->addAccountOpeningBalance($amount, $journal['account_id'], $this->canonicalJournalDate($request), $note);
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
                /*
                 |------------------------------------------------------------------
                 | IS2055 #1: journals never appeared in the PUMP OPERATOR ledger.
                 |------------------------------------------------------------------
                 |
                 | The operator ledger selects on
                 |
                 |     transactions.pump_operator_id = <operator>
                 |
                 | (PumpOperatorController::__transactionQuery), but this only ever
                 | wrote contact_id - so an operator journal matched nothing and the
                 | ledger stayed empty.
                 |
                 | When the ledger holder IS an operator, the id is written to both
                 | columns: contact_id as before, and pump_operator_id so the operator
                 | ledger can find it. Customer and supplier journals are unaffected -
                 | pump_operator_id stays null for them.
                 |
                 | Guarded on the column existing, since older installs may not have
                 | it and a missing column would abort the whole journal save.
                 */
                $ledger_payload = [
                    'business_id'      => $business_id,
                    'location_id'      => $request->location_id,
                    'type'             => 'ledger',
                    'contact_id'       => $contact_id,
                    'invoice_no'       => "Journal: " . $journal_id,
                    'total_before_tax' => $total_amt,
                    'transaction_date' => $this->canonicalJournalDate($request),
                    'final_total'      => $total_amt,
                    'additional_notes' => $all_notes,
                    'created_by'       => request()->session()->get('user.id'),
                ];

                if ($request->show_in_ledger === 'pump_operator'
                    && \Illuminate\Support\Facades\Schema::hasColumn('transactions', 'pump_operator_id')) {
                    $ledger_payload['pump_operator_id'] = $contact_id;
                }

                $transaction = Transaction::create($ledger_payload);

                /*
                 |--------------------------------------------------------------
                 | IS2046: ONE ledger entry, in the column the operator chose.
                 |--------------------------------------------------------------
                 |
                 | This loop created one contact-ledger row per JOURNAL row, and
                 | set each one's type by inverting that row's own type:
                 |
                 |     $ledger_type = ($row['journal_type'] == 'debit') ? 'credit' : 'debit';
                 |
                 | A journal always has at least one debit row and one credit row,
                 | so the loop wrote a debit ledger entry AND a credit ledger
                 | entry for the same journal. That is the customer symptom - the
                 | same entry showing in both columns - and the supplier symptom
                 | too, since whichever of the two the supplier ledger picked up
                 | could be the opposite of what was asked for.
                 |
                 | "Show In" is the operator's answer to which column this should
                 | land in, and it was being ignored here: the line that used it
                 | is sitting commented out immediately above the line that
                 | overrode it.
                 |
                 | update() already does this correctly - a single entry, typed by
                 | $request->show_in, for the journal total. Create now matches it,
                 | so saving and then editing a journal cannot produce different
                 | ledger rows.
                 |
                 | The per-row note, cheque number and journal_row_id are kept by
                 | folding them into the single entry, so nothing that was
                 | recorded before is lost.
                 */
                $ledger_notes = [];
                $ledger_cheque_numbers = [];
                $first_journal_row_id = null;

                foreach ($per_row_for_ledger as $row) {
                    if (! empty($row['note'])) {
                        $ledger_notes[] = $row['note'];
                    }

                    if (! empty($row['cheque_number'])) {
                        $ledger_cheque_numbers[] = $row['cheque_number'];
                    }

                    if ($first_journal_row_id === null && ! empty($row['journal_row_id'])) {
                        $first_journal_row_id = $row['journal_row_id'];
                    }
                }

                $ledger_data = [
                    "business_id"    => $business_id,
                    "created_by"     => request()->session()->get('user.id'),
                    "contact_id"     => $contact_id,
                    // The column the operator selected, not the inverse of a row type.
                    "type"           => $request->show_in,
                    "amount"         => $total_amt,
                    "transaction_id" => $transaction->id,
                    "operation_date" => $this->canonicalJournalDate($request),
                    "note"           => ! empty($ledger_notes) ? implode(' | ', array_unique($ledger_notes)) : null,
                    "cheque_number"  => ! empty($ledger_cheque_numbers) ? implode(', ', array_unique($ledger_cheque_numbers)) : null,
                    "journal_row_id" => $first_journal_row_id,
                    "description"    => "Journal " . $journal_id,
                ];

                ContactLedger::create($ledger_data);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('account.journal_add_succuss'),
                'journal_id' => $journal_id,
            ];
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency('Finance journal save failed', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $this->journalStoreResponse(
            $request,
            $output,
            ! empty($output['success']) ? 200 : 500
        );
    }

    /**
     * Return JSON to the AJAX modal and preserve the normal redirect fallback
     * for installations that still submit the journal as a standard form.
     */
    private function journalStoreResponse(Request $request, array $output, int $statusCode = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($output, $statusCode);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * IS2034 #2: this was an empty stub, so the Actions menu had no View option to
     * offer. It returns a read-only modal for the whole journal entry - every row
     * sharing the same journal_id, not just the row that was clicked - because a
     * journal is only meaningful as a balanced set of lines.
     *
     * Read-only by design: editing stays in edit(), which already handles the
     * ledger-holder and account-row rebuilding.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $business_id = $this->businessId(request());

        $journal = Journal::where('business_id', $business_id)->findOrFail($id);

        $journal_table_columns = array_flip(Schema::getColumnListing('journals'));

        $journals = Journal::query()
            ->leftJoin('accounts as journal_accounts', 'journals.account_id', '=', 'journal_accounts.id')
            ->leftJoin('users as journal_users', 'journals.added_by', '=', 'journal_users.id')
            ->where('journals.business_id', $business_id)
            ->where('journals.journal_id', $journal->journal_id)
            ->select([
                'journals.*',
                'journal_accounts.name as account_name',
                'journal_users.username as user',
            ])
            ->orderBy('journals.id')
            ->get();

        $location_name = null;
        if (! empty($journal->location_id)) {
            $location_name = BusinessLocation::where('business_id', $business_id)
                ->where('id', $journal->location_id)
                ->value('name');
        }

        // The ledger-link columns are optional on some tenants - see
        // Database/SQL/IS1992_Journals_Add_Ledger_Columns.sql - so resolve the
        // holder name only when the columns are actually present.
        $ledger_holder_name = null;
        $show_in_ledger = isset($journal_table_columns['show_in_ledger'])
            ? $journal->show_in_ledger
            : null;

        if (! empty($show_in_ledger) && $show_in_ledger !== 'no') {
            $ledger_holder_name = $this->resolveLedgerHolderName($business_id, $journal, $journal_table_columns);
        }

        return view('finance::journal.show')->with(compact(
            'journal',
            'journals',
            'location_name',
            'show_in_ledger',
            'ledger_holder_name'
        ));
    }

    /**
     * Human-readable name of the customer, supplier or pump operator a journal is
     * linked to. Returns null when the link cannot be resolved, so the view can
     * simply omit the line rather than showing a bare id.
     */
    private function resolveLedgerHolderName(int $business_id, $journal, array $journal_table_columns): ?string
    {
        try {
            if ($journal->show_in_ledger === 'pump_operator') {
                if (! isset($journal_table_columns['pump_operator']) || empty($journal->pump_operator)) {
                    return null;
                }

                return collect($this->pumpOperators($business_id))->get($journal->pump_operator);
            }

            $contact_id = null;
            if ($journal->show_in_ledger === 'customer' && isset($journal_table_columns['customer_id'])) {
                $contact_id = $journal->customer_id;
            } elseif ($journal->show_in_ledger === 'supplier' && isset($journal_table_columns['supplier_id'])) {
                $contact_id = $journal->supplier_id;
            }

            if (empty($contact_id)) {
                return null;
            }

            return Contact::where('business_id', $business_id)
                ->where('id', $contact_id)
                ->value('name');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = $this->businessId(request());

        // Fetch required dropdowns
        $locations = BusinessLocation::forDropdown($business_id);
        $accounts  = Account::where('business_id', $business_id)
            ->notClosed()
            ->pluck('name', 'id');

        $account_types = $this->parentAccountTypesForDropdown($business_id);

        // Fetch the main journal entry
        $journal    = Journal::where('business_id', $business_id)->findOrFail($id);
        $journal_id = $journal->journal_id;

        // Fetch all journals with the same journal_id (grouped journal entries)
        $journals = Journal::where('business_id', $business_id)
            ->where('journal_id', $journal_id)
            ->get();

    $customers = Contact::customersDropdown($business_id, false);
    $suppliers = Contact::suppliersDropdown($business_id, false);
    $pump_operators = $this->pumpOperators($business_id);
    $settlement_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'settlement_sw_module');
    $petro_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_module');
    $cash_account_id = Account::where('business_id', $business_id)
        ->where('name', 'Cash')
        ->value('id');

    $ledger_holder = null;
    if ($journal->show_in_ledger == 'customer') {
        $ledger_holder = $journal->customer_id ?? null;
    } else if ($journal->show_in_ledger == 'supplier') {
        $ledger_holder = $journal->supplier_id ?? null;
    } else if ($journal->show_in_ledger == 'pump_operator') {
        $ledger_holder = $journal->pump_operator ?? null;
    }

    return view('finance::journal.edit')->with(compact(
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
        'ledger_holder',
        'cash_account_id'
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
        $business_id = $this->businessId($request);

        // IS2068: never trust a posted journal number for an edit. Resolve the
        // journal group from the row id and enforce the payment lock server-side.
        $existing_journal = Journal::where('business_id', $business_id)->findOrFail($id);
        $journal_id = (int) $existing_journal->journal_id;

        if ($this->journalHasCompletedPayment((int) $business_id, $journal_id)) {
            $output = [
                'success' => 0,
                'msg' => 'This journal can no longer be edited because a payment transaction has already been completed against it.',
            ];

            return $this->journalStoreResponse($request, $output, 409);
        }

        $request->merge([
            'note' => trim((string) $request->input('note', '')),
            // Keep the authoritative raw number in the request for legacy code
            // that still reads journal_id from it. The form displays JOUR0006.
            'journal_id' => $journal_id,
        ]);

        $resolved_date = $this->resolveJournalDate($request->input('date'));

        $request->merge([
            'date' => $resolved_date ?? $request->input('date'),
            'date_valid' => $resolved_date !== null ? 1 : 0,
        ]);

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|integer',
            'date' => 'required',
            'date_valid' => 'accepted',
            'note' => 'required|string|max:2000',
            'debit_total' => 'required|numeric|gt:0',
            'credit_total' => 'required|numeric|same:debit_total',
            'show_in_ledger' => 'required|in:no,customer,supplier,pump_operator',
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
        ], [
            'note.required' => 'The Note field is mandatory for every journal entry.',
            'credit_total.same' => 'The debit and credit totals must be equal.',
            'date_valid.accepted' => 'The date could not be read. Please pick the date again using the date field.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($this->journalLedgerSchemaIssues((string) $request->input('show_in_ledger', 'no')) as $schemaIssue) {
                $validator->errors()->add('show_in_ledger', $schemaIssue);
            }
        });

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg' => $validator->errors()->all()[0],
            ];
            return $this->journalStoreResponse($request, $output, 422);
        }

        try {
            $journals = (array) $request->input('journal', []);

            $has_reviewed = $this->transactionUtil->hasReviewed($request->date);
            if (! empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];
                return $this->journalStoreResponse($request, $output, 409);
            }

            $reviewed = $this->transactionUtil->get_review($request->date, $request->date);
            if (! empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't edit a journal for an already reviewed date",
                ];
                return $this->journalStoreResponse($request, $output, 409);
            }

            DB::beginTransaction();

            $total_amt = 0;
            $journal_update_table_columns = array_flip(Schema::getColumnListing('journals'));
            $existing_row_ids = Journal::where('business_id', $business_id)
                ->where('journal_id', $journal_id)
                ->pluck('id')
                ->map(static fn ($value) => (int) $value)
                ->all();
            $submitted_row_ids = [];
            $current_journal_row_ids = [];

            foreach ($journals as $journal) {
                $note = ! empty($request->note) ? $request->note : null;
                $data = [
                    'business_id' => $business_id,
                    'journal_id' => $journal_id,
                    'location_id' => $request->location_id,
                    'date' => $this->canonicalJournalDate($request),
                    'debit_amount' => ! empty($journal['debit_amount']) ? $journal['debit_amount'] : null,
                    'credit_amount' => ! empty($journal['credit_amount']) ? $journal['credit_amount'] : null,
                    'account_type_id' => $journal['account_type_id'],
                    'account_id' => $journal['account_id'],
                    'note' => $note,
                    'is_opening_balance' => $request->is_opening_balance,
                    'added_by' => Auth::user()->id,
                ];

                $optional_journal_data = [
                    'customer_id' => ($request->show_in_ledger === 'customer') ? $request->ledger_holder : null,
                    'supplier_id' => ($request->show_in_ledger === 'supplier') ? $request->ledger_holder : null,
                    'pump_operator' => ($request->show_in_ledger === 'pump_operator') ? $request->ledger_holder : null,
                    'show_in_ledger' => $request->show_in_ledger,
                    'show_in' => $request->show_in_ledger === 'no' ? null : $request->show_in,
                ];

                foreach ($optional_journal_data as $column => $value) {
                    if (isset($journal_update_table_columns[$column])) {
                        $data[$column] = $value;
                    }
                }

                $row_id = ! empty($journal['id']) ? (int) $journal['id'] : 0;
                if ($row_id > 0) {
                    if (! in_array($row_id, $existing_row_ids, true)) {
                        throw new \RuntimeException('A submitted journal row does not belong to this journal entry.');
                    }

                    Journal::where('business_id', $business_id)
                        ->where('journal_id', $journal_id)
                        ->where('id', $row_id)
                        ->update($data);
                    $submitted_row_ids[] = $row_id;
                    $current_journal_row_ids[] = $row_id;
                } else {
                    $new_journal = Journal::create($data);
                    $row_id = (int) $new_journal->id;
                    $current_journal_row_ids[] = $row_id;
                }

                if (! empty($journal['debit_amount'])) {
                    $type = 'debit';
                    $amount = (float) $journal['debit_amount'];
                } else {
                    $type = 'credit';
                    $amount = (float) $journal['credit_amount'];
                }

                if ($type === 'debit') {
                    $total_amt += $amount;
                }

                $acc_tran = [
                    'account_id' => $journal['account_id'],
                    'type' => $type,
                    'amount' => $amount,
                    'operation_date' => $this->canonicalJournalDate($request),
                    'created_by' => Auth::user()->id,
                    'note' => $note,
                ];

                $existing_acc_transaction = AccountTransaction::where('business_id', $business_id)
                    ->where('journal_entry', $row_id)
                    ->first();

                if ($existing_acc_transaction) {
                    $existing_acc_transaction->update($acc_tran);
                } else {
                    $acc_tran['business_id'] = $business_id;
                    $acc_tran['journal_entry'] = $row_id;
                    AccountTransaction::create($acc_tran);
                }

                if ($request->is_opening_balance === 'yes') {
                    $opening_balance = AccountTransaction::where('sub_type', 'opening_balance')
                        ->where('account_id', $journal['account_id'])
                        ->first();
                    if (! empty($opening_balance)) {
                        $opening_balance->note = $note;
                        $opening_balance->amount = $amount;
                        $opening_balance->operation_date = $this->canonicalJournalDate($request);
                        $opening_balance->save();
                    } else {
                        app(FinanceAccountController::class)->addAccountOpeningBalance(
                            $amount,
                            $journal['account_id'],
                            $this->canonicalJournalDate($request),
                            $note
                        );
                    }
                }
            }

            // IS2068: if a line was removed in Edit, remove its live account
            // transaction and journal row in the same DB transaction. Previously
            // stale lines remained in Account Books after the modal was saved.
            $removed_row_ids = array_values(array_diff($existing_row_ids, $submitted_row_ids));
            if (! empty($removed_row_ids)) {
                AccountTransaction::where('business_id', $business_id)
                    ->whereIn('journal_entry', $removed_row_ids)
                    ->delete();
                Journal::where('business_id', $business_id)
                    ->where('journal_id', $journal_id)
                    ->whereIn('id', $removed_row_ids)
                    ->delete();
            }

            $all_notes = $request->note;
            $contact_id = $request->show_in_ledger !== 'no' ? (int) $request->ledger_holder : null;

            if (! empty($request->show_in_ledger) && ! empty($contact_id)) {
                $transaction = Transaction::where('business_id', $business_id)
                    ->where('invoice_no', 'Journal: ' . $journal_id)
                    ->first();

                $transaction_data = [
                    'business_id' => $business_id,
                    'location_id' => $request->location_id,
                    'type' => 'ledger',
                    'contact_id' => $contact_id,
                    'invoice_no' => 'Journal: ' . $journal_id,
                    'total_before_tax' => $total_amt,
                    'transaction_date' => $this->canonicalJournalDate($request),
                    'final_total' => $total_amt,
                    'additional_notes' => $all_notes,
                    'created_by' => request()->session()->get('user.id'),
                ];

                if (Schema::hasColumn('transactions', 'pump_operator_id')) {
                    $transaction_data['pump_operator_id'] = $request->show_in_ledger === 'pump_operator'
                        ? $contact_id
                        : null;
                }

                if ($transaction) {
                    $transaction->fill($transaction_data);
                    $transaction->save();
                } else {
                    $transaction = Transaction::create($transaction_data);
                }

                $first_journal_row_id = ! empty($current_journal_row_ids)
                    ? (int) reset($current_journal_row_ids)
                    : null;

                $ledger_data = [
                    'business_id' => $business_id,
                    'created_by' => request()->session()->get('user.id'),
                    'contact_id' => $contact_id,
                    'type' => $request->show_in,
                    'amount' => $total_amt,
                    'transaction_id' => $transaction->id,
                    'operation_date' => $this->canonicalJournalDate($request),
                    'note' => $all_notes,
                    'journal_row_id' => $first_journal_row_id,
                    'description' => 'Journal ' . $journal_id,
                ];

                $ledger = ContactLedger::where('business_id', $business_id)
                    ->where('transaction_id', $transaction->id)
                    ->first();
                if ($ledger) {
                    $ledger->update($ledger_data);
                } else {
                    ContactLedger::create($ledger_data);
                }
            } else {
                $transaction = Transaction::where('business_id', $business_id)
                    ->where('invoice_no', 'Journal: ' . $journal_id)
                    ->first();
                if ($transaction) {
                    ContactLedger::where('business_id', $business_id)
                        ->where('transaction_id', $transaction->id)
                        ->delete();
                    $transaction->delete();
                }
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('account.journal_update_succuss'),
                'journal_id' => $journal_id,
            ];
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency('Finance journal update failed', [
                'business_id' => $business_id,
                'journal_id' => $journal_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $this->journalStoreResponse(
            $request,
            $output,
            ! empty($output['success']) ? 200 : 500
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $business_id = $this->businessId(request());
        $journalId = (int) $id;

        try {
            $journals = Journal::where('business_id', $business_id)
                ->where('journal_id', $journalId)
                ->get();

            if ($journals->isEmpty()) {
                return [
                    'success' => 0,
                    'msg' => 'Journal entry not found.',
                ];
            }

            // IS2073: UI lock and server-side lock use the same authoritative
            // payment detector. A direct DELETE request cannot bypass it.
            if ($this->journalHasCompletedPayment((int) $business_id, $journalId)) {
                return [
                    'success' => 0,
                    'msg' => 'This journal can no longer be deleted because a payment transaction has already been completed against it.',
                ];
            }

            $has_reviewed = $this->transactionUtil->hasReviewed($journals[0]->date);
            if (! empty($has_reviewed)) {
                return [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];
            }

            $reviewed = $this->transactionUtil->get_review($journals[0]->date, $journals[0]->date);
            if (! empty($reviewed)) {
                return [
                    'success' => 0,
                    'msg' => "You can't delete a journal for an already reviewed date",
                ];
            }

            DB::beginTransaction();

            $journalRowIds = $journals->pluck('id')
                ->map(static fn ($value) => (int) $value)
                ->filter()
                ->values()
                ->all();

            /*
             * Previous code flipped each account transaction from debit to credit
             * (or vice versa) and left it live. That did not remove the journal's
             * accounting effect; it changed the effect to the opposite side.
             *
             * AccountTransaction uses SoftDeletes, so delete the linked rows.
             * This removes the deleted journal from Account Books/balances while
             * preserving the audit/history row in the database.
             */
            if ($journalRowIds !== []) {
                $accountTransactions = AccountTransaction::where('business_id', $business_id)
                    ->whereIn('journal_entry', $journalRowIds)
                    ->get();

                foreach ($accountTransactions as $accountTransaction) {
                    if (Schema::hasColumn('account_transactions', 'journal_deleted')) {
                        $accountTransaction->journal_deleted = 1;
                        $accountTransaction->save();
                    }
                    $accountTransaction->delete();
                }
            }

            foreach ($journals as $journal) {
                $journal->delete();
            }

            $transaction = Transaction::where('business_id', $business_id)
                ->where('invoice_no', 'Journal: ' . $journalId)
                ->first();

            if ($transaction) {
                ContactLedger::where('business_id', $business_id)
                    ->where('transaction_id', $transaction->id)
                    ->delete();

                $transaction->delete();
            }

            DB::commit();

            return [
                'success' => 1,
                'msg' => __('account.journal_delete_succuss'),
            ];
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency('Finance journal delete failed', [
                'business_id' => $business_id,
                'journal_id' => $journalId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
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
        $business_id = $this->businessId($request);
        $accounts      = Account::where('business_id', $business_id)->notClosed()->pluck('name', 'id');
        $account_types = $this->parentAccountTypesForDropdown($business_id);

        return view('finance::journal.get_row')->with(compact('accounts', 'index', 'account_types'));
    }
    /*
     | Sub types of the chosen Account Type.
     |
     | Assets and Liabilities hold their accounts in sub types; Income, Expenses
     | and Equity have none and keep theirs directly. So an empty list is normal
     | rather than an error - the dropdown says "No Account Sub Types" and the
     | Account list stays as it was.
    */
    /**
     * Account types, their sub types, and the accounts under each — in one array.
     *
     * Shape:
     *   [
     *     'types'    => [ ['id'=>10,'name'=>'Assets'], ... ],   // top level only
     *     'subs'     => [ 10 => [ ['id'=>15,'name'=>'Current Assets'], ... ] ],
     *     'accounts' => [ 15 => [ ['id'=>3,'name'=>'Petty Cash'], ... ] ],
     *   ]
     *
     * `accounts` is keyed by whichever type the account is attached to, parent
     * or sub. The page resolves a parent selection by unioning its own accounts
     * with those of its children, exactly as the endpoint does — kept on the
     * client so the answer is instant.
     *
     * THREE QUERIES, not N. Building this per account type would be the same
     * N+1 in a different place; the whole tree costs one query each for types
     * and accounts.
     */
    private function journalAccountLookup($businessId): array
    {
        $allTypes = \DB::table('account_types')
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_account_type_id']);

        $types = [];
        $subs  = [];

        foreach ($allTypes as $type) {
            $parentId = (int) ($type->parent_account_type_id ?? 0);

            if ($parentId === 0) {
                $types[] = ['id' => (int) $type->id, 'name' => $type->name];
                continue;
            }

            $subs[$parentId][] = ['id' => (int) $type->id, 'name' => $type->name];
        }

        /*
         | Same rule as the dropdown helper: a tenant with no top-level rows at
         | all gets everything, so the form stays usable rather than empty.
         */
        if (empty($types)) {
            foreach ($allTypes as $type) {
                $types[] = ['id' => (int) $type->id, 'name' => $type->name];
            }
            $subs = [];
        }

        $accounts = [];

        /*
         | default_account_id is NOT an exclusion.
         |
         | It marks an account created from the standard chart of accounts when
         | the business was set up - Cash, Bank, Accounts Receivable, Sales and
         | the rest (see BusinessController, which stamps it at creation). They
         | are ordinary, postable accounts.
         |
         | Filtering them out left only manually-added accounts: on this
         | business, 46 of 64 were hidden, and Liabilities offered exactly one.
         | A journal that cannot touch Cash or Sales is not much of a journal.
         |
         | Closed accounts are still excluded, which is the filter that actually
         | means "do not post to this".
        */
        $accountRows = Account::where('business_id', $businessId)
            ->notClosed()
            ->orderBy('name')
            ->get(['id', 'name', 'account_type_id']);

        foreach ($accountRows as $account) {
            $typeId = (int) $account->account_type_id;
            $accounts[$typeId][] = ['id' => (int) $account->id, 'name' => $account->name];
        }

        return [
            'types'    => $types,
            'subs'     => $subs,
            'accounts' => $accounts,
        ];
    }

    /**
     * Top-level Account Types only, for the journal form's first dropdown.
     *
     * WHY NOT AccountType::forDropdown()
     *   That helper returns EVERY row in account_types with no filter on
     *   parent_account_type_id - so the "Select Account Type" dropdown listed
     *   the sub types alongside the types they belong to.
     *
     *   Beyond being confusing, it broke the cascade: choosing a sub type from
     *   that list asked getAccountSubTypes() for ITS children, of which there
     *   are none, so the Account Sub Type dropdown showed "No Account Sub
     *   Types" and the form looked broken.
     *
     *   forDropdown() is an application-level helper used elsewhere, so it is
     *   left alone and the journal form filters for itself.
     *
     * Both NULL and 0 count as top level: whichever a tenant uses for a root
     * row, it must not be treated as a child of account type zero.
     */
    private function parentAccountTypesForDropdown($businessId)
    {
        $parents = \DB::table('account_types')
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereNull('parent_account_type_id')
                    ->orWhere('parent_account_type_id', 0);
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        /*
         | Fallback: if a tenant has no top-level rows at all - every account
         | type carrying a parent, which should not happen but does on data
         | imported oddly - return everything rather than an empty dropdown.
         |
         | An unusable form is worse than a slightly untidy list, and an empty
         | Account Type dropdown gives the user nothing to act on at all.
         */
        if ($parents->isEmpty()) {
            return \DB::table('account_types')
                ->where('business_id', $businessId)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        return $parents;
    }

    public function getAccountSubTypes($id)
    {
        $businessId = $this->businessId(request());

        $subTypes = \DB::table('account_types')
            ->where('business_id', $businessId)
            ->where('parent_account_type_id', (int) $id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return response()->json([
            'sub_types' => $subTypes,
            'has_any'   => $subTypes->isNotEmpty(),
        ]);
    }

    /**
     * Accounts for a chosen Account Type OR Account Sub Type.
     *
     * WHAT CHANGED AND WHY
     *   This matched accounts on the given id alone. That is right when a SUB
     *   TYPE is chosen, because an account under Assets is attached to
     *   "Current Assets" rather than to "Assets" itself.
     *
     *   But it left the Account dropdown EMPTY whenever a parent type was
     *   selected and the sub type was left on "All Sub Types" - which is the
     *   first thing anyone does. The accounts existed; they were simply hanging
     *   off the children of the id being asked about.
     *
     *   The id's children are now included, so:
     *     - pick "Assets"                -> every account under all its sub types
     *     - pick "Assets" > "Current"    -> only that sub type's accounts
     *     - pick "Expenses" (no children)-> its own accounts, as before
     *
     *   Selecting the parent therefore shows everything available, and choosing
     *   a sub type narrows it - which is how the two dropdowns are meant to read
     *   together.
     */
    public function getAccountDropdownByAccountType($account_type_id)
    {
        $businessId = $this->businessId(request());
        $accountTypeId = (int) $account_type_id;

        $typeIds = [$accountTypeId];

        // Children of the selected type, where it has any. Income, Expenses and
        // Equity have none, so this simply adds nothing for them.
        $childIds = \DB::table('account_types')
            ->where('business_id', $businessId)
            ->where('parent_account_type_id', $accountTypeId)
            ->pluck('id')
            ->all();

        if (! empty($childIds)) {
            $typeIds = array_merge($typeIds, $childIds);
        }

        $accounts = Account::where('business_id', $businessId)
            ->whereIn('account_type_id', $typeIds)
            ->notClosed()
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->transactionUtil->createDropdownHtml($accounts, 'Please select');
    }

    /**
     * Apply all Journal List filters using unambiguous journal table columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    /**
     * S-666 #2: grouped options for the Ledger Holder filter dropdown.
     *
     * Keys are "type:id" so applyJournalFilters() can tell a customer from a
     * supplier with the same numeric id. Groups are only included when the
     * matching column exists on this tenant.
     */
    private function ledgerHolderFilterOptions(int $businessId): array
    {
        $journalTableColumns = array_flip(Schema::getColumnListing('journals'));
        $names = $this->ledgerHolderNameMap($businessId, $journalTableColumns);

        $labels = [
            'customer' => __('account.customer_ledger'),
            'supplier' => __('account.supplier_ledger'),
            'pump_operator' => __('account.pump_operator'),
        ];

        $options = [];
        foreach ($names as $type => $holders) {
            if (empty($holders)) {
                continue;
            }

            $group = [];
            foreach ($holders as $id => $name) {
                $group[$type . ':' . $id] = $name;
            }

            $options[$labels[$type] ?? $type] = $group;
        }

        return $options;
    }

    /**
     * S-666 #2: id => name for every possible ledger holder, keyed by ledger type.
     *
     * Built once per request and handed to the row callback. Returns empty lists
     * for any type whose column is missing on this tenant, so the caller can fall
     * back to "-" without checking again.
     */
    private function ledgerHolderNameMap(int $businessId, array $journalTableColumns): array
    {
        $map = ['customer' => [], 'supplier' => [], 'pump_operator' => []];

        if (! isset($journalTableColumns['show_in_ledger'])) {
            return $map;
        }

        try {
            if (isset($journalTableColumns['customer_id'])) {
                $map['customer'] = collect(Contact::customersDropdown($businessId, false))->toArray();
            }
            if (isset($journalTableColumns['supplier_id'])) {
                $map['supplier'] = collect(Contact::suppliersDropdown($businessId, false))->toArray();
            }
            if (isset($journalTableColumns['pump_operator'])) {
                $map['pump_operator'] = collect($this->pumpOperators($businessId))->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('Finance journal ledger-holder name lookup failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
        }

        return $map;
    }

    private function applyJournalFilters($query, Request $request, ?array $permittedLocationIds, array $journalTableColumns = []): void
    {
        /*
         * S-666 #2: Ledger Holder and Status filters.
         *
         * Both depend on the optional ledger columns, so they are skipped entirely
         * on a tenant that does not have them - filtering on a column that is not
         * there is what broke this list in IS1992.
         */
        if (isset($journalTableColumns['show_in_ledger'])) {
            if ($request->filled('ledger_holder')) {
                // Value arrives as "type:id", e.g. "supplier:42".
                $parts = explode(':', (string) $request->input('ledger_holder'), 2);
                $type = $parts[0] ?? '';
                $holderId = isset($parts[1]) ? (int) $parts[1] : 0;

                $columnForType = [
                    'customer' => 'customer_id',
                    'supplier' => 'supplier_id',
                    'pump_operator' => 'pump_operator',
                ];

                if (isset($columnForType[$type], $journalTableColumns[$columnForType[$type]]) && $holderId > 0) {
                    $query->where('journals.show_in_ledger', $type)
                        ->where('journals.' . $columnForType[$type], $holderId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($request->filled('ledger_status')) {
                if ($request->input('ledger_status') === 'linked') {
                    $query->whereNotNull('journals.show_in_ledger')
                        ->where('journals.show_in_ledger', '!=', 'no');
                } elseif ($request->input('ledger_status') === 'not_linked') {
                    $query->where(function ($statusQuery) {
                        $statusQuery->whereNull('journals.show_in_ledger')
                            ->orWhere('journals.show_in_ledger', 'no');
                    });
                }
            }
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('journals.date', '>=', $request->input('start_date'))
                ->whereDate('journals.date', '<=', $request->input('end_date'));
        }

        if ($request->filled('account_id')) {
            $query->where('journals.account_id', (int) $request->input('account_id'));
        }

        if ($request->filled('journal_entry_type')) {
            if ($request->input('journal_entry_type') === 'opening') {
                $query->where('journals.is_opening_balance', 'yes');
            } elseif ($request->input('journal_entry_type') === 'regular') {
                $query->where(function ($typeQuery) {
                    $typeQuery->whereNull('journals.is_opening_balance')
                        ->orWhere('journals.is_opening_balance', '!=', 'yes');
                });
            }
        }

        if ($request->filled('location_id')) {
            $locationId = (int) $request->input('location_id');

            if (is_array($permittedLocationIds) && ! in_array($locationId, $permittedLocationIds, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('journals.location_id', $locationId);
            }

            return;
        }

        if (is_array($permittedLocationIds)) {
            if (empty($permittedLocationIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('journals.location_id', $permittedLocationIds);
            }
        }
    }

    /**
     * Resolve the intersection of the logged-in user's assigned locations and
     * the Finance module's configured locations. Null means unrestricted.
     */
    private function permittedLocationIds(int $businessId): ?array
    {
        $locationSets = [];

        try {
            $user = Auth::user();
            if (is_object($user) && method_exists($user, 'permitted_locations')) {
                $userLocations = $user->permitted_locations();
                if ($userLocations !== 'all' && is_array($userLocations)) {
                    $locationSets[] = array_values(array_unique(array_map('intval', $userLocations)));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Finance journal user-location lookup failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            if (Schema::hasTable('module_permission_locations')) {
                $moduleLocations = ModulePermissionLocation::getModulePermissionLocations(
                    $businessId,
                    'accounting_module'
                );

                if (! empty($moduleLocations) && is_array($moduleLocations->locations) && ! empty($moduleLocations->locations)) {
                    $locationSets[] = array_values(array_unique(array_map(
                        'intval',
                        array_keys($moduleLocations->locations)
                    )));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Finance journal module-location lookup failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
        }

        if (empty($locationSets)) {
            return null;
        }

        $permitted = array_shift($locationSets);
        foreach ($locationSets as $locationSet) {
            $permitted = array_values(array_intersect($permitted, $locationSet));
        }

        return array_values(array_unique(array_map('intval', $permitted)));
    }

    /**
     * IS2055 #4: journal_ids that already have a payment against them.
     *
     * A journal writes a `transactions` row with invoice_no "Journal: {id}" -
     * the same key the update path uses to find it again. A payment made from a
     * related page records a `transaction_payments` row against that
     * transaction.
     *
     * So a journal is "paid" when a transaction_payments row exists for its
     * transaction. Once that is true, Edit and Delete are withdrawn: changing or
     * removing the journal would leave the payment pointing at an amount that no
     * longer exists, or at nothing at all.
     *
     * Loaded for the whole page in ONE query and passed to the action closure,
     * rather than queried per row - a per-row lookup would add a query for every
     * line on every draw.
     *
     * Returns an empty set if either table is absent, so the buttons stay
     * available rather than the list erroring.
     */
    protected function paidJournalIds(int $businessId, array $journalIds): array
    {
        $journalIds = array_values(array_filter(array_unique(array_map('intval', $journalIds))));

        if ($journalIds === [] || ! Schema::hasTable('transactions')) {
            return [];
        }

        $invoiceNos = [];
        foreach ($journalIds as $id) {
            if ($id > 0) {
                $invoiceNos['Journal: ' . $id] = $id;
            }
        }

        if ($invoiceNos === []) {
            return [];
        }

        $transactionQuery = DB::table('transactions')
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.invoice_no', array_keys($invoiceNos));

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $transactionQuery->whereNull('transactions.deleted_at');
        }

        $transactions = $transactionQuery->get(['transactions.id', 'transactions.invoice_no']);

        if ($transactions->isEmpty()) {
            return [];
        }

        $transactionToJournal = [];
        $transactionIds = [];
        foreach ($transactions as $transaction) {
            $transactionId = (int) $transaction->id;
            $journalId = $invoiceNos[(string) $transaction->invoice_no] ?? null;
            if ($transactionId > 0 && $journalId) {
                $transactionIds[] = $transactionId;
                $transactionToJournal[$transactionId] = (int) $journalId;
            }
        }

        if ($transactionIds === []) {
            return [];
        }

        $paid = [];
        $paymentIds = [];

        /*
         * Direct payment: the normal path stores transaction_payments.transaction_id.
         */
        if (Schema::hasTable('transaction_payments')) {
            $directPaymentQuery = DB::table('transaction_payments')
                ->whereIn('transaction_payments.transaction_id', $transactionIds);

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $directPaymentQuery->whereNull('transaction_payments.deleted_at');
            }
            if (Schema::hasColumn('transaction_payments', 'amount')) {
                $directPaymentQuery->where('transaction_payments.amount', '>', 0);
            }

            foreach ($directPaymentQuery->pluck('transaction_payments.transaction_id')->all() as $transactionId) {
                $transactionId = (int) $transactionId;
                if (isset($transactionToJournal[$transactionId])) {
                    $paid[$transactionToJournal[$transactionId]] = true;
                }
            }
        }

        /*
         * Parent/allocated payment paths can update the related ledger row with a
         * transaction_payment_id while the payment row itself has no transaction_id.
         * Follow that explicit link as well, otherwise Edit/Delete can remain enabled
         * after a real customer/supplier/operator payment has been completed.
         */
        if (Schema::hasTable('contact_ledgers')
            && Schema::hasColumn('contact_ledgers', 'transaction_id')
            && Schema::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            $paymentIds = array_merge(
                $paymentIds,
                DB::table('contact_ledgers')
                    ->whereIn('transaction_id', $transactionIds)
                    ->whereNotNull('transaction_payment_id')
                    ->pluck('transaction_payment_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter()
                    ->all()
            );
        }

        if (Schema::hasTable('account_transactions')
            && Schema::hasColumn('account_transactions', 'transaction_id')
            && Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
            $accountPaymentQuery = DB::table('account_transactions')
                ->whereIn('transaction_id', $transactionIds)
                ->whereNotNull('transaction_payment_id');

            if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                $accountPaymentQuery->whereNull('deleted_at');
            }

            $paymentIds = array_merge(
                $paymentIds,
                $accountPaymentQuery->pluck('transaction_payment_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter()
                    ->all()
            );
        }

        $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds))));

        if ($paymentIds !== [] && Schema::hasTable('transaction_payments')) {
            $linkedPaymentQuery = DB::table('transaction_payments')
                ->whereIn('transaction_payments.id', $paymentIds);

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $linkedPaymentQuery->whereNull('transaction_payments.deleted_at');
            }
            if (Schema::hasColumn('transaction_payments', 'amount')) {
                $linkedPaymentQuery->where('transaction_payments.amount', '>', 0);
            }

            $validPaymentIds = array_flip(
                $linkedPaymentQuery->pluck('transaction_payments.id')
                    ->map(static fn ($id) => (int) $id)
                    ->all()
            );

            if ($validPaymentIds !== []) {
                if (Schema::hasTable('contact_ledgers')) {
                    $linkedLedgers = DB::table('contact_ledgers')
                        ->whereIn('transaction_id', $transactionIds)
                        ->whereIn('transaction_payment_id', array_keys($validPaymentIds))
                        ->get(['transaction_id', 'transaction_payment_id']);

                    foreach ($linkedLedgers as $ledger) {
                        $transactionId = (int) $ledger->transaction_id;
                        if (isset($transactionToJournal[$transactionId])) {
                            $paid[$transactionToJournal[$transactionId]] = true;
                        }
                    }
                }

                if (Schema::hasTable('account_transactions')) {
                    $linkedAccounts = DB::table('account_transactions')
                        ->whereIn('transaction_id', $transactionIds)
                        ->whereIn('transaction_payment_id', array_keys($validPaymentIds));

                    if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                        $linkedAccounts->whereNull('deleted_at');
                    }

                    foreach ($linkedAccounts->get(['transaction_id', 'transaction_payment_id']) as $accountTransaction) {
                        $transactionId = (int) $accountTransaction->transaction_id;
                        if (isset($transactionToJournal[$transactionId])) {
                            $paid[$transactionToJournal[$transactionId]] = true;
                        }
                    }
                }
            }
        }

        return $paid;
    }

    /**
     * IS2068: authoritative server-side payment lock for one journal.
     */
    protected function journalHasCompletedPayment(int $businessId, int $journalId): bool
    {
        return isset($this->paidJournalIds($businessId, [$journalId])[$journalId]);
    }

    /**
     * Plug-and-play journal ledger-link schema repair for older tenant DBs.
     *
     * Some tenants were created before the five ledger-link columns existed on
     * the shared `journals` table. Finance used to stop the save and tell the
     * user to run an SQL file manually. That is not suitable for this ERP's
     * non-technical deployments, so Finance now self-heals ONLY the missing
     * columns on the currently active tenant connection.
     *
     * The operation is idempotent and race-safe: every column is checked before
     * the ALTER, and if another request adds it at the same moment the second
     * request simply re-checks and continues.
     *
     * @return array<int, string> remaining schema errors, if automatic repair
     *                            genuinely could not complete
     */
    protected function ensureJournalLedgerSchema(): array
    {
        if (! Schema::hasTable('journals')) {
            return ['The journals table is unavailable in this tenant database.'];
        }

        $definitions = [
            'show_in_ledger' => static function ($table): void {
                $table->string('show_in_ledger', 20)->nullable()->default('no');
            },
            'show_in' => static function ($table): void {
                $table->string('show_in', 255)->nullable();
            },
            'customer_id' => static function ($table): void {
                $table->unsignedInteger('customer_id')->nullable();
            },
            'supplier_id' => static function ($table): void {
                $table->unsignedInteger('supplier_id')->nullable();
            },
            'pump_operator' => static function ($table): void {
                $table->unsignedInteger('pump_operator')->nullable();
            },
        ];

        try {
            foreach ($definitions as $column => $addColumn) {
                if (Schema::hasColumn('journals', $column)) {
                    continue;
                }

                try {
                    Schema::table('journals', function ($table) use ($addColumn): void {
                        $addColumn($table);
                    });
                } catch (\Throwable $e) {
                    // Two requests can discover the same missing column before
                    // either ALTER finishes. If the column now exists, the
                    // second request is already satisfied; otherwise this is a
                    // real DDL failure and must be surfaced safely.
                    if (! Schema::hasColumn('journals', $column)) {
                        throw $e;
                    }
                }
            }

            // IS2073 compatibility: a few older tenants used an ENUM for this
            // field that did not include `pump_operator`. Normalise that legacy
            // definition to the VARCHAR used by current Finance code. This runs
            // only when SHOW COLUMNS positively identifies an ENUM.
            try {
                $column = DB::selectOne("SHOW COLUMNS FROM `journals` WHERE `Field` = 'show_in_ledger'");
                $type = strtolower((string) ($column->Type ?? $column->type ?? ''));

                if (str_starts_with($type, 'enum(')) {
                    DB::statement("ALTER TABLE `journals` MODIFY `show_in_ledger` VARCHAR(20) NULL DEFAULT 'no'");
                }
            } catch (\Throwable $e) {
                // SHOW COLUMNS can be restricted on some shared hosts. Do not
                // fail otherwise-correct journal usage merely because the type
                // could not be inspected. A real incompatible ENUM will still
                // be caught below when pump_operator is requested.
                Log::warning('Finance journal ledger-link type inspection skipped', [
                    'message' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Finance could not auto-repair journal ledger-link schema', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return [
                'Finance could not prepare the journal ledger-link fields automatically. '
                . 'Please contact the system administrator and try again.',
            ];
        }

        $required = ['show_in_ledger', 'show_in', 'customer_id', 'supplier_id', 'pump_operator'];
        $missing = array_values(array_filter($required, static function (string $column): bool {
            return ! Schema::hasColumn('journals', $column);
        }));

        if ($missing !== []) {
            return [
                'Finance could not prepare the journal ledger-link fields automatically. Missing: '
                . implode(', ', $missing)
                . '.',
            ];
        }

        return [];
    }

    /**
     * Validate that the requested ledger link is usable after Finance has had
     * a chance to self-heal the active tenant schema.
     *
     * @return array<int, string>
     */
    protected function journalLedgerSchemaIssues(string $ledgerType): array
    {
        if ($ledgerType === '' || $ledgerType === 'no') {
            return [];
        }

        $repairIssues = $this->ensureJournalLedgerSchema();
        if ($repairIssues !== []) {
            return $repairIssues;
        }

        $requiredColumns = ['show_in_ledger', 'show_in'];
        if ($ledgerType === 'customer') {
            $requiredColumns[] = 'customer_id';
        } elseif ($ledgerType === 'supplier') {
            $requiredColumns[] = 'supplier_id';
        } elseif ($ledgerType === 'pump_operator') {
            $requiredColumns[] = 'pump_operator';
        }

        $missing = [];
        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('journals', $column)) {
                $missing[] = $column;
            }
        }

        if ($missing !== []) {
            return [
                'Finance could not prepare the journal ledger-link fields automatically. Missing: '
                . implode(', ', $missing)
                . '.',
            ];
        }

        if ($ledgerType === 'pump_operator') {
            try {
                $column = DB::selectOne("SHOW COLUMNS FROM `journals` WHERE `Field` = 'show_in_ledger'");
                $type = strtolower((string) ($column->Type ?? $column->type ?? ''));

                if (str_starts_with($type, 'enum(')
                    && strpos($type, "'pump_operator'") === false
                    && strpos($type, '"pump_operator"') === false) {
                    return [
                        'Finance could not update the journal ledger-link type automatically. '
                        . 'Please contact the system administrator and try again.',
                    ];
                }
            } catch (\Throwable $e) {
                // If SHOW COLUMNS is restricted, allow the normal save to
                // proceed; the current VARCHAR schema does not need inspection.
            }
        }

        return [];
    }

    protected function requiresLedgerDetails(Request $request): bool
    {
        return ! empty($request->show_in_ledger) && $request->show_in_ledger !== 'no';
    }

    /**
     * IS1992 (follow-up): turn the submitted date into Y-m-d, or null.
     *
     * Saving a journal failed with
     *     SQLSTATE[23000] ... Column 'date' cannot be null
     * even though validation passed - which is the giveaway. 'date' => 'required'
     * only proves something was POSTED. transactionUtil->uf_date() parses using
     * the BUSINESS date format and returns null when the string does not match
     * it, so a date picker writing a different format produced a non-empty value
     * that converted to null and went into the insert as null.
     *
     * uf_date() is still tried first: the business format is the correct reading
     * of an ambiguous date like 08/11/2026, and only it knows whether that means
     * 8 November or 11 August. The fallbacks exist so a picker that disagrees
     * with the business setting cannot lose the entry outright.
     */
    /**
     * IS2078: single source of truth for every persisted journal-related date.
     *
     * store()/update() normalise request.date once. Every journals,
     * account_transactions, transactions and contact_ledgers write then uses
     * this exact Y-m-d value; it must never be passed through uf_date() again.
     */
    protected function canonicalJournalDate(Request $request): string
    {
        $date = $this->resolveJournalDate($request->input('date'));

        if ($date === null) {
            throw new \RuntimeException('The journal date could not be resolved.');
        }

        return $date;
    }

    protected function resolveJournalDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string) $value);

        /*
         * IS2078: a browser/native date is unambiguous when it starts with the
         * four-digit year. Parse those forms BEFORE uf_date(). Passing an
         * already-normalised 2026-08-01 through a tenant using d/m/Y or m/d/Y
         * can reinterpret the same calendar date and produce 2026-01-08.
         */
        foreach (['Y-m-d', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed && $parsed->format($format) === $value) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Continue to the tenant date format below.
            }
        }

        try {
            $converted = $this->transactionUtil->uf_date($value);

            if (! empty($converted)) {
                return Carbon::parse($converted)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // Not the business format - fall through to the formats below.
        }

        foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'd.m.Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);

                // Round-trip check. Without it Carbon quietly accepts 31/02/2026
                // and rolls it forward into March.
                if ($parsed && $parsed->format($format) === $value) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function pumpOperators(int $businessId)
    {
        try {
            if (! Schema::hasTable('pump_operators')) {
                return collect();
            }

            return DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->where('active', 1)
                ->orderBy('name')
                ->pluck('name', 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Accept both journal payload formats used by older Finance builds:
     * column arrays (journal[account_id][]) and row arrays
     * (journal[0][account_id]). Fully empty rows are ignored.
     */
    private function normaliseJournalRows(array $journal): array
    {
        $rows = [];

        if (isset($journal['account_id']) || isset($journal['account_type_id'])) {
            $accountTypeIds = array_values((array) ($journal['account_type_id'] ?? []));
            $accountIds = array_values((array) ($journal['account_id'] ?? []));
            $debits = array_values((array) ($journal['debit_amount'] ?? []));
            $credits = array_values((array) ($journal['credit_amount'] ?? []));
            $rowCount = max(count($accountTypeIds), count($accountIds), count($debits), count($credits));

            for ($index = 0; $index < $rowCount; $index++) {
                $row = [
                    'account_type_id' => $accountTypeIds[$index] ?? null,
                    'account_id' => $accountIds[$index] ?? null,
                    'debit_amount' => $debits[$index] ?? null,
                    'credit_amount' => $credits[$index] ?? null,
                ];

                if ($this->journalRowIsCompletelyEmpty($row)) {
                    continue;
                }

                $row['debit_amount'] = $this->normaliseJournalAmount($row['debit_amount']);
                $row['credit_amount'] = $this->normaliseJournalAmount($row['credit_amount']);
                $rows[] = $row;
            }

            return $rows;
        }

        foreach ($journal as $row) {
            if (! is_array($row) || $this->journalRowIsCompletelyEmpty($row)) {
                continue;
            }

            $rows[] = [
                'account_type_id' => $row['account_type_id'] ?? null,
                'account_id' => $row['account_id'] ?? null,
                'debit_amount' => $this->normaliseJournalAmount($row['debit_amount'] ?? null),
                'credit_amount' => $this->normaliseJournalAmount($row['credit_amount'] ?? null),
            ];
        }

        return $rows;
    }

    private function journalRowsToColumns(array $rows): array
    {
        $columns = [
            'account_type_id' => [],
            'account_id' => [],
            'debit_amount' => [],
            'credit_amount' => [],
        ];

        foreach ($rows as $row) {
            $columns['account_type_id'][] = $row['account_type_id'] ?? null;
            $columns['account_id'][] = $row['account_id'] ?? null;
            $columns['debit_amount'][] = (float) ($row['debit_amount'] ?? 0);
            $columns['credit_amount'][] = (float) ($row['credit_amount'] ?? 0);
        }

        return $columns;
    }

    private function journalRowIsCompletelyEmpty(array $row): bool
    {
        foreach (['account_type_id', 'account_id', 'debit_amount', 'credit_amount'] as $field) {
            if (isset($row[$field]) && trim((string) $row[$field]) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normaliseJournalAmount($value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        try {
            if (method_exists($this->transactionUtil, 'num_uf')) {
                return (float) $this->transactionUtil->num_uf($value);
            }
        } catch (\Throwable $e) {
            // Fall through to the locale-neutral normaliser below.
        }

        $normalised = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalised) ? (float) $normalised : 0.0;
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
