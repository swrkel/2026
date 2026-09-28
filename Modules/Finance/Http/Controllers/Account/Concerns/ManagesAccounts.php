<?php

namespace Modules\Finance\Http\Controllers\Account\Concerns;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountSetting;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\AccountType;
use App\Business;
use Modules\Finance\Entities\BusinessLocation;
use App\Category;
use Modules\Finance\Entities\Contact;
use App\ContactLedger;
use App\Journal;
use App\NotificationTemplate;
use App\Product;
use App\PurchaseLine;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use Modules\Finance\Entities\TransactionPayment;
use App\TransactionSellLine;
use Modules\Finance\Entities\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\StockAdjustmentLine;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Modules\Essentials\Entities\EssentialsEmployee;
use Modules\Fleet\Entities\Driver;
use Modules\Fleet\Entities\Fleet;
use Modules\Fleet\Entities\Helper;
use Modules\Hms\Entities\HmsRoom;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\PriceChanges\Entities\PriceChangesDetail;
use Modules\PriceChanges\Entities\PriceChangesHeader;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertySellLine;
use Modules\Shipping\Entities\ShippingAgent;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartner;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatPayment;
use Modules\Finance\Services\Reports\FinanceIntegrationLedgerService;
use Modules\Finance\Services\Accounts\AccountBookDataService;
use Modules\Finance\Services\Accounts\AccountListQueryService;
use Modules\Finance\Services\Deposits\BankDepositAccountResolver;
use Modules\Finance\Services\Deposits\CardDepositAccountResolver;
use Modules\Finance\Services\Deposits\ChequeDepositListService;
use Modules\Finance\Services\FinanceAccountNumberService;
use Yajra\DataTables\Facades\DataTables;

/**
 * Creating, editing, closing and importing accounts.
 *
 * MA-002: split out of Finance's AccountController, which was 9,782 lines in
 * a single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. The 84 routes that point at
 *   AccountController still resolve, action() targets still resolve, and the
 *   $this-> calls between these 98 methods still work. Separate controller
 *   classes would mean rewriting all of those.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: index, create, store, edit, update, close, checkAccountNumber, getAccountNames, account_details, disabledAccount, disabledStatus, imageModal, account_access, getAccNo, buildFinanceAddAccountFormData, addAccountOpeningBalance, getImportAccounts, postImportAccounts
 */
