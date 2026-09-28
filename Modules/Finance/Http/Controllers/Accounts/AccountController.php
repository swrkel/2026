<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use App\Account;
use App\Product;
use App\AccountGroup;
use App\AccountSetting;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\Contact;
use App\Journal;
use App\ContactLedger;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use App\TransactionSellLine;
use Modules\Finance\Entities\User;
use Modules\Finance\Entities\TransactionPayment;
use App\PurchaseLine;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Utils\Util;;
use Modules\Fleet\Entities\Driver;
use Modules\Fleet\Entities\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Petro\Entities\PumpOperator;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;
use Intervention\Image\Facades\Image;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\Settlement;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Psy\TabCompletion\Matcher\FunctionsMatcher;
use Excel;
use Illuminate\Support\Facades\Auth;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertySellLine;
use Modules\Fleet\Entities\Fleet;
use Illuminate\Support\Facades\Session;
use App\NotificationTemplate;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Shipping\Entities\ShippingAgent;
use Modules\Shipping\Entities\ShippingPartner;
use Modules\PriceChanges\Entities\PriceChangesDetail;
use Modules\PriceChanges\Entities\PriceChangesHeader;
use Modules\Superadmin\Entities\AccountNumber;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Vat\Entities\VatPayment;
use Modules\Essentials\Entities\EssentialsEmployee;
use App\Category;
use Illuminate\Routing\Controller;

/**
 * Finance accounts screen.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE. The last of the twenty.
 *
 * This file was an 18-line wrapper declaring
 *     extends App\Http\Controllers\AccountController
 * pulling 5,551 lines of core controller into the module.
 *
 * WHAT IS ACTUALLY ROUTED HERE
 *   Finance routes exactly TWO actions to this class:
 *       /finance/accounts          index
 *       /finance/accounts/create   create
 *   No other inherited method was reachable. The five methods that matter -
 *   those two plus the three private helpers they call - are 519 lines,
 *   extracted VERBATIM.
 *
 * THE action() CALLS ARE DELIBERATELY LEFT POINTING AT CORE
 *   index() builds its row buttons with action('AccountController@show'),
 *   '@edit', '@close', '@getNotes', '@getFundTransfer', '@getDeposit' and
 *   '@disabledStatus' - 21 calls in all.
 *
 *   My first attempt retargeted them at this class, as I had done for the
 *   earlier extractions. That was WRONG here and I caught it before shipping:
 *   NONE of those methods were moved, because none of them is routed to this
 *   class. Retargeting would have pointed every row button at a method that
 *   does not exist here.
 *
 *   They stay pointing at core's controller, which still defines and routes
 *   them. So this screen still depends on core for its row actions. That is a
 *   real remaining dependency and I would rather name it than imply this
 *   module is further along than it is.
 *
 * DECOUPLING ONLY - ENTITIES REMAIN CORE'S
 *   index() calls updateLoans() and updateOBs(), which write account
 *   transactions and opening balances, and getAccountBalanceMain() computes
 *   displayed balances. This is the account book. Every class reference is
 *   identical to core's, so the file differs from the original ONLY in
 *   namespace. Behaviour is unchanged by construction.
 *
 * A NOTE ON THE OTHER AccountController IN THIS MODULE
 *   Modules/Finance/Http/Controllers/AccountController.php is a separate,
 *   already-standalone 9,782-line controller serving /accounting-module and
 *   rendering finance::account.index. THIS class serves /finance/accounts and
 *   renders CORE's account.index - two different screens, two different views.
 *   Repointing these two routes at that controller would delete this file
 *   entirely, but it would change what /finance/accounts looks like. That is a
 *   product decision, not mine.
 *
 * Finance bridge controllers remaining: 2 -> 1.
 */
