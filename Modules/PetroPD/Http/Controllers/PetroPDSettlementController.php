<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PetroWhatsAppTemplate;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCustomerLoan;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementEditHistory;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\TankSellLine;
use Modules\PetroPD\Entities\TanksTransactionDetail;
use Modules\PetroPD\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\PetroPD\Services\PetroPdSmsNotificationService;

class PetroPDSettlementController extends Controller
{

    /*
     * MA-002: this controller was 15,639 lines. Its work now lives in the
     * traits below, grouped by what each does. Same class at runtime - routes,
     * action() targets and $this-> calls are all unchanged.
     *
     *     ScopesPdSettlements       permissions, business scope, PD prefixes
     *     ListsPdSettlements        index, show, print
     *     CreatesPdSettlements      create, store
     *     EditsPdSettlements        edit, update, destroy
     *     PostsPdSettlementLedger   transactions, account entries, ledger
     *     HandlesPdMeterSales       meter sale entry, display, totals
     *     HandlesPdOtherEntries     other sales, income, customer payments
     *     ReconcilesPdCash          cash match vs pumper dashboard, shortage
     *     ProvidesPdLookups         dropdown and lookup endpoints
     */
    use UpdatesSettlementTransactions;

    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $notificationUtil;

    private $barcode_types;

    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */
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
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\ScopesPdSettlements;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\ListsPdSettlements;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\CreatesPdSettlements;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\EditsPdSettlements;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\PostsPdSettlementLedger;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\HandlesPdMeterSales;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\HandlesPdOtherEntries;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\ReconcilesPdCash;
    use \Modules\PetroPD\Http\Controllers\Settlement\Concerns\ProvidesPdLookups;

    public function __construct(

        Util $commonUtil,

        ProductUtil $productUtil,

        ModuleUtil $moduleUtil,

        TransactionUtil $transactionUtil,

        BusinessUtil $businessUtil,

        NotificationUtil $notificationUtil

    ) {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;
    }


    /**
     * Send PetroPD settlement SMS after the settlement transaction has been committed.
     * Any SMS provider/template issue is logged only and must not roll back settlement save.
     */
}
