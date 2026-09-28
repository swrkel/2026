<?php
namespace Modules\Finance\Http\Controllers;

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
use Modules\Superadmin\Entities\AccountNumber;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatPayment;
use Modules\Finance\Services\Reports\FinanceIntegrationLedgerService;
use Modules\Finance\Services\Accounts\AccountBookDataService;
use Modules\Finance\Services\Accounts\AccountListQueryService;
use Modules\Finance\Services\Deposits\BankDepositAccountResolver;
use Modules\Finance\Services\Deposits\CardDepositAccountResolver;
use Modules\Finance\Services\Deposits\ChequeDepositListService;
use Yajra\DataTables\Facades\DataTables;

class AccountController extends Controller
{

    /*
     * MA-002: this controller was 9,782 lines. Its work now lives in the
     * traits below, grouped by what each does. Same class at runtime - the 84
     * routes pointing here, the action() targets and the $this-> calls between
     * the 98 methods all resolve exactly as before.
     *
     *   ManagesAccounts             create, edit, close, import
     *   ShowsAccountBook            the account book screen
     *   HandlesAccountTransactions  editing and de-duplicating rows
     *   HandlesDepositsAndTransfers deposits, transfers, cheque OBs
     *   HandlesCheques              cheque deposit and realisation
     *   ReportsAccountBalances      balances, cash flow, profit and loss
     *   ProvidesAccountLookups      dropdowns and permission helpers
     *   RunsAccountCorrections      one-off data-correction routines
     */
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;
    /*
     * MA-002: these trait references are FULLY QUALIFIED, and must stay that way.
     *
     * They were written as `use Concerns\Name;` when the split was made. PHP
     * resolves an unqualified name inside a class against the FILE's namespace,
     * so that became
     *     Modules\...\Http\Controllers\Concerns\Name
     * while the traits actually live one level deeper, under
     *     Modules\...\Http\Controllers\<Group>\Concerns\Name
     *
     * The result was a fatal on every screen this controller serves:
     *     Trait "...\Concerns\ScopesPdSettlements" not found
     *
     * A leading backslash removes the ambiguity entirely.
     */
    use \Modules\Finance\Http\Controllers\Account\Concerns\ManagesAccounts;
    use \Modules\Finance\Http\Controllers\Account\Concerns\ShowsAccountBook;
    use \Modules\Finance\Http\Controllers\Account\Concerns\HandlesAccountTransactions;
    use \Modules\Finance\Http\Controllers\Account\Concerns\HandlesDepositsAndTransfers;
    use \Modules\Finance\Http\Controllers\Account\Concerns\HandlesCheques;
    use \Modules\Finance\Http\Controllers\Account\Concerns\ReportsAccountBalances;
    use \Modules\Finance\Http\Controllers\Account\Concerns\ProvidesAccountLookups;
    use \Modules\Finance\Http\Controllers\Account\Concerns\RunsAccountCorrections;

    public function __construct(Util $commonUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {

        $this->commonUtil      = $commonUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->productUtil     = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil    = $businessUtil;
    }
}
