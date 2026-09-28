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
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;

class SettlementPDController extends Controller
{

    /*
     * MA-002: this controller was 13,540 lines. Its work now lives in the
     * traits below, grouped by what each does. Same class at runtime - routes,
     * action() targets and $this-> calls are unchanged.
     */

    /**
     * MA-002 PERF: request-scoped cache of pump id -> pump_no.
     *
     * The "pump_nos" column runs once per row and ends with
     *     Pump::whereIn("id", $_pump_ids)->pluck("pump_no")
     * so every row issues its own query, even though a station has only a
     * handful of pumps and the same ids recur down the page.
     *
     * meter_sales_pd.details is already eager loaded on the base query, so
     * this was the remaining per-row query. Pump numbers are reference data
     * and cannot change while the table renders. Misses are cached too, so a
     * deleted pump is not re-queried on every row.
     *
     * @param  array<int, mixed>  $pumpIds
     * @return array<int, string>
     */
    private static array $ma002PumpNoCache = [];

    private static function ma002PumpNos(array $pumpIds): array
    {
        $pumpIds = array_values(array_unique(array_filter($pumpIds)));
        if ($pumpIds === []) {
            return [];
        }

        $unknown = array_values(array_diff($pumpIds, array_keys(self::$ma002PumpNoCache)));
        if ($unknown !== []) {
            foreach (Pump::whereIn('id', $unknown)->pluck('pump_no', 'id')->toArray() as $id => $no) {
                self::$ma002PumpNoCache[$id] = $no;
            }
            foreach ($unknown as $id) {
                if (! array_key_exists($id, self::$ma002PumpNoCache)) {
                    self::$ma002PumpNoCache[$id] = null;
                }
            }
        }

        $out = [];
        foreach ($pumpIds as $id) {
            $no = self::$ma002PumpNoCache[$id] ?? null;
            if ($no !== null && $no !== '') {
                $out[] = $no;
            }
        }

        return $out;
    }

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
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\ScopesPdSettlements;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\ListsPdSettlements;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\CreatesPdSettlements;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\EditsPdSettlements;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\PostsPdSettlementLedger;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\HandlesPdMeterSales;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\HandlesPdOtherEntries;
    use \Modules\Petro\Http\Controllers\SettlementPD\Concerns\ProvidesPdLookups;

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
     * PD settlements (settlement_no PDST…) require explicit Petro PD role permissions for edit or delete.
     */
}