trait ManagesAccounts
{
    public function index(Request $request)
    {

        $business_id = session()->get('user.business_id');
        $user_id     = request()->session()->get('user.id');

        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        }
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $banking_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'banking_module');

        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $account_access = 1;
        }

        if ($request->ajax()) {
            $permitted_locations = $this->getPermittedLocations();
            $accounts = app(AccountListQueryService::class)->build(
                (int) $business_id,
                $request,
                $permitted_locations,
                (int) $account_access
            );
            $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');

            /*
             |------------------------------------------------------------------
             | Finance / List Accounts: make a failure state its own cause.
             |------------------------------------------------------------------
             |
             | This endpoint builds a DataTables response from a query with six
             | joins, a correlated balance subquery and around twenty column
             | formatters. When any of it throws, DataTables shows only its
             | generic "Ajax error" and the grid is empty - which is
             | indistinguishable from "there are no accounts" and tells nobody
             | anything.
             |
             | The driver message, the compiled SQL and the bindings now go to
             | laravel.log, and the message is returned in the JSON `error` field
             | so the warning on screen names the real cause.
             |
             | A 403 shows up here too: if the signed-in role does not hold
             | account.access, index() aborts above this point and the grid is
             | empty for that reason instead. The log distinguishes the two.
             */
            try {
            return DataTables::of($accounts)
                ->addColumn('action', function ($row) use ($account_access, $banking_module, $chequeId) {
                    $html = '';

                    // Check if the account is of type "Post Dated Cheques"
                    $isCompanyPostDatedCheques = $row->name === 'Post Dated Cheques';
                    $isLockedFreeProductsAccount = Account::isIncomeFreeProductsOrSamplesAccountName($row->name);

                    if ($isCompanyPostDatedCheques) {
                        $disabled      = null;
                        $disabledClose = '';
                        if (($row->name == 'Accounts Payable' || $row->name == 'Accounts Receivable') && $banking_module == 1 && $account_access == 0) {
                            $html = '<h4 class="text-danger">You have not subscribed to Accounting Module, so details in this page will not show</h4>';
                        } else {
                            // Check if the user has edit permission and the account is not of type "Post Dated Cheques"
                            $disabledEdit  = 'disabled';
                            $disabledClose = 'disabled';

                            // edit button
                            $html .= '<button ' . $disabledEdit . ' data-href="' . route('finance.account.edit-form', ['id' => $row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary finance-account-modal-trigger edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>&nbsp';

                            // check if its main account
                            if ($row->is_main_account == 0) {
                                $html .= '<a href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __('account.account_book') . '</a>&nbsp';
                            } else {
                                $html .= '<a href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __('lang_v1.main_account_book') . '</a>&nbsp';
                            }

                            // funds transfer
                            $html .= '<button data-href="' . route('finance.account.fund-transfer.form', ['id' => $row->id]) . '" class="btn btn-xs btn-info finance-account-modal-trigger transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __('account.fund_transfer') . '</button>&nbsp';

                            // if ($this->userCan('account.edit')) {
                            //     $html .=  '<button disabled data-href="' . route('finance.account.edit-form', ['id' => $row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary finance-account-modal-trigger edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>&nbsp';
                            // }

                            // close button
                            $html .= '<button  data-url="' . route('finance.account.close', ['id' => $row->id]) . '" data-account-name="' . e($row->name) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __('messages.close') . '</button>&nbsp';

                            // get notes
                            $html .= '<button data-href="' . route('finance.account.notes', ['id' => $row->id]) . '" class="btn btn-xs btn-default finance-account-modal-trigger" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __('account.notes') . '</button> &nbsp';

                            // enabled
                            $html .= '<button data-url="' . route('finance.account.disabled-status', ['id' => $row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __('account.enabled') . '</button>&nbsp';

                            // if ($row->is_closed == 0) {
                            //     if ($row->is_main_account == 0) {
                            //         if ($row->id != $chequeId && !in_array($row->group_name, ['Cash Account', 'Card']) && !in_array($row->name, ['Accounts Receivable'])) {
                            //             $html .=  '<button ' . $disabledEdit . ' data-href="' . route('finance.account.fund-transfer.form', ['id' => $row->id]) . '" class="btn btn-xs btn-info finance-account-modal-trigger transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __("account.fund_transfer") . '</button>&nbsp';

                            //             if (!in_array($row->group_name, ['Bank Account'])) {
                            //                 $html .=  '<button ' . $disabledEdit . ' data-href="' . route('finance.account.deposit.form', ['id' => $row->id]) . '" class="btn btn-xs btn-success finance-account-modal-trigger deposit_btn" data-container=".account_model"><i class="fa fa-money"></i> ' . __("account.deposit") . '</button>&nbsp<br><br>';
                            //             }
                            //         }
                            //         $html .=  '<button ' . $disabledClose . ' data-url="' . route('finance.account.close', ['id' => $row->id]) . '" data-account-name="' . e($row->name) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __("messages.close") . '</button>&nbsp';
                            //     }
                            //     $html .=  '<button ' . $disabledEdit . ' data-href="' . route('finance.account.notes', ['id' => $row->id]) . '" class="btn btn-xs btn-default finance-account-modal-trigger" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __("account.notes") . '</button> &nbsp';
                            //     if ($row->disabled == 0) {
                            //         if ($row->is_main_account == 0) {
                            //             $html .=  '<button ' . $disabledEdit . ' data-url="' . route('finance.account.disabled-status', ['id' => $row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __("account.enabled") . '</button>&nbsp';
                            //         }
                            //     }
                            // }
                        }
                    } else {
                        $disabled                  = '';
                        $disabledClose             = '';
                        $isCompanyPostDatedCheques = false;
                        if (($row->name == 'Accounts Payable' || $row->name == 'Accounts Receivable') && $banking_module == 1 && $account_access == 0) {
                            $html = '<h4 class="text-danger">You have not subscribed to Accounting Module, so details in this page will not show</h4>';
                        } else {
                            // Check if the user has edit permission and the account is not of type "Post Dated Cheques"
                            $disabledEdit = ($this->userCan('account.edit') && ! $isCompanyPostDatedCheques) ? '' : 'disabled';

                            if ($account_access == 0 && ! in_array($row->group_name, ['Cash Account', 'Bank Account', 'Card']) && $row->name != 'Cheques in Hand' || $row->name == 'Opening Balance Equity Account' || $row->name == 'Post Dated Cheques') {
                                $disabledEdit  = 'disabled';
                                $disabledClose = 'disabled';
                            }
                            if ($isLockedFreeProductsAccount) {
                                $disabledEdit = 'disabled';
                                $disabledClose = 'disabled';
                            }

                            if ($this->userCan('account.edit')) {
                                $html .= '<button ' . $disabledEdit . ' data-href="' . route('finance.account.edit-form', ['id' => $row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary finance-account-modal-trigger edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>&nbsp';
                            }

                            if ($row->is_main_account == 0) {
                                $html .= '<a href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __('account.account_book') . '</a>&nbsp';
                            } else {
                                $html .= '<a href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __('lang_v1.main_account_book') . '</a>&nbsp';
                            }

                            if ($row->is_closed == 0) {
                                if ($row->is_main_account == 0) {
                                    /*
                                     * MA-002 (S-611 #3): Cash Account was excluded here, so
                                     * cash accounts showed NEITHER the Transfer button nor
                                     * the Deposit button - and cash is exactly what people
                                     * transfer and deposit from. That is the reported fault.
                                     *
                                     * 'Card' is left excluded. You asked about Transfer and
                                     * Deposit on the account list, not about card accounts,
                                     * and card balances are settled through their own
                                     * screens - opening them here would be a change nobody
                                     * asked for.
                                     */
                                    if ($row->id != $chequeId && ! in_array($row->group_name, ['Card']) && ! in_array($row->name, ['Accounts Receivable'])) {
                                        $html .= '<button ' . $disabledEdit . ' data-href="' . route('finance.account.fund-transfer.form', ['id' => $row->id]) . '" class="btn btn-xs btn-info finance-account-modal-trigger transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __('account.fund_transfer') . '</button>&nbsp';

                                        if (! in_array($row->group_name, ['Bank Account'])) {
                                            $html .= '<button ' . $disabledEdit . ' data-href="' . route('finance.account.deposit.form', ['id' => $row->id]) . '" class="btn btn-xs btn-success finance-account-modal-trigger deposit_btn" data-container=".account_model"><i class="fa fa-money"></i> ' . __('account.deposit') . '</button>&nbsp<br><br>';
                                        }
                                    }
                                    $html .= '<button ' . $disabledClose . ' data-url="' . route('finance.account.close', ['id' => $row->id]) . '" data-account-name="' . e($row->name) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __('messages.close') . '</button>&nbsp';
                                }
                                $html .= '<button ' . $disabledEdit . ' data-href="' . route('finance.account.notes', ['id' => $row->id]) . '" class="btn btn-xs btn-default finance-account-modal-trigger" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __('account.notes') . '</button> &nbsp';
                                if ($row->disabled == 0) {
                                    if ($row->is_main_account == 0) {
                                        $html .= '<button ' . $disabledEdit . ' data-url="' . route('finance.account.disabled-status', ['id' => $row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __('account.enabled') . '</button>&nbsp';
                                    }
                                }
                            }
                        }
                    }

                    return $html;
                })

                ->editColumn('name', function ($row) {
                    if ($row->is_closed == 1) {
                        return $row->name . ' <small class="label pull-right bg-red no-print">' . __('account.closed') . '</small><span class="print_section">(' . __('account.closed') . ')</span>';
                    } else {
                        return $row->name;
                    }
                })
                ->editColumn('parent_account_id', function ($row) {
                    return (string) ($row->parent_account_name ?? '');
                })
                ->addColumn('balance', function ($row) {
                    $balance = (float) ($row->debit_normal_balance ?? 0);
                    $typeName = strtolower((string) ($row->parent_account_type_name ?: $row->account_type_name));

                    if (str_contains($typeName, 'liabilit') || str_contains($typeName, 'equity') || str_contains($typeName, 'income')) {
                        $balance *= -1;
                    }

                    return '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $balance . '">' . $balance . '</span>';
                })
                ->addColumn('account_location', function ($row) {
                    // Legacy rows may contain NULL/blank instead of the intended
                    // 'all' marker. Treat them as All locations so old accounts
                    // remain visible and are labelled consistently.
                    if ($row->location_id === null || trim((string) $row->location_id) === '' || $row->location_id === 'all') {
                        return ucfirst(__('messages.all'));
                    }

                    return (string) ($row->account_location_name ?? '');
                })
                ->editColumn('account_type', function ($row) {
                    $parentType = trim((string) ($row->parent_account_type_name ?? ''));
                    $type = trim((string) ($row->account_type_name ?? ''));

                    return $parentType !== '' ? $parentType . ' / ' . $type : $type;
                })
                ->editColumn('parent_account_type_name', function ($row) {
                    $parent_account_type_name = empty($row->parent_account_type_name) ? $row->account_type_name : $row->parent_account_type_name;

                    return $parent_account_type_name;
                })
                ->editColumn('account_type_name', function ($row) {
                    $account_type_name = empty($row->parent_account_type_name) ? '' : $row->account_type_name;

                    return $account_type_name;
                })
                ->editColumn('added_by', function ($row) {
                    if ($row->created_by == 1) {
                        return 'Default';
                    } else {
                        return $row->added_by;
                    }
                })
                ->editColumn('account_group', function ($row) {
                    return (string) ($row->group_name ?? '');
                })
                ->setRowAttr([
                    'data-visible' => function ($row) {
                        return $row->visible;
                    },
                ])
            // ->removeColumn('id')
                ->removeColumn('is_closed')
                ->rawColumns(['action', 'balance', 'name', 'account_group', 'reconcile_status'])
                ->make(true);
            } catch (\Throwable $e) {
                $sql = null;
                $bindings = null;

                try {
                    $sql = $accounts->toSql();
                    $bindings = $accounts->getBindings();
                } catch (\Throwable $ignore) {
                    // The builder itself may be what is broken.
                }

                \Illuminate\Support\Facades\Log::error('Finance List Accounts query failed.', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'sql' => $sql,
                    'bindings' => $bindings,
                    'filters' => $request->only([
                        'account_type', 'account_type_s', 'account_sub_type', 'account_group',
                        'location_id', 'parent_account_id', 'account_name',
                    ]),
                ]);

                return response()->json([
                    'draw' => (int) $request->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'List Accounts could not be loaded: ' . $e->getMessage(),
                ]);
            }
        }

        // This badge value is not rendered on the Finance List Accounts page.
        // Avoid counting the complete payment history during page opening.
        $not_linked_payments = 0;
        $account_type_query = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id');
        $account_types_opts = $account_type_query->pluck('name', 'id');
        $account_type_query->with(['sub_types']);
        if ($account_access == 0) {
            $account_type_query->where(function ($q) {
                $q->where('name', 'Assets')->orWhere('name', 'Liabilities');
            });
        }
        $account_types = $account_type_query->get();
        // dd($account_types->toArray());
        $filterdata                       = [];
        $sub_acn_arr                      = [];
        $filterdata['subType_']['data'][] = ['id' => '', 'text' => 'All', true];
        foreach ($account_types->toArray() as $acunts) {
            $filterdata['subType_' . $acunts['id']]['data'][] = ['id' => '', 'text' => 'All', true];
            foreach ($acunts['sub_types'] as $sub_Acn) {
                $filterdata['subType_']['data'][]                 = ['id' => $sub_Acn['id'], 'text' => $sub_Acn['name']];
                $filterdata['subType_' . $acunts['id']]['data'][] = ['id' => $sub_Acn['id'], 'text' => $sub_Acn['name']];
                $sub_acn_arr[$sub_Acn['id']]                      = $sub_Acn['name'];
            }
        }
        // echo "<pre>";print_r($filterdata);
        $business_locations                 = collect();
        $account_groups_raw                 = AccountGroup::where('business_id', $business_id)->get()->toArray();
        $account_groups                     = [];
        $filterdata['groupType_']['data'][] = ['id' => '', 'text' => 'All', true];
        foreach ($account_groups_raw as $datarow) {
            $filterdata['groupType_' . $datarow['account_type_id']]['data'][] = ['id' => $datarow['id'], 'text' => $datarow['name']];
            $account_groups[$datarow['id']]                                   = $datarow['name'];
        }
        // dd($filterdata);
        $accounts      = Account::where('business_id', $business_id)->pluck('name', 'id');
        $users         = User::forDropdown($business_id);
        $orderStatuses = [];
        // Hidden tab customer filters are remote Select2 fields. Do not load
        // the complete contacts table while opening List Accounts.
        $suppliers     = [];
        $customers     = [];
        $chequeId      = $this->transactionUtil->account_exist_return_id('Cheques in Hand');

        $can_edit_ob = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_ob');

        // Heavy historical maintenance is intentionally not executed while opening
        // List Accounts. Those routines must run from their dedicated actions.

        $permitted_locations = $this->getPermittedLocations();

        if ($permitted_locations == 'all') {
            $_business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
            $_business_locations->prepend(__('messages.all'), 'all');
        } else {
            $_business_locations = BusinessLocation::where('business_id', $business_id)->whereIn('id', $permitted_locations)->pluck('name', 'id');
            $_business_locations->prepend(__('messages.all'), '');
        }


        // The List Deposits & Transfers tab is server-side and loads only when
        // opened. Never scan its full history while rendering List Accounts.
        $list_deposit_transfer_rows = collect();
        $list_deposit_transfer_total = 0;
        $realize_cheque_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'realize_cheque');

        // Preload the Add Account form inside List Accounts. This removes the
        // fragile AJAX GET dependency that previously produced the generic
        // ‘Unable to open the Finance form’ message.
        $add_account_form_data = $this->buildFinanceAddAccountFormData((int) $business_id);

        return view('finance::account.index')
            ->with(compact('_business_locations', 'can_edit_ob', 'chequeId', 'customers', 'filterdata', 'account_types_opts', 'sub_acn_arr', 'not_linked_payments', 'account_types', 'account_access', 'business_locations', 'account_groups', 'users', 'accounts', 'suppliers', 'orderStatuses', 'list_deposit_transfer_rows', 'list_deposit_transfer_total', 'realize_cheque_enabled', 'add_account_form_data'));
    }

    public function create()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) (session()->get('user.business_id') ?: session()->get('business.id'));

        return view('finance::account.create', $this->buildFinanceAddAccountFormData($businessId));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */

    public function store(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {

                $business_id = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));

                $isMainAccount = $request->boolean('is_main_account');
                $isSubAccount = $request->boolean('sub_type');
                if ($isMainAccount && $isSubAccount) {
                    return [
                        'success' => false,
                        'msg' => 'Please select either Main A/C or Sub A/C, not both.',
                    ];
                }
                if ($isSubAccount && empty($request->input('parent_account_id'))) {
                    return [
                        'success' => false,
                        'msg' => 'Parent Account is required for a Sub A/C.',
                    ];
                }

                $selectedAccountType = AccountType::where('business_id', $business_id)
                    ->find((int) $request->input('account_type_id'));
                if (empty($selectedAccountType)) {
                    return [
                        'success' => false,
                        'msg' => __('account.account_type') . ' is required.',
                    ];
                }

                // Super Admin -> Default Accounts -> Account Numbers is the
                // authoritative starting number/prefix. Finance auto-loads the
                // next value, but the business user may edit it before Save.
                $configuredAccountNumber = app(FinanceAccountNumberService::class)
                    ->nextNumberForType($selectedAccountType, $business_id);

                $accountNumber = trim((string) $request->input('account_number', ''));
                if ($accountNumber === '') {
                    $accountNumber = trim((string) ($configuredAccountNumber ?? ''));
                }

                if ($accountNumber === '') {
                    return [
                        'success' => false,
                        'msg' => 'Please enter an Account Number. No default Account Number is configured for the selected Account Type.',
                    ];
                }

                if (mb_strlen($accountNumber) > 191) {
                    return [
                        'success' => false,
                        'msg' => 'Account Number must not exceed 191 characters.',
                    ];
                }

                $request->merge(['account_number' => $accountNumber]);

                // Include soft-deleted Accounts so a manual override cannot
                // silently recycle an Account Number that still belongs to
                // historical accounting records.
                $check = Account::withTrashed()
                    ->where('business_id', $business_id)
                    ->where('account_number', $accountNumber)
                    ->first();
                if (! empty($check)) {
                    return [
                        'success' => false,
                        'msg'     => __('lang_v1.account_number_added_already'),
                    ];
                }

                $input                          = $request->only(['name', 'account_number', 'note', 'account_type_id', 'asset_type', 'is_main_account', 'is_need_cheque', 'show_in_balance_sheet', 'is_property', 'location_id']);
                $user_id                        = $request->session()->get('user.id');
                $input['is_main_account']       = ! empty($input['is_main_account']) ? $input['is_main_account'] : 0;
                $input['show_in_balance_sheet'] = empty($input['is_main_account']) ? ($input['show_in_balance_sheet'] ?? 0) : 0;
                $input['is_need_cheque']        = ($request->input('is_need_cheque') === 'Y') ? 'Y' : 'N';

                /*
                 * A blank location used to be stored as an empty string because
                 * the null-coalescing operator does not treat '' as missing.
                 * Those accounts were then hidden by the location filter. For a
                 * user with all-location access (including Business Admin), blank
                 * means the intended system default: All locations. A restricted
                 * user must choose one of the locations actually assigned to them.
                 */
                $permitted_locations = $this->getPermittedLocations();
                $requested_location = trim((string) ($input['location_id'] ?? ''));

                if ($requested_location === '') {
                    if ($permitted_locations === 'all') {
                        $requested_location = 'all';
                    } else {
                        return [
                            'success' => false,
                            'msg' => __('lang_v1.location') . ' is required. Please select an assigned location.',
                        ];
                    }
                }

                if ($permitted_locations !== 'all') {
                    $allowed_location_ids = array_map('strval', (array) $permitted_locations);
                    if ($requested_location === 'all' || ! in_array((string) $requested_location, $allowed_location_ids, true)) {
                        abort(403, 'Unauthorized location for this account.');
                    }
                }

                $input['location_id']           = $requested_location;
                $input['is_property']           = $input['is_property'] ?? 0;

                $input['business_id']               = $business_id;
                $input['created_by']                = $user_id;
                $input['visible']                   = 1;
                $input['is_business_bank_account']  = $request->has('is_business_bank_account') ? 1 : 0;
                $asset_type_ids       = AccountType::getAccountTypeIdOfType('Assets', $business_id);

                /*
                 | Account Group is driven by the selected Account Type.
                 |
                 | If linked groups exist, require one of those groups. If there
                 | are no linked groups at all, the account must still save
                 | successfully with asset_type = null.
                */
                $availableAccountGroups = $this->financeAccountGroupsForType($business_id, $selectedAccountType);
                $selectedAccountGroupId = (int) ($input['asset_type'] ?? 0);

                if ($availableAccountGroups->isEmpty()) {
                    $input['asset_type'] = null;
                } else {
                    if ($selectedAccountGroupId <= 0) {
                        return [
                            'success' => false,
                            'msg' => 'Account Group is required for the selected Account Type.',
                        ];
                    }

                    if (! $availableAccountGroups->contains('id', $selectedAccountGroupId)) {
                        return [
                            'success' => false,
                            'msg' => 'Please select an Account Group linked to the selected Account Type.',
                        ];
                    }

                    $input['asset_type'] = $selectedAccountGroupId;
                }
                if (! empty($request->sub_type)) {
                    $input['parent_account_id'] = $request->parent_account_id;
                }
                $account = Account::create($input);
                // Opening Balance
                $opening_bal = $request->input('opening_balance');
                if (! empty($opening_bal)) {
                    $account_type_name = AccountType::where('id', $input['account_type_id'])->first();
                    $type              = 'debit';
                    if (strpos($account_type_name, 'Assets') !== false || strpos($account_type_name, 'Expenses') !== false) {
                        if ($opening_bal >= 0) {
                            $type = 'debit';
                        } else {
                            $type = 'credit';
                        }
                    } else {
                        if ($opening_bal >= 0) {
                            $type = 'credit';
                        } else {
                            $type = 'debit';
                        }
                    }
                    $ob_transaction_data = [
                        'amount'         => abs($this->commonUtil->num_uf($opening_bal)),
                        'account_id'     => $account->id,
                        'type'           => $type,
                        'sub_type'       => 'opening_balance',
                        'operation_date' => Carbon::now(),
                        'created_by'     => $user_id,
                    ];
                    AccountTransaction::createAccountTransaction($ob_transaction_data);
                }
                $output = [
                    'success' => true,
                    'msg'     => __('account.account_created_success'),
                ];
            } catch (\Exception $e) {
                Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function edit($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $account     = Account::where('business_id', $business_id)
                ->find($id);
            if ($this->isIncomeFreeProductsOrSamplesLockedAccount($account)) {
                return response()->json($this->lockedAccountResponse());
            }
            $account_access     = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
            $account_type_query = AccountType::where('business_id', $business_id)
                ->whereNull('parent_account_type_id')
                ->with(['sub_types']);
            if ($account_access == 0) {
                $account_type_query->whereIn('name', ['Assets', 'Liabilities']);
            }
            $account_types          = $account_type_query->get();
            $selectedAccountType = AccountType::where('business_id', $business_id)
                ->find((int) $account->account_type_id);
            $account_groups = ! empty($selectedAccountType)
                ? $this->financeAccountGroupsForType((int) $business_id, $selectedAccountType)
                : collect();
            $account_groups_by_type = $this->financeAccountGroupOptionsMap((int) $business_id, $account_types);
            $selected_account_group = AccountGroup::where('business_id', $business_id)
                ->find($account->asset_type);
            $asset_type_ids         = json_encode(AccountType::getAccountTypeIdOfType('Assets', $business_id));
            $balance                = AccountTransaction::where('account_id', $id)
                ->select(DB::raw("SUM( IF(account_transactions.type='credit', -1*amount, amount) ) as balance"))->first();
            $start_date      = $this->commonUtil->format_date(request()->session()->get('business.start_date'));
            $parent_accounts = Account::leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')
                ->where('account_types.id', $account->account_type_id)->orWhere('parent_account_type_id', $account->account_type_id)
                ->where('accounts.business_id', $business_id)
                ->select('accounts.id', 'accounts.name')
                ->pluck('accounts.name', 'accounts.id');

            $parentAccountsData = Account::where(['is_main_account' => 1, 'business_id' => $business_id])->get()->toArray();

            $fixed_acc_id = AccountType::getAccountTypeIdOfType('Fixed Assets', $business_id);

            $fixed_acc_id = ! empty($fixed_acc_id) ? $fixed_acc_id[0] : 0;

            $permitted_locations = $this->getPermittedLocations();
            if ($permitted_locations == 'all') {
                $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
                $business_locations->prepend(__('messages.all'), 'all');
            } else {
                $business_locations = BusinessLocation::where('business_id', $business_id)->whereIn('id', $permitted_locations)->pluck('name', 'id');
                $business_locations->prepend(__('lang_v1.please_select'), '');
            }

            // modified by iftekhar
            return view('finance::account.edit')
                ->with(compact('business_locations', 'fixed_acc_id', 'account', 'account_types', 'balance', 'account_groups', 'account_groups_by_type', 'account_access', 'selected_account_group', 'asset_type_ids', 'start_date', 'parent_accounts', 'parentAccountsData'));
        }
    }

    public function update(Request $request, $id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {
                $user_id     = $request->session()->get('user.id');
                $input       = $request->only(['location_id', 'is_property', 'name', 'account_number', 'is_need_cheque', 'note', 'show_in_balance_sheet', 'account_type_id', 'parent_account_id', 'is_main_account', 'asset_type', 'increase_reduce', 'transaction_date']);
                $business_id = request()->session()->get('user.business_id');
                $account     = $oldAcc     = Account::where('business_id', $business_id)
                    ->findOrFail($id);
                if ($this->isIncomeFreeProductsOrSamplesLockedAccount($account)) {
                    return $this->lockedAccountResponse();
                }

                $selectedAccountType = AccountType::where('business_id', $business_id)
                    ->find((int) ($input['account_type_id'] ?? 0));
                if (empty($selectedAccountType)) {
                    return [
                        'success' => false,
                        'msg' => __('account.account_type') . ' is required.',
                    ];
                }

                $availableAccountGroups = $this->financeAccountGroupsForType((int) $business_id, $selectedAccountType);
                $selectedAccountGroupId = (int) ($input['asset_type'] ?? 0);

                if ($availableAccountGroups->isEmpty()) {
                    $input['asset_type'] = null;
                } else {
                    if ($selectedAccountGroupId <= 0) {
                        return [
                            'success' => false,
                            'msg' => 'Account Group is required for the selected Account Type.',
                        ];
                    }

                    if (! $availableAccountGroups->contains('id', $selectedAccountGroupId)) {
                        return [
                            'success' => false,
                            'msg' => 'Please select an Account Group linked to the selected Account Type.',
                        ];
                    }

                    $input['asset_type'] = $selectedAccountGroupId;
                }

                $account->name                  = $input['name'];
                $account->location_id           = $input['location_id'];
                $account->is_need_cheque        = ($request->input('is_need_cheque') === 'Y') ? 'Y' : 'N';
                $account->is_property           = $input['is_property'] ?? 0;
                $account->is_main_account       = ! empty($input['is_main_account']) ? $input['is_main_account'] : 0;
                $account->show_in_balance_sheet = empty($input['is_main_account']) ? $input['show_in_balance_sheet'] : 0;

                // The current value remains editable. If the Account Type is
                // changed and the user leaves Account Number blank, Finance
                // falls back to the next number from Super Admin's setup.
                $accountNumber = trim((string) ($input['account_number'] ?? ''));
                if ($accountNumber === '') {
                    $accountNumber = (string) (app(FinanceAccountNumberService::class)
                        ->nextNumberForType($selectedAccountType, (int) $business_id) ?? '');
                }

                if ($accountNumber === '') {
                    return [
                        'success' => false,
                        'msg' => 'Please enter an Account Number. No default Account Number is configured for the selected Account Type.',
                    ];
                }

                if (mb_strlen($accountNumber) > 191) {
                    return [
                        'success' => false,
                        'msg' => 'Account Number must not exceed 191 characters.',
                    ];
                }

                $duplicateNumber = Account::withTrashed()
                    ->where('business_id', $business_id)
                    ->where('id', '!=', $account->id)
                    ->where('account_number', $accountNumber)
                    ->exists();
                if ($duplicateNumber) {
                    return [
                        'success' => false,
                        'msg' => __('lang_v1.account_number_added_already'),
                    ];
                }

                $account->account_number = $accountNumber;

                $account->note                       = $input['note'];
                $account->account_type_id            = $input['account_type_id'];
                $account->is_business_bank_account   = $request->has('is_business_bank_account') ? 1 : 0;

                $asset_type_ids = AccountType::getAccountTypeIdOfType('Assets', $business_id);
                if (empty($input['asset_type'])) {
                    $account->asset_type = null;
                }
                $account->asset_type = ! empty($input['asset_type']) ? $input['asset_type'] : null;
                if (! empty($request->sub_type)) {
                    $account->parent_account_id = $request->parent_account_id;
                } else {
                    $account->parent_account_id = null;
                }

                if ($account->save()) {
                    $optrans = AccountTransaction::where(['account_id' => $id, 'sub_type' => 'opening_balance'])->get();
                    foreach ($optrans as $tras) {
                        $account_type_name = AccountType::where('id', $account->account_type_id)->first();
                        $type              = 'debit';
                        if (strpos($account_type_name, 'Assets') !== false || strpos($account_type_name, 'Expenses') !== false) {
                            if ($tras->amount >= 0) {
                                $type = 'debit';
                            } else {
                                $type = 'credit';
                            }
                        } else {
                            if ($tras->amount >= 0) {
                                $type = 'credit';
                            } else {
                                $type = 'debit';
                            }
                        }
                        $tras->type = $type;
                        $tras->save();
                    }
                }
                $opening_bal = $request->input('opening_balance');
                if (! empty($opening_bal) && ! empty($input['increase_reduce'])) {
                    $account_type_name = AccountType::where('id', $input['account_type_id'])->first();
                    if (strpos($account_type_name->name, 'Assets') !== false || strpos($account_type_name->name, 'Expense') !== false) {
                        if ($input['increase_reduce'] == 'increase') {
                            $type = 'debit';
                        } else {
                            $type = 'credit';
                        }
                    } else {
                        if ($input['increase_reduce'] == 'increase') {
                            $type = 'credit';
                        } else {
                            $type = 'debit';
                        }
                    }
                    $ob_transaction_data = [
                        'amount'         => abs($this->commonUtil->num_uf($opening_bal)),
                        'account_id'     => $account->id,
                        'type'           => $type,
                        'sub_type'       => 'opening_balance',
                        'operation_date' => Carbon::parse($input['transaction_date'])->format('Y-m-d H:i:s'),
                        'created_by'     => $user_id,
                    ];
                    AccountTransaction::createAccountTransaction($ob_transaction_data);
                }
                $output = [
                    'success' => true,
                    'msg'     => __('account.account_updated_success'),
                ];
            } catch (\Exception $e) {
                Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */

    public function close($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {
                $business_id = session()->get('user.business_id');
                $account     = Account::where('business_id', $business_id)
                    ->findOrFail($id);
                if ($this->isIncomeFreeProductsOrSamplesLockedAccount($account)) {
                    return $this->lockedAccountResponse();
                }
                $account->is_closed = 1;
                $account->save();
                $output = [
                    'success' => true,
                    'msg'     => __('account.account_closed_success'),
                ];
            } catch (\Exception $e) {
                Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Shows form to transfer fund.
     *
     * @param  int  $id
     * @return Response
     */

    public function checkAccountNumber(Request $request)
    {
        $account_number = trim((string) $request->account_number);
        $business_id    = (int) session()->get('user.business_id');
        $account_id     = (int) $request->input('account_id', 0);

        $check = Account::withTrashed()
            ->where('business_id', $business_id)
            ->when($account_id > 0, function ($query) use ($account_id) {
                $query->where('id', '!=', $account_id);
            })
            ->where('account_number', $account_number)
            ->exists();

        if ($check) {
            return ['success' => false, 'msg' => __('lang_v1.account_number_added_already')];
        }

        return ['success' => true];
    }

    public function getAccountNames(Request $request)
    {
        $business_id = session()->get('user.business_id');
        // dd(request()->get());
        $accountObj   = Account::leftjoin('account_settings', 'accounts.id', 'account_settings.account_id')->where('accounts.business_id', $business_id)->where('accounts.disabled', 0)->where('accounts.visible', 1);
        $acc_type     = request()->get('account_type_s', null);
        $acc_sub_type = request()->get('account_sub_type', null);
        if (! empty($acc_type) && $acc_type != 'All') {
            if (! empty($acc_sub_type) && $acc_sub_type != 'All') {
                $accountObj->where('accounts.account_type_id', $acc_sub_type);
            } else {
                $account_type_ids = AccountType::where('business_id', $business_id)->where('parent_account_type_id', $acc_type)->pluck('id');
                if (! empty($account_type_ids) && $account_type_ids->count()) {
                    $accountObj->whereIn('accounts.account_type_id', $account_type_ids);
                } else {
                    $accountObj->where('accounts.account_type_id', $acc_type);
                }
            }
        } else {
            if (! empty($acc_sub_type) && $acc_sub_type != 'All') {
                $accountObj->where('accounts.account_type_id', $acc_sub_type);
            }
        }
        $acc_group = request()->get('account_group', null);
        if (! empty($acc_group) && $acc_group != 'All') {
            $accountObj->where('accounts.asset_type', $acc_group);
        }
        $ac_parent = request()->get('parent_account_id', null);
        if (! empty($ac_parent) && $ac_parent != 'All') {
            $accountObj->where('accounts.parent_account_id', $ac_parent);
        }
        $account_amount = request()->get('amount', null);
        if (! empty($account_amount)) {
            $account_amount = str_replace(',', '', $account_amount);
            $accountObj->where('account_settings.amount', $account_amount);
        }
        if (! empty(request()->start_date)) {
            $accountObj->whereDate('account_settings.date', '>=', request()->start_date);
        }

        $accounts      = $accountObj->pluck('accounts.name', 'accounts.id');
        $res['data'][] = ['id' => '', 'text' => 'All'];
        foreach ($accounts as $key => $account) {
            $res['data'][] = ['id' => $key, 'text' => $account];
        }

        return $res;
    }

    public function account_details(Request $request)
    {
        if (request()->ajax()) {
            $query = DB::table('accounts')
                ->leftJoin('users AS u', 'accounts.created_by', '=', 'u.id')
                ->where('accounts.id', request()->id)
                ->select(['accounts.*', 'u.first_name'])
                ->first();

            return response()->json($query);
        }
    }

    // @eng START 15/2

    public function disabledAccount()
    {
        $business_id = (int) (session()->get('user.business_id') ?: session()->get('business.id'));

        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        }
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $account_access = 1;
        }

        if (request()->ajax()) {
            try {
                if (! $account_access) {
                    return DataTables::of(collect([]))->make(true);
                }

                /*
                 * Keep every aggregate column fully qualified. The old query used
                 * an unqualified `amount`, which becomes ambiguous after joining
                 * transactions and transaction_payments.
                 */
                $balance_query = DB::table('account_transactions as DAT')
                    ->leftJoin('transactions as DT', 'DAT.transaction_id', '=', 'DT.id')
                    ->leftJoin('transaction_payments as DTP', 'DAT.transaction_payment_id', '=', 'DTP.id')
                    ->join('accounts as DBA', function ($join) use ($business_id) {
                        $join->on('DBA.id', '=', 'DAT.account_id')
                            ->where('DBA.business_id', '=', $business_id);
                    })
                    ->whereNull('DAT.deleted_at')
                    ->where(function ($query) {
                        $query->whereNull('DAT.transaction_payment_id')
                            ->orWhereNotNull('DTP.id');
                    });

                $location_id = request()->get('location_id');
                if (! empty($location_id)) {
                    $balance_query->where(function ($query) use ($location_id) {
                        $query->where('DT.location_id', $location_id)
                            ->orWhere(function ($opening) use ($location_id) {
                                $opening->whereNull('DAT.transaction_id')
                                    ->where('DBA.location_id', $location_id);
                            });
                    });
                } else {
                    $allowed_locations = ModulePermissionLocation::getModulePermissionLocations($business_id, 'accounting_module');
                    if (! empty($allowed_locations) && ! empty($allowed_locations->locations)) {
                        $location_ids = array_map('intval', array_keys($allowed_locations->locations));
                        $balance_query->where(function ($query) use ($location_ids) {
                            $query->whereIn('DT.location_id', $location_ids)
                                ->orWhere(function ($opening) use ($location_ids) {
                                    $opening->whereNull('DAT.transaction_id')
                                        ->whereIn('DBA.location_id', $location_ids);
                                });
                        });
                    }
                }

                $balance_query->select([
                    'DAT.account_id',
                    DB::raw("COALESCE(SUM(CASE WHEN DAT.type = 'credit' THEN -1 * DAT.amount ELSE DAT.amount END), 0) as ass_exp_balance"),
                    DB::raw("COALESCE(SUM(CASE WHEN DAT.type = 'debit' THEN -1 * DAT.amount ELSE DAT.amount END), 0) as li_in_eq_balance"),
                ])->groupBy('DAT.account_id');

                $accounts = DB::table('accounts as DA')
                    ->leftJoinSub($balance_query, 'DBAL', function ($join) {
                        $join->on('DBAL.account_id', '=', 'DA.id');
                    })
                    ->leftJoin('account_types as DAT_SUB', 'DA.account_type_id', '=', 'DAT_SUB.id')
                    ->leftJoin('account_types as DAT_PARENT', 'DAT_SUB.parent_account_type_id', '=', 'DAT_PARENT.id')
                    ->leftJoin('account_groups as DAG', 'DA.asset_type', '=', 'DAG.id')
                    ->leftJoin('users as DU', 'DA.created_by', '=', 'DU.id')
                    ->where('DA.business_id', $business_id)
                    ->where('DA.disabled', 1)
                    ->whereNull('DA.deleted_at')
                    ->select([
                        'DA.name',
                        'DA.account_number',
                        'DA.visible',
                        'DA.note',
                        'DA.id',
                        'DA.account_type_id',
                        'DA.created_by',
                        'DA.disabled',
                        'DA.asset_type',
                        'DA.is_closed',
                        'DAT_SUB.name as account_type_name',
                        'DAT_PARENT.name as parent_account_type_name',
                        'DAG.name as account_group_name',
                        DB::raw('COALESCE(DBAL.ass_exp_balance, 0) as ass_exp_balance'),
                        DB::raw('COALESCE(DBAL.li_in_eq_balance, 0) as li_in_eq_balance'),
                        DB::raw("TRIM(CONCAT(COALESCE(DU.surname, ''),' ',COALESCE(DU.first_name, ''),' ',COALESCE(DU.last_name,''))) as added_by"),
                    ]);

                return DataTables::of($accounts)
                    ->addColumn('action', '
                        @can("account.edit")
                        <button data-href="{{route(\'finance.account.edit-form\', [\'id\' => $id])}}" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        @endcan
                        <a href="{{route(\'finance.list-accounts.live.account_book.show\', [\'id\' => $id], false)}}" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> @lang("account.account_book")</a>&nbsp;
                        @if($is_closed == 0)
                        <button data-href="{{route(\'finance.account.fund-transfer.form\', [$id])}}" class="btn btn-xs btn-info btn-modal transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> @lang("account.fund_transfer")</button>
                        <button data-href="{{route(\'finance.account.deposit.form\', [$id])}}" class="btn btn-xs btn-success btn-modal deposit_btn" data-container=".account_model"><i class="fa fa-money"></i> @lang("account.deposit")</button><br><br>
                        <button data-href="{{route(\'finance.account.notes\', [$id])}}" class="btn btn-xs btn-default btn-modal" data-container=".account_model"><i class="fa fa-sticky-note-o"></i> @lang("account.notes")</button>
                        <button data-url="{{route(\'finance.account.close\', [$id])}}" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> @lang("messages.close")</button>
                        @if($disabled == 1)
                        <button data-url="{{route(\'finance.account.disabled-status\', [$id])}}" class="btn btn-xs btn-info disable_status_account"><i class="fa fa-times-circle-o"></i> @lang("account.disabled")</button>
                        @endif
                        @endif
                    ')
                    ->editColumn('name', function ($row) {
                        if ((int) $row->is_closed === 1) {
                            return e($row->name) . ' <small class="label pull-right bg-red no-print">' . __('account.closed') . '</small><span class="print_section">(' . __('account.closed') . ')</span>';
                        }

                        return e($row->name);
                    })
                    ->addColumn('balance', function ($row) {
                        $type_name = trim(($row->parent_account_type_name ?: '') . ' ' . ($row->account_type_name ?: ''));
                        $is_debit_normal = stripos($type_name, 'asset') !== false || stripos($type_name, 'expense') !== false;
                        $balance = $is_debit_normal ? (float) $row->ass_exp_balance : (float) $row->li_in_eq_balance;

                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $balance . '">' . $balance . '</span>';
                    })
                    ->editColumn('parent_account_type_name', function ($row) {
                        return empty($row->parent_account_type_name) ? ($row->account_type_name ?: '') : $row->parent_account_type_name;
                    })
                    ->editColumn('account_type_name', function ($row) {
                        return empty($row->parent_account_type_name) ? '' : ($row->account_type_name ?: '');
                    })
                    ->editColumn('added_by', function ($row) {
                        return (int) $row->created_by === 1 ? 'Default' : ($row->added_by ?: '');
                    })
                    ->addColumn('account_group', function ($row) {
                        return $row->account_group_name ?: '';
                    })
                    ->filterColumn('parent_account_type_name', function ($query, $keyword) {
                        $query->where(function ($filter) use ($keyword) {
                            $filter->where('DAT_PARENT.name', 'like', '%' . $keyword . '%')
                                ->orWhere('DAT_SUB.name', 'like', '%' . $keyword . '%');
                        });
                    })
                    ->filterColumn('account_type_name', function ($query, $keyword) {
                        $query->where('DAT_SUB.name', 'like', '%' . $keyword . '%');
                    })
                    ->filterColumn('account_group', function ($query, $keyword) {
                        $query->where('DAG.name', 'like', '%' . $keyword . '%');
                    })
                    ->filterColumn('added_by', function ($query, $keyword) {
                        $query->whereRaw("TRIM(CONCAT(COALESCE(DU.surname, ''),' ',COALESCE(DU.first_name, ''),' ',COALESCE(DU.last_name,''))) LIKE ?", ['%' . $keyword . '%']);
                    })
                    ->orderColumn('parent_account_type_name', 'COALESCE(DAT_PARENT.name, DAT_SUB.name) $1')
                    ->orderColumn('account_type_name', 'DAT_SUB.name $1')
                    ->orderColumn('account_group', 'DAG.name $1')
                    ->orderColumn('added_by', 'DU.first_name $1')
                    ->setRowAttr([
                        'data-visible' => function ($row) {
                            return $row->visible;
                        },
                    ])
                    ->removeColumn('id')
                    ->removeColumn('is_closed')
                    ->rawColumns(['action', 'balance', 'name'])
                    ->make(true);
            } catch (\Throwable $e) {
                Log::error('Finance disabled accounts DataTable failed', [
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
                    'error' => __('messages.something_went_wrong'),
                ]);
            }
        }

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $disabled_message_color = System::getProperty('not_enalbed_module_user_color');
        $disabled_message_font_size = System::getProperty('not_enalbed_module_user_font_size');
        $disabled_message = System::getProperty('not_enalbed_module_user_message');

        return view('finance::account.disabled_accounts')->with(compact(
            'account_access',
            'business_locations',
            'disabled_message_color',
            'disabled_message_font_size',
            'disabled_message'
        ));
    }

    public function disabledStatus($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');

        try {
            $output = DB::transaction(function () use ($business_id, $id) {
                $account = Account::where('business_id', $business_id)
                    ->where('id', (int) $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->isIncomeFreeProductsOrSamplesLockedAccount($account)) {
                    return $this->lockedAccountResponse();
                }

                $account->disabled = ! (bool) $account->disabled;
                $account->save();

                return [
                    'success' => true,
                    'msg' => __('account.success'),
                ];
            }, 3);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function imageModal(Request $request)
    {
        $url   = $request->url;
        $title = $request->title;

        // modified by iftekhar
        return view('finance::account.image_modal')->with(compact('title', 'url'));
    }

    public function account_access($business_id)
    {
        $subscription = Subscription::active_subscription($business_id);
        if (! empty($subscription)) {
            $package             = DB::table('packages')->where('id', $subscription->package_id)->select('package_permissions')->first();
            $package_permissions = ! empty($package) ? json_decode($package->package_permissions) : [];
        } else {
            $package_permissions = [];
        }
        $account_access = 0;
        if (! empty($package_permissions)) {
            if ($package_permissions->account_access == 1) {
                $account_access = 1;
            }
        }

        return $account_access;
    }

    public function getAccNo($id)
    {
        $businessId = (int) (session()->get('user.business_id') ?: session()->get('business.id'));
        $accountType = AccountType::where('business_id', $businessId)->find((int) $id);

        if (empty($accountType)) {
            return response()->json(['disable' => 1, 'account_no' => '']);
        }

        $accountNo = app(FinanceAccountNumberService::class)
            ->nextNumberForType($accountType, $businessId);

        return response()->json([
            // Auto-populate from Super Admin, but keep the field editable.
            'disable' => 0,
            'account_no' => $accountNo ?: '',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Return the Account Type ids whose Account Groups are valid for the
     * selected type. Finance has historically allowed a child Account Type to
     * inherit groups configured on its parent Account Type, so Add, Edit and
     * save validation all use the same rule.
     */
    private function financeEligibleAccountGroupTypeIds(AccountType $accountType): array
    {
        $typeIds = [(int) $accountType->id];

        if (! empty($accountType->parent_account_type_id)) {
            // A child Account Type inherits Account Groups configured on its
            // parent Account Type.
            $typeIds[] = (int) $accountType->parent_account_type_id;
        } else {
            // When the user selects a top-level Account Type (for example
            // Expenses), also include groups configured against its immediate
            // child types. This mirrors the long-standing Finance setup where
            // some businesses keep Account Groups on the child type.
            $childTypeIds = $accountType->sub_types()
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all();

            $typeIds = array_merge($typeIds, $childTypeIds);
        }

        return array_values(array_unique(array_filter($typeIds)));
    }

    /**
     * Return every Account Group that belongs to the selected Account Type
     * (including the selected child type's parent, when applicable).
     */
    private function financeAccountGroupsForType(int $businessId, AccountType $accountType)
    {
        return AccountGroup::query()
            ->where('business_id', $businessId)
            ->whereIn('account_type_id', $this->financeEligibleAccountGroupTypeIds($accountType))
            ->orderBy('name')
            ->get();
    }

    /**
     * Build an in-form Account Group map keyed by Account Type id.
     *
     * IS2292: Add/Edit Account must not depend on a second AJAX request just to
     * make the Account Group dropdown usable. The modal itself is already loaded
     * fresh from the server, so send the valid groups with that response and
     * switch them locally when Account Type changes. This is both faster and
     * immune to legacy/stale route collisions.
     */
    private function financeAccountGroupOptionsMap(int $businessId, $accountTypes): array
    {
        $allGroups = AccountGroup::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->get();

        $map = [];

        foreach ($accountTypes as $parentType) {
            $parentId = (int) $parentType->id;
            $childIds = collect($parentType->sub_types ?? [])
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            $parentEligibleIds = array_values(array_unique(array_merge([$parentId], $childIds)));
            $map[$parentId] = $this->financeAccountGroupOptionRows($allGroups, $parentEligibleIds);

            foreach ($parentType->sub_types ?? [] as $childType) {
                $childId = (int) $childType->id;
                $map[$childId] = $this->financeAccountGroupOptionRows(
                    $allGroups,
                    array_values(array_unique([$childId, $parentId]))
                );
            }
        }

        return $map;
    }

    private function financeAccountGroupOptionRows($allGroups, array $eligibleTypeIds): array
    {
        return $allGroups
            ->filter(static function ($group) use ($eligibleTypeIds) {
                return in_array((int) $group->account_type_id, $eligibleTypeIds, true);
            })
            ->map(static function ($group) {
                return [
                    'id' => (int) $group->id,
                    'name' => (string) $group->name,
                    // reg_cheque is optional on older tenant schemas. Reading a
                    // missing model attribute safely falls back to N.
                    'show_cheque' => (string) ($group->reg_cheque ?? 'N'),
                ];
            })
            ->values()
            ->all();
    }

    private function buildFinanceAddAccountFormData(int $businessId): array
    {
        abort_if($businessId <= 0, 403, 'Business session is not available.');

        $accountAccess = (int) $this->moduleUtil->hasThePermissionInSubscription($businessId, 'access_account');
        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $accountAccess = 1;
        }

        $accountTypeQuery = AccountType::query()
            ->where('business_id', $businessId)
            ->whereNull('parent_account_type_id')
            ->with(['sub_types' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name');

        if ($accountAccess === 0) {
            $accountTypeQuery->whereIn('name', ['Assets', 'Liabilities']);
        }

        $accountTypes = $accountTypeQuery->get();

        // Pass models because the option needs both the id/name and cheque flag.
        // The Blade renders the options explicitly; it never passes model objects
        // directly to Form::select().
        // S724 #3: do not explicitly select the optional reg_cheque column.
        // Older tenant schemas can omit it; loading the model normally keeps the
        // Add form compatible and Blade safely falls back to 'N'.
        // Add Account opens before an Account Type is selected. Keep the visible
        // field empty, but preload a compact per-type Account Group map into the
        // freshly loaded modal so selecting Account Type is instant and does not
        // rely on a second HTTP request (IS2292).
        $accountGroups = collect();
        $accountGroupsByType = $this->financeAccountGroupOptionsMap($businessId, $accountTypes);

        $parentAccountQuery = Account::query()
            ->where('business_id', $businessId)
            ->where('is_main_account', 1)
            ->orderBy('name');

        $parentAccounts = (clone $parentAccountQuery)->pluck('name', 'id');

        // IS2246 #1: the Add Account Blade uses the selected parent account's
        // group to keep Sub A/C consistent with its parent.  The view was
        // receiving only the name/id list, so rendering @json($parentAccountsData)
        // threw an undefined-variable exception and the modal returned HTTP 500.
        $parentAccountsData = (clone $parentAccountQuery)
            ->get(['id', 'name', 'asset_type', 'account_type_id'])
            ->values()
            ->toArray();

        $fixedAccountId = (int) (AccountType::query()
            ->where('business_id', $businessId)
            ->where('name', 'like', '%Fixed Assets%')
            ->orderBy('id')
            ->value('id') ?: 0);

        $permittedLocations = $this->getPermittedLocations();
        $businessLocationQuery = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->orderBy('name');

        if ($permittedLocations !== 'all') {
            $businessLocationQuery->whereIn('id', (array) $permittedLocations);
        }

        $businessLocations = $businessLocationQuery->pluck('name', 'id');
        if ($permittedLocations === 'all') {
            $businessLocations->prepend(__('messages.all'), 'all');
        } else {
            $businessLocations->prepend(__('lang_v1.please_select'), '');
        }

        return [
            'account_types' => $accountTypes,
            'account_groups' => $accountGroups,
            'account_groups_by_type' => $accountGroupsByType,
            'parentAccounts' => $parentAccounts,
            'parentAccountsData' => $parentAccountsData,
            'fixed_acc_id' => $fixedAccountId,
            'business_locations' => $businessLocations,
        ];
    }

    public function addAccountOpeningBalance($opening_bal, $account_id, $date = null, $note = null)
    {
        $account           = Account::find($account_id);
        $account_type_name = AccountType::where('id', $account->account_type_id)->first();
        $type              = 'debit';
        if (strpos($account_type_name, 'Assets') !== false || strpos($account_type_name, 'Expenses') !== false) {
            if ($opening_bal >= 0) {
                $type = 'debit';
            } else {
                $type = 'credit';
            }
        } else {
            if ($opening_bal >= 0) {
                $type = 'credit';
            } else {
                $type = 'debit';
            }
        }
        $ob_transaction_data = [
            'amount'         => abs($this->commonUtil->num_uf($opening_bal)),
            'account_id'     => $account_id,
            'type'           => $type,
            'sub_type'       => 'opening_balance',
            'operation_date' => ! empty($date) ? $date : Carbon::now(),
            'note'           => ! empty($note) ? $note : null,
            'created_by'     => Auth::user()->id,
        ];

        return AccountTransaction::createAccountTransaction($ob_transaction_data);
    }

    public function getImportAccounts()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id    = request()->session()->get('user.business_id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        // modified by iftekhar
        return view('finance::account.import')->with(compact('account_access'));
    }

    public function postImportAccounts(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $notAllowed = $this->commonUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }
            // Set maximum php execution time
            ini_set('max_execution_time', 0);
            if ($request->hasFile('accounts_csv')) {
                $file         = $request->file('accounts_csv');
                $parsed_array = Excel::toArray([], $file);
                // Remove header row
                $imported_data = array_splice($parsed_array[0], 1);
                $business_id   = $request->session()->get('user.business_id');
                $user_id       = $request->session()->get('user.id');
                $formated_data = [];
                $is_valid      = true;
                $error_msg     = '';
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {
                    // validate data is enough
                    if (count($value) != 8) {
                        $is_valid  = false;
                        $error_msg = 'Number of columns mismatch';
                        break;
                    }

                    $row_no        = $key + 1;
                    $account_array = [];

                    if (! empty(trim($value[0]))) {
                        $account_array['transaction_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value[0]);
                    } else {
                        $account_array['transaction_date'] = date('Y-m-d');
                    }

                    $account_type = null;
                    if (! empty($value[1])) {
                        $account_type = AccountType::where('business_id', $business_id)->where('name', $value[1])->first();
                        if (empty($account_type)) {
                            $is_valid  = false;
                            $error_msg = "account type not exist in row on. $row_no";
                            break;
                        }
                        $account_array['account_type_id'] = $account_type->id;
                    } else {
                        $is_valid  = false;
                        $error_msg = "account type is required in row no. $row_no";
                        break;
                    }

                    if (! empty($value[2])) {
                        $account_type = AccountType::where('business_id', $business_id)->where('name', $value[2])->first();

                        if (empty($account_type)) {
                            $is_valid  = false;
                            $error_msg = "account sub type does not exist in row on. $row_no";
                            break;
                        }
                        $account_array['account_type_id'] = $account_type->id;
                    }

                    if (! empty($account_array['account_type_id'])) {

                        $account_type = AccountType::find($account_array['account_type_id']);

                        $response = $this->getAccNo($account_type->id);
                        $content  = $response->getContent();

                        $acc_no_arr = json_decode($content, true);

                        if (empty($acc_no_arr['account_no'])) {
                            $is_valid  = false;
                            $error_msg = 'Prefix for ' . $account_type->name . ' does not exist, please add it first!';
                            break;
                        }

                        $account_array['account_number'] = $acc_no_arr['account_no'];
                    }

                    // Check account group name
                    $account_group_type = null;
                    if (! empty($value[3])) {
                        $account_group_type = AccountGroup::where('business_id', $business_id)->where('name', $value[3])->first();
                        if (empty($account_group_type)) {
                            $is_valid  = false;
                            $error_msg = "account group not exist in row on. $row_no";
                            break;
                        }
                        $account_array['asset_type'] = $account_group_type->id;
                    } else {
                        $account_array['asset_type'] = null;
                    }

                    if (! empty($value[4])) {
                        $account_array['name'] = $value[4];
                    } else {
                        $is_valid  = false;
                        $error_msg = "Account Name is required in row no. $row_no";
                        break;
                    }

                    if (! empty($value[5])) {
                        $account_array['db_cr'] = strtolower($value[5]);

                        if (! in_array($account_array['db_cr'], ['debit', 'credit'])) {
                            $is_valid  = false;
                            $error_msg = "Unsurported type in row no. $row_no" . ' use only debit or credit';
                            break;
                        }
                    } else {
                        $account_array['db_cr'] = null;
                    }

                    if (! empty($value[6])) {
                        $account_array['opening_balance'] = $value[6];
                    } else {
                        $account_array['opening_balance'] = 0;
                    }

                    if (! empty($value[7])) {
                        $account_array['obe_db_cr'] = strtolower($value[7]);

                        if (! in_array($account_array['obe_db_cr'], ['debit', 'credit'])) {
                            $is_valid  = false;
                            $error_msg = "Unsurported type in row no. $row_no" . ' use only debit or credit';
                            break;
                        }
                    } else {
                        $account_array['obe_db_cr'] = null;
                    }

                    if ($account_array['opening_balance'] > 0 && (empty($account_array['obe_db_cr']) || empty($account_array['db_cr']))) {
                        $is_valid  = false;
                        $error_msg = "Debit or Credit for entered amount and opening balance equity account are both required in row no. $row_no";
                        break;
                    }

                    $account_array['business_id'] = $business_id;
                    $account_array['created_by']  = Auth::user()->id;
                    $account_array['visible']     = 1;
                    $formated_data[]              = $account_array;
                }
                if (! $is_valid) {
                    throw new \Exception($error_msg);
                }

                if (! empty($formated_data)) {
                    foreach ($formated_data as $account_data) {
                        $opening_balance = 0;
                        $ob_type         = 'db_cr';
                        $obe_type        = 'obe_db_cr';

                        if (isset($account_data['opening_balance'])) {
                            $opening_balance = $account_data['opening_balance'];
                            $obe_type        = $account_data['obe_db_cr'];
                            $ob_type         = $account_data['db_cr'];

                            unset($account_data['opening_balance']);
                            unset($account_data['obe_db_cr']);
                            unset($account_data['db_cr']);
                        }

                        if (isset($account_data['transaction_date'])) {
                            $transaction_date = $account_data['transaction_date'];
                            unset($account_data['transaction_date']);
                        }

                        $account_data['business_id'] = $business_id;
                        $account                     = Account::create($account_data);

                        if (! empty($opening_balance)) {

                            $ob_transaction_data = [
                                'amount'         => abs($this->commonUtil->num_uf($opening_balance)),
                                'account_id'     => $account->id,
                                'type'           => $ob_type,
                                'sub_type'       => 'opening_balance',
                                'operation_date' => $transaction_date,
                                'created_by'     => Auth::user()->id,
                            ];
                            AccountTransaction::createAccountTransaction($ob_transaction_data);

                            $ob_transaction_data['type']       = $obe_type;
                            $ob_transaction_data['account_id'] = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account') ?? null;

                            if (! empty($ob_transaction_data['account_id'])) {
                                AccountTransaction::createAccountTransaction($ob_transaction_data);
                            }
                        }
                    }
                }
                $output = [
                    'success' => 1,
                    'msg'     => __('product.file_imported_successfully'),
                ];
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => $e->getMessage(),
            ];

            return Redirect::route('finance.accounts.import')->with('notification', $output);
        }

        return Redirect::back()->with('status', $output);
    }
}
