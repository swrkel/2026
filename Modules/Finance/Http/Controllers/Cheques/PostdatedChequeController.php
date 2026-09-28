<?php

namespace Modules\Finance\Http\Controllers\Cheques;

use App\AccountGroup;
use App\Business;
use App\ExpenseCategory;
use App\Package;
use App\Subscription;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\Contact;
use Modules\Finance\Entities\TransactionPayment;
use Yajra\DataTables\Facades\DataTables;

/**
 * Finance post-dated cheques listing.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * This class previously declared
 *     extends App\Http\Controllers\PostdatedChequeController
 * pulling 964 lines of core controller into the Finance module.
 *
 * CHECKED BEFORE MOVING IT
 *   - Only ONE action is routed here: index, via
 *     Modules/Finance/Routes/cheques.php. No inherited method was
 *     reachable through any route.
 *   - index() is 236 lines and contains exactly ONE action() call, which is
 *     the fewest of any remaining bridge - that is why this one was chosen.
 *   - Its only $this-> dependencies were productUtil, transactionUtil and a
 *     15-line private helper, getBankAccountByGroupName().
 *
 * WHAT MOVED
 *   The method is reproduced VERBATIM - same joins, same conditions, same
 *   column formatting - so the listing cannot change. Account,
 *   AccountTransaction, BusinessLocation, Contact and TransactionPayment now
 *   resolve to FINANCE'S OWN entities. getBankAccountByGroupName is copied in
 *   rather than inherited. The view was core's postdated_cheques.index; a copy
 *   now lives in the module.
 *
 * WHAT DELIBERATELY DID NOT MOVE, AND IS STILL A CORE DEPENDENCY
 *   - ProductUtil and TransactionUtil are injected. index() calls
 *     productUtil->num_f() for currency formatting and
 *     transactionUtil->payment_types(). Finance uses these two utilities
 *     throughout; extracting them is the 22,000-line problem flagged
 *     separately, not something to smuggle into this parcel.
 *   - Business, Subscription, Package, ExpenseCategory and AccountGroup stay
 *     as App\ classes. Finance has no equivalents, and inventing duplicates
 *     is exactly the drift that caused the ContactLedger and Journal bugs.
 *   - One row action still calls
 *         action("CustomerPaymentController@viewPayment", [$tp_id])
 *     which resolves to CORE's controller. It is a single link in a row
 *     dropdown, and re-pointing it means moving that controller too.
 *
 * So this is a real reduction - 964 lines of implicit inheritance replaced by
 * a handful of explicit, visible imports - but it is not independence, and I
 * would rather say so than imply otherwise.
 *
 * Finance bridge controllers remaining: 7 -> 6.
 */
