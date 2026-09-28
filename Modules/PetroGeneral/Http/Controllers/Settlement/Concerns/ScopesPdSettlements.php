<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroGeneral\Entities\CustomerBillVatPrefix;
use Modules\PetroGeneral\Entities\DailyCard;
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
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementEditHistory;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroGeneral\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Permissions, business scoping and the PD settlement-number prefix rules.
 *
 * MA-002: split out of PetroGeneral's SettlementController, which was 11,479
 * lines in a single file.
 *
 * The grouping follows the one used for the PD settlement controllers, since
 * these files share most of their method names - but it was rebuilt against
 * THIS file, because the modules have genuinely diverged in content.
 *
 * Traits, not separate controllers: routes, action() targets and the $this->
 * calls between these methods all resolve exactly as before. Method bodies
 * are byte-identical to the original.
 *
 * Methods here: excludePetroPdModuleSettlements, excludeSettlementsWithPetroPdSettledShifts, getPetroPdModuleSettlementPrefixes, getConfiguredPetroPdPrefixIfExclusive
 */
trait ScopesPdSettlements
{
    protected function excludePetroPdModuleSettlements($query, $business_id, string $column = 'settlements.settlement_no')
    {
        foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
            $query->where($column, 'NOT LIKE', $prefix . '%');
        }

        return $query;
    }

    protected function excludeSettlementsWithPetroPdSettledShifts($query, $business_id)
    {
        $petroPdPrefixes = $this->getPetroPdModuleSettlementPrefixes($business_id);

        $query->whereNotExists(function ($subQuery) use ($petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('meter_sales as leaked_meter_sales')
                ->join('pump_operator_assignments as leaked_assignments', function ($join) {
                    $join->on('leaked_assignments.shift_id', '=', 'leaked_meter_sales.shift_id')
                        ->whereNotNull('leaked_assignments.settlement_id');
                })
                ->join('settlements as petro_pd_settlements', 'petro_pd_settlements.id', '=', 'leaked_assignments.settlement_id')
                ->whereColumn('leaked_meter_sales.settlement_no', 'settlements.id')
                ->where('petro_pd_settlements.status', 0)
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('petro_pd_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        return $query->whereNotExists(function ($subQuery) use ($petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('pump_operator_meter_sales as leaked_operator_meter_sales')
                ->join('pump_operator_assignments as leaked_assignments', function ($join) {
                    $join->on('leaked_assignments.shift_id', '=', 'leaked_operator_meter_sales.shift_id')
                        ->whereNotNull('leaked_assignments.settlement_id');
                })
                ->join('settlements as petro_pd_settlements', 'petro_pd_settlements.id', '=', 'leaked_assignments.settlement_id')
                ->where(function ($linkedQuery) {
                    $linkedQuery
                        ->whereColumn('leaked_operator_meter_sales.settlement_no', 'settlements.settlement_no')
                        ->orWhereColumn('leaked_operator_meter_sales.settlement_no', 'settlements.id');
                })
                ->where('petro_pd_settlements.status', 0)
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('petro_pd_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });
    }


    /**
     * Direct Settlement must never use shifts that already belong to PetroPD.
     * PetroPD settlements use PDST... numbers, but still share the legacy
     * pump_operator_assignments / petro_shifts tables until full separation.
     */

    protected function getPetroPdModuleSettlementPrefixes($business_id): array
    {
        $prefixes = request()->session()->get('business.ref_no_prefixes', []);

        if (empty($prefixes) && ! empty($business_id)) {
            $business = Business::find($business_id);
            $prefixes = $business->ref_no_prefixes ?? [];
        }

        if (is_string($prefixes)) {
            $decoded = json_decode($prefixes, true);
            $prefixes = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_unique(array_filter([
            'PDST',
            $this->getConfiguredPetroPdPrefixIfExclusive($prefixes),
        ])));
    }

    protected function getConfiguredPetroPdPrefixIfExclusive(array $prefixes): ?string
    {
        $pdPrefix = $prefixes['settlement_pd'] ?? null;
        $petroPrefix = $prefixes['settlement'] ?? 'ST';

        if (empty($pdPrefix) || $pdPrefix === $petroPrefix) {
            return null;
        }

        return $pdPrefix;
    }


    /**
     * Permanent module separation: Petro / Direct Settlement uses ST only.
     */
}
