<?php

namespace Modules\PetroGeneral\Http\Controllers;

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
use Modules\PetroGeneral\Entities\CustomerPayment;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DayEnd;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\OtherIncome;
use Modules\PetroGeneral\Entities\OtherSale;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\PetroWhatsAppTemplate;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumperDayEntry;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorCommission;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementEditHistory;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\PetroGeneral\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

use Modules\PetroGeneral\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroGeneralPD\Services\PetroPdClosedShiftQuery;

class SettlementPDController extends Controller
{

    /*
     * MA-002: this controller was 13,430 lines. Its work now lives in the
     * traits below, grouped by what each does. Same class at runtime.
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
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\ScopesPdSettlements;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\ListsPdSettlements;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\CreatesPdSettlements;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\EditsPdSettlements;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\PostsPdSettlementLedger;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\HandlesPdMeterSales;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\HandlesPdOtherEntries;
    use \Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns\ProvidesPdLookups;

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
