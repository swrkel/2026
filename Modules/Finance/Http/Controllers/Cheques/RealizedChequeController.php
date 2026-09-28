<?php

namespace Modules\Finance\Http\Controllers\Cheques;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountTransaction;
use Yajra\DataTables\Facades\DataTables;

/**
 * Finance realized cheques listing.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * This class previously declared
 *     extends App\Http\Controllers\RealizedChequeController
 * and inherited its single index() method - 106 lines of core code - along
 * with that controller's dependencies on App\Account, App\AccountTransaction,
 * App\Business, App\Contact, App\AccountType, App\AccountGroup,
 * App\ContactGroup, App\Transaction, App\TransactionPayment, App\User and
 * three core Utils.
 *
 * The logic now lives here, using FINANCE'S OWN entities:
 *     Modules\Finance\Entities\Account
 *     Modules\Finance\Entities\AccountTransaction
 *
 * WHAT WAS CHECKED BEFORE MOVING IT
 *   - Only one route points at this class: cheques.realized.index -> index.
 *     No other method was reachable.
 *   - The core version injected TransactionUtil, ModuleUtil and ProductUtil
 *     in its constructor and then never used any of them in index(). They are
 *     not carried over.
 *   - Account::getAccountByAccountName() exists on Finance's Account entity,
 *     so the cheque-account lookup needed no change.
 *   - The view was core's realized_cheques.index. A copy now lives at
 *     Modules/Finance/Resources/views/realized_cheques/index.blade.php and is
 *     referenced through the module namespace, so Finance no longer depends
 *     on a core view either.
 *
 * The query itself is reproduced exactly - same joins, same conditions, same
 * ordering, same selected columns, same column formatting. This is a
 * relocation, not a rewrite.
 */
class RealizedChequeController extends Controller
{
    /**
     * List cheques that have been deposited and realized.
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $cheque_account = Account::getAccountByAccountName('Cheques in Hand');

            $query = AccountTransaction::join('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
                ->leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
                ->leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('contacts', 'transaction_payments.payment_for', 'contacts.id')
                ->leftjoin('users', 'users.id', 'account_transactions.created_by')
                ->where('account_transactions.account_id', '!=', $cheque_account->id)
                ->where('transaction_payments.method', 'cheque')
                ->where('account_transactions.type', 'debit')
                ->where('transaction_payments.is_deposited', 1)
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.is_realized', 1);

            $cheque_lists = $query->select(
                'contacts.name as customer_name',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_date',
                'accounts.name as bank_name',
                'account_transactions.amount',
                'transaction_payments.id',
                'transactions.id as t_id',
                'users.username',
                'transaction_payments.updated_at',
                'accounts.account_number'
            )->orderBy('transaction_payments.cheque_date', 'desc')->get();

            $datatable = DataTables::of($cheque_lists)
                ->editColumn('cheque_date', '{{@format_date($cheque_date)}}')
                ->editColumn('updated_at', '{{@format_datetime($updated_at)}}')
                ->editColumn('amount', '{{@num_format($amount)}}');

            $rawColumns = ['name', 'bank_name', 'action', 'payment_amount'];

            return $datatable->rawColumns($rawColumns)->make(true);
        }

        return view('finance::realized_cheques.index');
    }
}