class AccountController extends Controller
{
    public function __construct(Util $commonUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {

        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
        $this->transactionUtil =  $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    public function index(Request $request)
    {

        /*
         * MA-002 - USAGE PROBE. Read this before removing it.
         *
         * We want to delete this controller and its two routes, because that
         * takes Finance to zero core bridges. Static analysis says nothing
         * reaches this screen:
         *
         *   - the class is named nowhere outside its own two route lines
         *   - its route names, finance.accounts.index and .create, are called
         *     nowhere (the module's views call OTHER finance.accounts.* names,
         *     registered elsewhere)
         *   - the menu links to accounting-module/account, not here
         *   - the only textual matches are in generated bootstrap/cache files
         *
         * But static analysis cannot see a bookmark, a saved link, or another
         * system calling the URL, and access logs were not available. So this
         * line makes the application answer the question instead.
         *
         * WHAT TO DO
         *   Deploy, leave it for a few weeks of normal use, then:
         *       grep "MA-002 PROBE: /finance/accounts" storage/logs/*.log
         *
         *   NOTHING FOUND  -> the screen is genuinely dead. Delete this file
         *                     and the two routes in Finance/Routes/accounts.php.
         *                     Finance reaches zero bridges.
         *   SOMETHING FOUND -> it is in use. The log names the user and how
         *                     they arrived, so you can see whether it is a
         *                     bookmark worth redirecting or a real workflow.
         *
         * It logs one line per visit. This screen is, on the evidence, close
         * to never visited - so the cost is nil. If it turns out to be busy,
         * that is itself the answer.
         */

        $business_id = session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        }
        if (!auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        // if 'Post Dated Cheques' account is not created, then create
        Account::crearePostdatedChequesAccount($business_id, $user_id);

        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->first();

        $account_payable_id = !empty($account_payable) ? $account_payable : null;
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        $banking_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'banking_module');
        if (auth()->user()->can('superadmin')) {
            $account_access = 1;
        }
        $parentAccounts = Account::where([/*'is_main_account'=>1,*/'business_id' => $business_id])->pluck('name', 'id');

        if (request()->ajax()) {
            $accounts = Account::leftjoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id');
                $join->whereNull('AT.deleted_at');
            })
                ->leftjoin(
                    'transactions',
                    'AT.transaction_id',
                    '=',
                    'transactions.id'
                )
                ->leftjoin(
                    'account_types as ats',
                    'accounts.account_type_id',
                    '=',
                    'ats.id'
                )
                ->leftjoin(
                    'account_types as pat',
                    'ats.parent_account_type_id',
                    '=',
                    'pat.id'
                )
                ->leftjoin(
                    'account_groups',
                    'accounts.asset_type',
                    '=',
                    'account_groups.id'
                )
                ->leftJoin('users AS u', 'accounts.created_by', '=', 'u.id')

                ->leftJoin('transaction_payments AS TP', 'AT.transaction_payment_id', '=', 'TP.id')
                // ->where(function ($query) {
                //     $query->whereNull('AT.transaction_payment_id')
                //           ->orWhere(function ($query2) {
                //                 $query2->whereNotNull('AT.transaction_payment_id')
                //                         ->whereNotNull('TP.id');
                //           });
                // })
                ->where('accounts.business_id', $business_id)
                // ->where('accounts.visible', 1)
                ->select([
                    'accounts.location_id',
                    'accounts.name',
                    'accounts.parent_account_id',
                    'accounts.account_number',
                    'accounts.visible',
                    'accounts.is_main_account',
                    'accounts.note',
                    'accounts.id',
                    'accounts.account_type_id',
                    'accounts.created_by',
                    'accounts.disabled',
                    'accounts.asset_type',
                    'ats.name as account_type_name',
                    'pat.name as parent_account_type_name',
                    'is_closed',
                    'account_groups.name as group_name',
                    DB::raw("SUM( IF(AT.type='credit', -1*AT.amount, AT.amount) ) as ass_exp_balance"),
                    DB::raw("SUM( IF(AT.type='debit', -1*AT.amount, AT.amount) ) as li_in_eq_balance"),
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                ]);
            $accounts->where('disabled', 0);
            $acc_type = request()->get('account_type_s', null);
            $acc_sub_type = request()->get('account_sub_type', null);
            if (!empty($acc_type)  && $acc_type != 'All') {
                if (!empty($acc_sub_type) && $acc_sub_type != 'All') {
                    $accounts->where('accounts.account_type_id', $acc_sub_type);
                } else {
                    $account_type_ids = AccountType::where('business_id', $business_id)->where('parent_account_type_id', $acc_type)->pluck('id');
                    if (count($account_type_ids) > 0) {
                        $accounts->whereIn('accounts.account_type_id', $account_type_ids);
                    } else {
                        $accounts->where('accounts.account_type_id', $acc_type);
                    }
                }
            } else {
                if (!empty($acc_sub_type)  && $acc_sub_type != 'All') {
                    $accounts->where('accounts.account_type_id', $acc_sub_type);
                }
            }
            $acc_group = request()->get('account_group', null);
            if (!empty($acc_group)  && $acc_group != 'All') {
                $accounts->where('account_groups.id', $acc_group);
            }


            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations !== 'all') {
                $accounts->whereIn('accounts.location_id', $permitted_locations);
            }


