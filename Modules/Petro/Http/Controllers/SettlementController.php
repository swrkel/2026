<?php

namespace Modules\Petro\Http\Controllers;

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
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\CustomerBillVatPrefix;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayEnd;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PetroWhatsAppTemplate;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorCommission;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

class SettlementController extends Controller
{

    /*
     * MA-002: this controller was 11,795 lines. Its work now lives in the
     * traits below, grouped by what each does. Same class at runtime - routes,
     * action() targets and $this-> calls unchanged.
     *
     *     ScopesDirectSettlements   scope, PD exclusion, numbering, dropdown
     *     ListsSettlements          index, show, print
     *     CreatesSettlements        create, store
     *     EditsSettlements          edit, update, destroy
     *     PostsSettlementLedger     transactions, account entries, ledger
     *     HandlesMeterSales         meter sales incl. mechanical meter
     *     HandlesOtherEntries       other sales, income, customer payments
     *     ManagesShifts             shift numbering and auto-increment
     *     ProvidesLookups           dropdown and lookup endpoints
     */
    use UpdatesSettlementTransactions;

    /**
     * IS1509 - Direct Settlement Operator dropdown.
     *
     * Direct Settlement is separate from PetroPD/PD Operator code. It must load
     * operators directly from the shared pump_operators table for the current
     * tenant/business and must not apply pending-shift or PD assignment filters.
     */

    /*
     * MA-002 (LA-1140): these were relative - "use Concerns\X;".
     *
     * This controller is in Modules\Petro\Http\Controllers, so a relative
     * import resolved to
     *     Modules\Petro\Http\Controllers\Concerns\X
     * while the trait files actually live in
     *     Modules\Petro\Http\Controllers\Settlement\Concerns\X
     *
     * PHP only resolves a trait when the class is first loaded, so the file
     * looked fine and the error appeared at runtime - "Trait not found" the
     * moment anything touched a settlement.
     *
     * The same fault was fixed on the other split controllers earlier; this
     * one was missed. All nine are now fully qualified, which cannot be
     * misresolved by where the class happens to sit.
     */
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\ScopesDirectSettlements;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\ListsSettlements;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\CreatesSettlements;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\EditsSettlements;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\PostsSettlementLedger;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\HandlesMeterSales;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\HandlesOtherEntries;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\ManagesShifts;
    use \Modules\Petro\Http\Controllers\Settlement\Concerns\ProvidesLookups;

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
}
