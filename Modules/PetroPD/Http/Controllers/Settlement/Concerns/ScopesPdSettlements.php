<?php

namespace Modules\PetroPD\Http\Controllers\Settlement\Concerns;

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

/**
 * Permissions, business scoping and the PD settlement-number prefix rules.
 *
 * MA-002: split out of PetroPDSettlementController, which was 15,639 lines in
 * a single file - the largest controller in the application after core's
 * ReportController.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   PetroPDSettlementController, action() targets still resolve, and $this->
 *   calls between these 111 methods still work. Splitting into separate
 *   controller classes would mean rewriting routes and every action()
 *   reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: assertPetroPdSettlementRolePermission, canPetroPdSettlementEntryAction, assertPetroPdSettlementEntryAction, resolvePetroPdBusinessId, applyPdSettlementScope, excludePetroPdModuleSettlements, excludeSettlementsWithPetroPdSettledShifts, getPdSettlementPrefixes, getPetroPdModuleSettlementPrefixes, getConfiguredPetroPdPrefixIfExclusive, isPetroPdModuleRequest, applyPetroPdModuleSettlementScope, isPetroPdModuleSettlementNo, getPrimaryPetroPdModuleSettlementPrefix, notifyPetroPdSettlementSaved, ensureBusinessSessionDefaults
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

    /**
     * Authorize row-level Meter Sales / Other Sales actions in the Petro PD settlement form.
     * Meter-sale changes remain additionally protected by the existing Manual Entry permission.
     */

    protected function canPetroPdSettlementEntryAction(string $action, bool $requiresManualEntry = false): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('superadmin')) {
            return true;
        }

        $permission = $action === 'delete'
            ? 'petro_pd.delete_settlement'
            : 'petro_pd.edit_settlement';

        if (! $user->can($permission)) {
            return false;
        }

        return ! $requiresManualEntry || $user->can('petro_pd.manual_entry');
    }

    protected function assertPetroPdSettlementEntryAction(string $action, bool $requiresManualEntry = false): void
    {
        if (! $this->canPetroPdSettlementEntryAction($action, $requiresManualEntry)) {
            abort(403, 'Unauthorized Access');
        }
    }

    protected function resolvePetroPdBusinessId(Request $request): int
    {
        return (int) (
            $request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional($request->user())->business_id
        );
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
        // This controller belongs to the Petro PD module. All settlements created here are PDST.
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

    protected function notifyPetroPdSettlementSaved(Settlement $settlement): void
    {
        try {
            app(PetroPdSmsNotificationService::class)->sendPdSettlementSaved($settlement);
        } catch (\Throwable $e) {
            Log::warning('PetroPD settlement SMS notification failed', [
                'settlement_id' => $settlement->id ?? null,
                'settlement_no' => $settlement->settlement_no ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * PD settlements (settlement_no PDST…) require explicit Petro PD role permissions for edit or delete.
     */

    private function ensureBusinessSessionDefaults(Request $request, $business_id = null)
    {
        $session_business = (array) $request->session()->get('business', []);

        if (empty($session_business['id']) && ! empty($business_id)) {
            $session_business['id'] = $business_id;
        }

        $defaults = [
            'enable_product_expiry' => 0,
            'on_product_expiry'     => 'keep_selling',
            'stop_selling_before'   => 0,
        ];

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $session_business) || $session_business[$key] === null) {
                $session_business[$key] = $value;
            }
        }

        $request->session()->put('business', $session_business);
    }

    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    // change and set by @zeeshan ali
}