class PostdatedChequeController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_details = Business::find($business_id);
        if (request()->ajax()) {
           $sells = AccountTransaction::leftJoin(
                    'accounts as A',
                    'account_transactions.account_id',
                    '=',
                    'A.id'
                )
                ->leftJoin('transaction_payments AS tp', 'account_transactions.transaction_payment_id', '=', 'tp.id')
                
                ->leftJoin(
                    'accounts as Arelated',
                    'tp.related_account_id',
                    '=',
                    'Arelated.id'
                )
                
                ->leftJoin(
                    'transactions',
                    'transactions.id',
                    '=',
                    'account_transactions.transaction_id'
                )
                ->leftJoin('contacts', 'tp.payment_for', '=', 'contacts.id')
                ->leftJoin('users', 'tp.created_by', '=', 'users.id')
                ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->leftjoin(
                    'account_types as ats',
                    'A.account_type_id',
                    '=',
                    'ats.id' 
                )
                ->where('A.business_id', $business_id)
                ->where(function ($query) {
                    $query->where('account_transactions.post_dated_cheque',1)
                        ->orWhere('tp.post_dated_cheque', 1);
                })
                ->whereDate('tp.cheque_date', '>=', DB::raw('CURDATE()'))
                ->whereNull('account_transactions.deleted_at')
                ->select(
                    'tp.note',
                    'transactions.id',
                    DB::raw('COALESCE(tp.paid_on, transactions.transaction_date) as transaction_date'),
                    'transactions.invoice_no',
                    'A.name as bank_name',
                    'Arelated.name as related_bank_name',
                    'contacts.name',
                    'ec.name as expense_category_name',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'business_locations.name as location_name',
                    'tp.id as tp_id',
                    'tp.cheque_date',
                    'tp.method',
                    'account_transactions.id as act_id',
                    'account_transactions.interest',
                    'tp.parent_id',
                    'tp.cheque_number',
                    'tp.card_number',
                    'tp.payment_ref_no',
                    'tp.paid_in_type',
                    'tp.created_by',
                     'users.username',
                     'tp.amount as total_paid',
                     'contacts.type as contact_type'
                );
                
            // dd($sells);
             if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                    
                if(request()->post_party_type == 'others'){
                    $sells->where('A.id',$customer_id)->where('account_transactions.type','credit');
                }elseif(request()->post_party_type == 'expense_payments'){
                    $sells->where('transactions.expense_category_id', $customer_id);
                }else{
                    $sells->where('contacts.id', $customer_id);
                }
                
            }
            
            if (!empty(request()->post_party_type)) {
                if(request()->post_party_type == 'others'){
                    $sells->whereNull('contacts.id');
                }else if(request()->post_party_type != 'expense_payments'){
                    $type = request()->post_party_type;
                    $sells->where('contacts.type', $type);
                }
                
            }
            
            if (!empty(request()->bill_no)) {
                $sells->where('transactions.invoice_no', request()->bill_no);
            }
            if (!empty(request()->bank)) {
                $sells->where('account_transactions.account_id', request()->bank);
            }
            if (!empty(request()->cheque_number)) {
                $sells->where('tp.cheque_number', request()->cheque_number);
            }
            if (!empty(request()->payment_method)) {
                $sells->where('tp.method', request()->payment_method);
            }

            if (!empty(request()->payment_amount)) {
                $sells->where('tp.amount', request()->payment_amount);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('tp.cheque_date', '>=', $start)
                    ->whereDate('tp.cheque_date', '<=', $end);
            }
            
            $subscription = Subscription::active_subscription($business_id);
            $pacakge_details = $subscription->package_details;
            
            if(!empty($package_details) && !empty($package_details->post_dated_cheques_effective_date)){
                $sells->whereDate('tp.cheque_date', '>=', $package_details->post_dated_cheques_effective_date);
            }
            
            
            $sells->orderBy('tp.cheque_date', 'desc')->groupBy('tp.payment_ref_no');

            $datatable = DataTables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false"> @lang("messages.actions")<span class="caret"></span><span class="sr-only">Toggle Dropdown

                                </span>
                        </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">
                    <li><a href="{{action("CustomerPaymentController@viewPayment", [$tp_id])}}" class="view_payment_modal"><i class="fa fa-money" aria-hidden="true" ></i> @lang("purchase.view_payments")</a></li>
                    </ul></div>'
                )
                ->addColumn('payment_amount', function ($row) use ($business_details) {
                    if (!empty($row->parent_id)) {
                        $parent_payment = TransactionPayment::where('id', $row->parent_id)->first();
                        if (!empty($parent_payment)) {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $parent_payment->amount . '">' . $this->productUtil->num_f($parent_payment->amount, false, $business_details, false) . '</span>';
                        } else {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                        }
                    } else {
                        return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                    }
                })->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('cheque_date', '{{@format_date($cheque_date)}}')
                ->editColumn('bank_name', function ($row) {
                    if(!empty($row->related_bank_name)){
                        return ucfirst($row->related_bank_name);
                    }else{
                        return ucfirst($row->bank_name);
                    }
                    
                })
                ->editColumn('note', function ($row)  {
                    $bank_name = !empty($row->related_bank_name) ? $row->related_bank_name : $row->bank_name;
                    $html = $row->note;

                    if (!empty($row->expense_category_name)) {
                        $html = trim($row->expense_category_name . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                    }
                    
                    if(!empty($html)){
                        return '<button type="button" class="btn btn-xs note_btn" style="background: #8F3A84; color:#fff;" data-string="' . $html . '">' . __('lang_v1.note') . '</button>';
                    
                    }else{
                        return '';
                    }
                    
                    
                   
                })
                ->editColumn('contact_type', function ($row) {
                    if(empty($row->contact_type)){
                        return __('account.others');
                    }
                    return ucfirst($row->contact_type);
                })
                
                ->editColumn('name', function ($row) {
                    if(empty($row->contact_type)){
                        if(!empty($row->related_bank_name)){
                            return ucfirst($row->related_bank_name);
                        }else{
                            return ucfirst($row->bank_name);
                        }
                    }
                    return ucfirst($row->name);
                })
                
                ->editColumn('cheque_number', function ($row) {
                    return $row->cheque_number;
                })
                ->setRowAttr([

                ]);
            $rawColumns = ['name', 'bank_name', 'action', 'payment_amount','note'];
            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }
        
        $business_id = request()->session()->get('business.id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        $suppliers = Contact::suppliersDropdown($business_id, false, true);
        $customers = Contact::customersDropdown($business_id, false, true);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();
        $banks = $this->getBankAccountByGroupName('Bank Account', $business_id, null);
        $package_manage = Package::where('only_for_business', $business_id)->first();
        $accounts = Account::forDropdown($business_id,false);
        
        $expense_categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');


        return view('finance::postdated_cheques.index')->with(compact(
            'contacts',
            'business_locations',
            'banks',
            'suppliers',
            'customers',
            'accounts',
            'expense_categories'
        ));
    }


    public function getBankAccountByGroupName($group_name, $business_id, $location_id)
    { 
        if(!empty($group_name)){
                $group_id = AccountGroup::where('business_id', $business_id)->where('name', $group_name)->first();
                if (!empty($group_id)) {
                    $accounts = Account::where('business_id', $business_id)->where('asset_type', $group_id->id)->where('is_main_account', 0)->pluck('name', 'id');
                } else {
                    $accounts = [];
                }
            
        }else{
            $accounts = [];
        }
        return $accounts;
    } 
}
