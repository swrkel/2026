<?php

namespace Modules\Petro\Http\Controllers\SettlementPD\Concerns;

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

/**
 * Permissions, business scoping and the PD settlement-number prefix rules.
 *
 * MA-002: split out of SettlementPDController, which was 13,540 lines in a
 * single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   SettlementPDController, action() targets still resolve, and the $this->
 *   calls between these 85 methods still work. Separate controller classes
 *   would mean rewriting routes and every action() reference - a behavioural
 *   change dressed up as tidying, and with 85 methods the odds of missing one
 *   are high.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: assertPetroPdSettlementRolePermission, applyPdSettlementScope, excludePetroPdModuleSettlements, excludeSettlementsWithPetroPdSettledShifts, getPdSettlementPrefixes, getPetroPdModuleSettlementPrefixes, getConfiguredPetroPdPrefixIfExclusive, isPetroPdModuleRequest, applyPetroPdModuleSettlementScope, resolvePetroPdSettlementLocationId, isPetroPdModuleSettlementNo, getPrimaryPetroPdModuleSettlementPrefix
 */
trait ScopesPdSettlements
{
    protected function assertPetroPdSettlementRolePermission(Settlement $settlement, string $action): void
    {
        if (auth()->user() && auth()->user()->can('superadmin')) {
            return;
        }

        $permission = $action === 'delete'
            ? 'petro_pd.delete_settlement'
            : 'petro_pd.edit_settlement';

        if (! auth()->user() || ! auth()->user()->can($permission)) {
            abort(403, 'Unauthorized Access');
        }
    }

    protected function applyPdSettlementScope($query, $business_id)
    {
        // Permanent module separation: Petro PD list must show ONLY PDST settlements.
        return $this->applyPetroPdModuleSettlementScope($query, $business_id);
    }

    protected function excludePetroPdModuleSettlements($query, $business_id, string $column = "settlements.settlement_no")
    {
        foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
            $query->where($column, "NOT LIKE", $prefix . "%");
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
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('petro_pd_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });
    }

    protected function getPdSettlementPrefixes($business_id): array
    {
        // Petro PD uses its own sequence: PDST1, PDST2, PDST3...
        return $this->getPetroPdModuleSettlementPrefixes($business_id);
    }

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

    protected function isPetroPdModuleRequest(Request $request): bool
    {
        // IMPORTANT: This controller is the PD Settlement controller used by /petro/settlement-pd/*.
        // Even though the URL starts with /petro, these actions belong to Petro PD.
        // Therefore every draft/finalized settlement created here must use PDST, never ST.
        return true;
    }

    protected function applyPetroPdModuleSettlementScope($query, $business_id, string $column = 'settlements.settlement_no')
    {
        return $query->where(function ($query) use ($business_id, $column) {
            foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
                $query->orWhere($column, 'LIKE', $prefix . '%');
            }
        });
    }

    /**
     * Resolve a real business location for a Petro PD settlement.
     *
     * Some legacy/draft requests reached createSettlementIfNotExist before the
     * location control had a value and stored location_id = 0. Once the global
     * location filter auto-selects a location, those otherwise valid PD
     * settlements disappear from the list. Prefer the submitted location, then
     * the selected operator's location, and finally the business's first
     * location. Every candidate is constrained to the current business.
     */

    protected function resolvePetroPdSettlementLocationId(int $businessId, $requestedLocationId = null, $pumpOperatorId = null): ?int
    {
        $requestedLocationId = (int) $requestedLocationId;

        if (
            $requestedLocationId > 0
            && BusinessLocation::where('business_id', $businessId)
                ->where('id', $requestedLocationId)
                ->exists()
        ) {
            return $requestedLocationId;
        }

        $pumpOperatorId = (int) $pumpOperatorId;
        if ($pumpOperatorId > 0) {
            $operatorLocationId = (int) PumpOperator::where('business_id', $businessId)
                ->where('id', $pumpOperatorId)
                ->value('location_id');

            if (
                $operatorLocationId > 0
                && BusinessLocation::where('business_id', $businessId)
                    ->where('id', $operatorLocationId)
                    ->exists()
            ) {
                return $operatorLocationId;
            }
        }

        $fallbackLocationId = BusinessLocation::where('business_id', $businessId)
            ->orderBy('id')
            ->value('id');

        return $fallbackLocationId ? (int) $fallbackLocationId : null;
    }

    protected function isPetroPdModuleSettlementNo(?string $settlementNo, $business_id): bool
    {
        $settlementNo = (string) $settlementNo;

        foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
            if (Str::startsWith($settlementNo, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function getPrimaryPetroPdModuleSettlementPrefix($business_id): string
    {
        return $this->getPetroPdModuleSettlementPrefixes($business_id)[0] ?? 'PDST';
    }
}
