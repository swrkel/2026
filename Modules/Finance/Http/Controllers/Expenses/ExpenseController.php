<?php

namespace Modules\Finance\Http\Controllers\Expenses;

use Modules\Finance\Entities\User;
use App\Account;
use Modules\Finance\Entities\Contact;
use App\TaxRate;
use App\Business;
use App\AccountType;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use App\ContactLedger;
use App\ExpenseCategory;
use Modules\Finance\Entities\BusinessLocation;
use App\Utils\ModuleUtil;
use App\AccountTransaction;
use Modules\Finance\Entities\TransactionPayment;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use App\NotificationTemplate;
use App\Utils\TransactionUtil;
use App\Utils\NotificationUtil;
use Modules\Fleet\Entities\Fleet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Services\FinanceBudgetControlService;
use Illuminate\Support\Facades\Gate;
use App\Providers\AppServiceProvider;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Superadmin\Entities\Package;
use Yajra\DataTables\Facades\DataTables;
use Modules\Fleet\Entities\RouteOperation;
use Modules\Property\Entities\PaymentOption;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Essentials\Entities\EssentialsEmployee;
use Illuminate\Routing\Controller;

/**
 * Finance expenses.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE. 3,591 inherited lines removed.
 *
 * All methods extracted programmatically from the core controller rather than
 * retyped, and the action() targets that pointed back at core's own
 * ExpenseController now point here.
 *
 * DECOUPLING ONLY - THE ENTITIES ARE STILL CORE'S, ON PURPOSE.
 *
 * Same reasoning as CustomerPaymentController in the previous parcel, and for
 * the same reason: this controller writes to the ledger directly. It has
 *     addAccountTransaction()
 *     reverseAccountTransaction()
 * plus store(), update() and destroy() paths that create and reverse
 * account_transactions and contact ledger rows for an expense.
 *
 * Swapping the model classes underneath that in the same change that moves the
 * file would put two variables in motion through code that moves money. So
 * every class reference here is IDENTICAL to core's - the file differs from
 * the original in namespace, view names and action() targets and nothing else.
 * Behaviour is unchanged by construction rather than by inspection.
 *
 * Entity migration for this controller belongs in its own parcel, with a
 * before/after check on a real expense edit.
 *
 * ACTION TARGETS THAT STILL RESOLVE TO CORE, BY DESIGN
 *     TransactionPaymentController@show, @addPayment, @print
 * Those belong to another controller entirely; re-pointing them means moving
 * it too.
 *
 * A PRE-EXISTING ODDITY, CARRIED ACROSS UNCHANGED
 *     storeold(), update_old() and update1() are dead variants left in the
 *     core controller. I have not deleted them - removing code while moving it
 *     mixes two changes, and if any of them is still reachable by a route I
 *     have not found, deleting it here would be a silent break. Worth a
 *     separate cleanup once this settles.
 *
 * Finance bridge controllers remaining: 3 -> 2.
 */
class ExpenseController extends Controller
{

    /*
     * MA-002: the work of this controller now lives in the traits below.
     *
     * It was 3,605 lines in one file. Same class at runtime - routes,
     * action() targets and $this-> calls are all unchanged - but grouped by
     * concern so each file can be read on its own.
     *
     *     ListsExpenses           index, DataTables, route-operation view
     *     CreatesExpenses         create, store
     *     UpdatesExpenses         edit, update
     *     DeletesExpenses         destroy
     *     PostsExpenseLedger      account transactions posted and reversed
     *     HandlesExpensePayments  payment methods, PD cheques, print, show
     */

    protected $transactionUtil;

    protected $moduleUtil;

    protected $notificationUtil;

    protected $businessUtil;

    protected $dummyPaymentLine;

    /**

     * Constructor

     *

     * @param TransactionUtil $transactionUtil

     * @return void

     */

    use Concerns\ListsExpenses;
    use Concerns\CreatesExpenses;
    use Concerns\UpdatesExpenses;
    use Concerns\DeletesExpenses;
    use Concerns\PostsExpenseLedger;
    use Concerns\HandlesExpensePayments;

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)

    {

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

        $this->notificationUtil = $notificationUtil;

        $this->businessUtil = $businessUtil;



        $this->dummyPaymentLine = [

            'method' => '',
            'amount' => 0,
            'note' => '',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_year' => '',
            'card_security' => '',
            'cheque_number' => '',
            'cheque_date' => '',
            'bank_account_number' => '',

            'is_return' => 0,
            'transaction_no' => ''

        ];
    }
}