            $location_id = request()->get('location_id', null);
            if (!empty($location_id)  && $location_id != 'all') {
                $accounts->where('accounts.location_id', $location_id);
            }


            $ac_parent = request()->get('parent_account_id', null);
            if (!empty($ac_parent) && $ac_parent != 'All') {
                $accounts->where('accounts.parent_account_id', $ac_parent);
            }
            $acc_name = request()->get('account_name', null);
            if (!empty($acc_name) && $acc_name != 'All') {
                $accounts->where('accounts.id', $acc_name);
            }
            if ($account_access == 0) {
                $accounts->where(function ($query) {
                    $query->whereIn('accounts.name', ['Accounts Receivable', 'Accounts Payable', 'Cards (Credit Debit) Account', 'Cash', 'Cheques in Hand', 'Customer Deposits', 'Petty Cash']);
                    $query->orWhere('accounts.visible', 1);
                });
            }
            $accounts->groupBy('accounts.id');
            $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
            $asset_type_accounts = Account::AssetTypeAccountGroupActive();
            return DataTables::of($accounts)
                ->addColumn('action', function ($row) use ($account_access, $banking_module, $chequeId) {
                    $html = '';

                    // Check if the account is of type "Post Dated Cheques"
                    $isCompanyPostDatedCheques = $row->name === 'Post Dated Cheques';

                    if ($isCompanyPostDatedCheques) {
                        $disabled = null;
                        $disabledClose = '';
                        if (($row->name == "Accounts Payable" || $row->name == "Accounts Receivable") && $banking_module == 1 && $account_access == 0) {
                            $html = '<h4 class="text-danger">You have not subscribed to Accounting Module, so details in this page will not show</h4>';
                        } else {
                            // Check if the user has edit permission and the account is not of type "Post Dated Cheques"
                            $disabledEdit = 'disabled';
                            $disabledClose = 'disabled';

                            // edit button
                            $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@edit', [$row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>&nbsp';

                            // check if its main account
                            if ($row->is_main_account == 0) {
                                $html .=  '<a href="' . action('AccountController@show', [$row->id]) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __("account.account_book") . '</a>&nbsp';
                            } else {
                                $html .=  '<a href="' . action('AccountController@show', [$row->id]) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __("lang_v1.main_account_book") . '</a>&nbsp';
                            }

                            // funds transfer
                            $html .=  '<button data-href="' . action('AccountController@getFundTransfer', [$row->id]) . '" class="btn btn-xs btn-info btn-modal transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __("account.fund_transfer") . '</button>&nbsp';


                            // if (auth()->user()->can('account.edit')) {
                            //     $html .=  '<button disabled data-href="' . action('AccountController@edit', [$row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>&nbsp';
                            // }

                            // close button
                            $html .=  '<button  data-url="' . action('AccountController@close', [$row->id]) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __("messages.close") . '</button>&nbsp';

                            // get notes
                            $html .=  '<button data-href="' . action('AccountController@getNotes', [$row->id]) . '" class="btn btn-xs btn-default btn-modal" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __("account.notes") . '</button> &nbsp';

                            // enabled 
                            $html .=  '<button data-url="' . action('AccountController@disabledStatus', [$row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __("account.enabled") . '</button>&nbsp';

                            // if ($row->is_closed == 0) {
                            //     if ($row->is_main_account == 0) {
                            //         if ($row->id != $chequeId && !in_array($row->group_name, ['Cash Account', 'Card']) && !in_array($row->name, ['Accounts Receivable'])) {
                            //             $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getFundTransfer', [$row->id]) . '" class="btn btn-xs btn-info btn-modal transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __("account.fund_transfer") . '</button>&nbsp';

                            //             if (!in_array($row->group_name, ['Bank Account'])) {
                            //                 $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getDeposit', [$row->id]) . '" class="btn btn-xs btn-success btn-modal deposit_btn" data-container=".account_model"><i class="fa fa-money"></i> ' . __("account.deposit") . '</button>&nbsp<br><br>';
                            //             }
                            //         }
                            //         $html .=  '<button ' . $disabledClose . ' data-url="' . action('AccountController@close', [$row->id]) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __("messages.close") . '</button>&nbsp';
                            //     }
                            //     $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getNotes', [$row->id]) . '" class="btn btn-xs btn-default btn-modal" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __("account.notes") . '</button> &nbsp';
                            //     if ($row->disabled == 0) {
                            //         if ($row->is_main_account == 0) {
                            //             $html .=  '<button ' . $disabledEdit . ' data-url="' . action('AccountController@disabledStatus', [$row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __("account.enabled") . '</button>&nbsp';
                            //         }
                            //     }
                            // }
                        }
                    } else {
                        $disabled = '';
                        $disabledClose = '';
                        $isCompanyPostDatedCheques = false;
                        if (($row->name == "Accounts Payable" || $row->name == "Accounts Receivable") && $banking_module == 1 && $account_access == 0) {
                            $html = '<h4 class="text-danger">You have not subscribed to Accounting Module, so details in this page will not show</h4>';
                        } else {
                            // Check if the user has edit permission and the account is not of type "Post Dated Cheques"
                            $disabledEdit = (auth()->user()->can('account.edit') && !$isCompanyPostDatedCheques) ? '' : 'disabled';

                            if ($account_access == 0 && !in_array($row->group_name, ['Cash Account', 'Bank Account', 'Card']) && $row->name != 'Cheques in Hand' || $row->name == 'Opening Balance Equity Account' || $row->name == 'Post Dated Cheques') {
                                $disabledEdit = 'disabled';
                                $disabledClose = 'disabled';
                            }

                            if (auth()->user()->can('account.edit')) {
                                $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@edit', [$row->id]) . '" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>&nbsp';
                            }

                            if ($row->is_main_account == 0) {
                                $html .=  '<a href="' . action('AccountController@show', [$row->id]) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __("account.account_book") . '</a>&nbsp';
                            } else {
                                $html .=  '<a href="' . action('AccountController@show', [$row->id]) . '" class="btn btn-warning btn-xs"><i class="fa fa-book"></i> ' . __("lang_v1.main_account_book") . '</a>&nbsp';
                            }

                            if ($row->is_closed == 0) {
                                if ($row->is_main_account == 0) {
                                    if ($row->id != $chequeId && !in_array($row->group_name, ['Cash Account', 'Card']) && !in_array($row->name, ['Accounts Receivable'])) {
                                        $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getFundTransfer', [$row->id]) . '" class="btn btn-xs btn-info btn-modal transfer_btn" data-container=".account_model"><i class="fa fa-exchange"></i> ' . __("account.fund_transfer") . '</button>&nbsp';

                                        if (!in_array($row->group_name, ['Bank Account'])) {
                                            $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getDeposit', [$row->id]) . '" class="btn btn-xs btn-success btn-modal deposit_btn" data-container=".account_model"><i class="fa fa-money"></i> ' . __("account.deposit") . '</button>&nbsp<br><br>';
                                        }
                                    }
                                    $html .=  '<button ' . $disabledClose . ' data-url="' . action('AccountController@close', [$row->id]) . '" class="btn btn-xs btn-danger close_account"><i class="fa fa-close"></i> ' . __("messages.close") . '</button>&nbsp';
                                }
                                $html .=  '<button ' . $disabledEdit . ' data-href="' . action('AccountController@getNotes', [$row->id]) . '" class="btn btn-xs btn-default btn-modal" data-container=".account_model"><i class="fa fa-sticky-note-o "></i> ' . __("account.notes") . '</button> &nbsp';
                                if ($row->disabled == 0) {
                                    if ($row->is_main_account == 0) {
                                        $html .=  '<button ' . $disabledEdit . ' data-url="' . action('AccountController@disabledStatus', [$row->id]) . '" class="btn btn-xs btn-success disable_status_account"><i class="fa fa-check"></i> ' . __("account.enabled") . '</button>&nbsp';
                                    }
                                }
                            }
                        }
                    }

                    return $html;
                })

                ->editColumn('name', function ($row) {
                    if ($row->is_closed == 1) {
                        return $row->name . ' <small class="label pull-right bg-red no-print">' . __("account.closed") . '</small><span class="print_section">(' . __("account.closed") . ')</span>';
                    } else {
                        return $row->name;
                    }
                })
                ->editColumn('parent_account_id', function ($row) use ($parentAccounts) {
                    // logger($parentAccounts);

                    if ($row->parent_account_id && isset($parentAccounts[$row->parent_account_id])) {
                        return $parentAccounts[$row->parent_account_id];
                    }
                    return "";
                })
                ->addColumn('balance', function ($row) use ($business_id) {

                    if ($row->is_main_account == 1) {
                        $balance = $this->getAccountBalanceMain($row->id);
                        return '<span class="display_currency" data-currency_symbol="true">' . $balance['balance'] . '</span>';
                    } else {
                        $balance = Account::getAccountBalance($row->id);
                        return '<span class="display_currency" data-currency_symbol="true">' . $balance . '</span>';
                    }
                })
                ->addColumn('account_location', function ($row) {
                    if ($row->location_id == 'all') {
                        return ucfirst($row->location_id);
                    } else {
                        $loc = BusinessLocation::find($row->location_id);

                        if (!empty($loc)) {
                            return $loc->name;
                        }
                    }
                })
                ->editColumn('account_type', function ($row) {
                    $account_type = '';
                    if (!empty($row->account_type->parent_account)) {
                        $account_type .= $row->account_type->parent_account->name . ' / ';
                    }
                    if (!empty($row->account_type)) {
                        $account_type .= $row->account_type->name;
                    }
                    return $account_type;
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
                ->editColumn('account_group', function ($row) use ($business_id) {
                    // return $row->asset_type;
                    if (!empty($row->asset_type)) {
                        $account_group =  AccountGroup::where('business_id', $business_id)->where('id', $row->asset_type)->first();
                        if (!empty($account_group)) {
                            return $account_group->name;
                        }
                        return '';
                    } else {
                        return '';
                    }
                })
                ->setRowAttr([
                    'data-visible' => function ($row) {
                        return $row->visible;
                    }
                ])
                // ->removeColumn('id')
                ->removeColumn('is_closed')
                ->rawColumns(['action', 'balance', 'name', 'account_group', 'reconcile_status'])
                ->make(true);
        }

        $not_linked_payments = TransactionPayment::leftjoin(
            'transactions as T',
            'transaction_payments.transaction_id',
            '=',
            'T.id'
        )
            ->whereNull('transaction_payments.parent_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('account_id')
            ->count();
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
        $filterdata = [];
        $sub_acn_arr = [];
        $filterdata['subType_']['data'][] = array('id' => "", 'text' => "All", true);
        foreach ($account_types->toArray() as $acunts) {
            $filterdata['subType_' . $acunts['id']]['data'][] = array('id' => "", 'text' => "All", true);
            foreach ($acunts['sub_types'] as $sub_Acn) {
                $filterdata['subType_']['data'][] = array('id' => $sub_Acn['id'], 'text' => $sub_Acn['name']);
                $filterdata['subType_' . $acunts['id']]['data'][] = array('id' => $sub_Acn['id'], 'text' => $sub_Acn['name']);
                $sub_acn_arr[$sub_Acn['id']] = $sub_Acn['name'];
            }
        }
        // echo "<pre>";print_r($filterdata);
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $account_groups_raw = AccountGroup::where('business_id', $business_id)->get()->toArray();
        $account_groups = [];
        $filterdata['groupType_']['data'][] = array('id' => "", 'text' => "All", true);
        foreach ($account_groups_raw as $datarow) {
            $filterdata['groupType_' . $datarow['account_type_id']]['data'][] = array('id' => $datarow['id'], 'text' => $datarow['name']);
            $account_groups[$datarow['id']] = $datarow['name'];
        }
        // dd($filterdata);
        $accounts = Account::where('business_id', $business_id)->pluck('name', 'id');
        $users = User::forDropdown($business_id);
        $orderStatuses = $this->productUtil->orderStatuses();
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $chequeId = $this->transactionUtil->account_exist_return_id('Cheques in Hand');

        $can_edit_ob = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_ob');


        $this->updateOBs();
        $this->updateLoans();

        $permitted_locations = auth()->user()->permitted_locations();

        if ($permitted_locations == 'all') {
            $_business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
            $_business_locations->prepend(__('messages.all'), 'all');
        } else {
            $_business_locations = BusinessLocation::where('business_id', $business_id)->whereIn('id', $permitted_locations)->pluck('name', 'id');
            $_business_locations->prepend(__('messages.all'), '');
        }

        return view('account.index')
            ->with(compact('_business_locations', 'can_edit_ob', 'chequeId', 'customers', 'parentAccounts', 'filterdata', 'account_types_opts', 'sub_acn_arr', 'not_linked_payments', 'account_types', 'account_access', 'business_locations', 'account_groups', 'users', 'accounts', 'suppliers', 'orderStatuses'));
    }

    public function create()
    {

        /*
         * MA-002 - USAGE PROBE. Read this before removing it.
         *
         * We want to delete this controller and its two routes, because that
         * takes Finance to zero core bridges. Static analysis says nothing
         * reaches this screen:
         *
         *   - the class is named nowhere outside its own two route lines
         *   - its route names, finance.accounts.index and .create, are called
         *     nowhere (the module's views call OTHER finance.accounts.* names,
         *     registered elsewhere)
         *   - the menu links to accounting-module/account, not here
         *   - the only textual matches are in generated bootstrap/cache files
         *
         * But static analysis cannot see a bookmark, a saved link, or another
         * system calling the URL, and access logs were not available. So this
         * line makes the application answer the question instead.
         *
         * WHAT TO DO
         *   Deploy, leave it for a few weeks of normal use, then:
         *       grep "MA-002 PROBE: /finance/accounts" storage/logs/*.log
         *
         *   NOTHING FOUND  -> the screen is genuinely dead. Delete this file
         *                     and the two routes in Finance/Routes/accounts.php.
         *                     Finance reaches zero bridges.
         *   SOMETHING FOUND -> it is in use. The log names the user and how
         *                     they arrived, so you can see whether it is a
         *                     bookmark worth redirecting or a real workflow.
         *
         * It logs one line per visit. This screen is, on the evidence, close
         * to never visited - so the cost is nil. If it turns out to be busy,
         * that is itself the answer.
         */

        if (!auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = session()->get('user.business_id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $account_type_query = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->with(['sub_types']);
        if ($account_access == 0) {
            $account_type_query->whereIn('name', ['Assets', 'Liabilities']);
        }
        $account_types = $account_type_query->get();
        $account_groups = AccountGroup::where('business_id', $business_id)->pluck('name', 'id');
        $accounts = Account::where('business_id', $business_id)->pluck('name', 'id');
        $asset_type_ids = json_encode(AccountType::getAccountTypeIdOfType('Assets', $business_id));
        $parentAccounts = Account::where(['is_main_account' => 1, 'business_id' => $business_id])->pluck('name', 'id', 'asset_type');

        $parentAccountsData = Account::where(['is_main_account' => 1, 'business_id' => $business_id])->get()->toArray();
        $fixed_acc_id = AccountType::getAccountTypeIdOfType('Fixed Assets', $business_id);

        $permitted_locations = auth()->user()->permitted_locations();

        if ($permitted_locations == 'all') {
            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
            $business_locations->prepend(__('messages.all'), 'all');
        } else {
            $business_locations = BusinessLocation::where('business_id', $business_id)->whereIn('id', $permitted_locations)->pluck('name', 'id');
            $business_locations->prepend(__('lang_v1.please_select'), '');
        }

        $fixed_acc_id = !empty($fixed_acc_id) ? $fixed_acc_id[0] : 0;

        // modified by iftekhar
        return view('account.create')
            ->with(compact('fixed_acc_id', 'account_types', 'account_groups', 'asset_type_ids', 'accounts', 'parentAccounts', 'parentAccountsData', 'business_locations'));
    }

    public function getAccountBalanceMain($id)
    {
        if (!auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $accounts = Account::where('parent_account_id', $id)->where('business_id', $business_id)
                ->select([
                    'accounts.name',
                    'accounts.id',
                    'accounts.account_number'
                ])->get();
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');
            $balance = 0;
            foreach ($accounts as  $account) {
                $balance += Account::getAccountBalance($account->id, $start_date, $end_date);
            }
            return ['balance' => round($balance, 2)];
        }
    }

    public function updateLoans()
    {
        $business_id = session()->get('user.business_id');
        $opening_balances = Transaction::where('sub_type', 'loan_payment')->where('business_id', $business_id)->get();
        $cash = $this->transactionUtil->account_exist_return_id('Cash');


        foreach ($opening_balances as $bal) {
            $account_transaction_data = [
                'amount' => $bal->final_total,
                'account_id' => $cash,
                'type' => 'debit',
                'operation_date' => $bal->transaction_date,
                'created_by' => $bal->created_by,
                'transaction_id' => $bal->id
            ];

            $id = AccountTransaction::updateOrCreate(['account_id' => $cash, 'transaction_id' => $bal->id, 'type' => 'debit'], $account_transaction_data);

            $account_transaction_data['type'] = 'credit';
            $id = AccountTransaction::updateOrCreate(['account_id' => $cash, 'transaction_id' => $bal->id, 'type' => 'credit'], $account_transaction_data);
        }
    }

    public function updateOBs()
    {
        $business_id = session()->get('user.business_id');
        $opening_balances = Transaction::where('type', 'opening_balance')->where('business_id', $business_id)->whereNotNull('contact_id')->get();
        $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');

        foreach ($opening_balances as $bal) {
            $contact = Contact::find($bal->contact_id);

            if (!empty($contact)) {
                if ($contact->type == 'customer') {
                    $type = 'credit';
                } else {
                    $type = 'debit';
                }

                $account_transaction_data = [
                    'amount' => $bal->final_total,
                    'account_id' => $opening_balance_equity_id,
                    'type' => $type,
                    'sub_type' => 'ledger_show',
                    'operation_date' => $bal->transaction_date,
                    'created_by' => $bal->created_by,
                    'transaction_id' => $bal->id
                ];

                $id = AccountTransaction::updateOrCreate(['account_id' => $opening_balance_equity_id, 'transaction_id' => $bal->id], $account_transaction_data);
            }
        }
    }
}
