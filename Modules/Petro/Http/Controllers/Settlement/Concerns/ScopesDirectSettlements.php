<?php

namespace Modules\Petro\Http\Controllers\Settlement\Concerns;

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

/**
 * Business scope, PD-module exclusion, settlement numbering and the operator dropdown.
 *
 * MA-002: split out of Petro's SettlementController, which was 11,795 lines.
 *
 * The grouping was worked out FOR THIS CONTROLLER, not copied from PetroPD's.
 * The four settlement modules have genuinely diverged - 17 of the 19
 * controllers they share differ in logic - so Petro has methods PetroPD does
 * not (mechanical meter comparison, auto shift numbering, real-time payment
 * sync) and vice versa. Copying a grouping across would have produced tidy
 * files with the wrong things in them.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged: routes still point at SettlementController,
 *   action() targets still resolve, and $this-> calls between these 91 methods
 *   still work. Separate controller classes would mean rewriting routes and
 *   every action() reference - a behavioural change dressed up as tidying.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten.
 *
 * Methods here: getCurrentBusinessIdForDirectSettlement, excludePetroPdModuleSettlements, excludeSettlementsWithPetroPdSettledShifts, excludePetroPdModuleAssignments, getPetroPdModuleSettlementPrefixes, getConfiguredPetroPdPrefixIfExclusive, getDirectSettlementPrefix, getNextDirectSettlementNo, findReusableDirectDraftSettlement, canEditDirectSettlements, normalizeDirectSettlementDate, getDirectSettlementPumpOperatorDropdown, getDirectSettlementHiddenPendingPumpOperatorIds, shouldShowPendingShiftPumpOperatorsInDirectSettlement
 */
trait ScopesDirectSettlements
{
    private function getCurrentBusinessIdForDirectSettlement(): ?int
    {
        $session = request()->session();

        return (int) (
            $session->get('user.business_id')
            ?: $session->get('business.id')
            ?: session('user.business_id')
            ?: session('business.id')
        ) ?: null;
    }

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

    protected function excludePetroPdModuleAssignments($query, $business_id, string $assignmentAlias = 'pump_operator_assignments', string $settlementAlias = 'settlements')
    {
        $petroPdPrefixes = $this->getPetroPdModuleSettlementPrefixes($business_id);

        $query->where(function ($q) use ($petroPdPrefixes, $settlementAlias) {
            $q->whereNull($settlementAlias . '.id')
                ->orWhere(function ($notPdSettlement) use ($petroPdPrefixes, $settlementAlias) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $notPdSettlement->where($settlementAlias . '.settlement_no', 'NOT LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('settlements as pd_assignment_settlements')
                ->whereColumn('pd_assignment_settlements.id', $assignmentAlias . '.settlement_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_assignment_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('pump_operator_meter_sales as pd_operator_meter_sales')
                ->whereColumn('pd_operator_meter_sales.shift_id', $assignmentAlias . '.shift_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_operator_meter_sales.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('meter_sales as pd_meter_sales')
                ->join('settlements as pd_meter_settlements', 'pd_meter_settlements.id', '=', 'pd_meter_sales.settlement_no')
                ->whereColumn('pd_meter_sales.shift_id', $assignmentAlias . '.shift_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_meter_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        return $query;
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


    /**
     * Permanent module separation: Petro / Direct Settlement uses ST only.
     */

    protected function getDirectSettlementPrefix($business_id): string
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

        $prefix = $prefixes['settlement'] ?? 'ST';

        return ! empty($prefix) ? $prefix : 'ST';
    }


    /**
     * Direct Settlement number generator.
     *
     * Important rules:
     * - Direct Settlement uses only ST sequence.
     * - Petro PD settlements (PDST...) and SET-SW must never affect ST numbering.
     * - Empty pending draft rows must not push the next ST number forward.
     * - When the create page already displays a number, AJAX updates must keep that number.
     */

    protected function getNextDirectSettlementNo($business_id, ?string $preferredSettlementNo = null): string
    {
        $prefix = $this->getDirectSettlementPrefix($business_id);
        $preferredSettlementNo = trim((string) $preferredSettlementNo);

        if ($preferredSettlementNo !== '' && Str::startsWith($preferredSettlementNo, $prefix)) {
            return $preferredSettlementNo;
        }

        $usedNumbers = Settlement::where('business_id', $business_id)
            ->where('settlement_no', 'LIKE', $prefix . '%')
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%')
            ->where(function ($query) {
                $query->where('status', 0)
                    ->orWhere('total_amount', '>', 0);

                foreach ([
                    'meter_sales',
                    'other_sales',
                    'other_incomes',
                    'customer_payments',
                    'settlement_cash_payments',
                    'settlement_card_payments',
                    'settlement_cheque_payments',
                    'settlement_cash_deposits',
                    'settlement_expense_payments',
                    'settlement_shortage_payments',
                    'settlement_excess_payments',
                    'settlement_loan_payments',
                    'settlement_drawing_payments',
                    'settlement_customer_loans',
                ] as $detailTable) {
                    $query->orWhereExists(function ($sub) use ($detailTable) {
                        $sub->select(DB::raw(1))
                            ->from($detailTable)
                            ->where(function ($detailQuery) use ($detailTable) {
                                $detailQuery->whereColumn($detailTable . '.settlement_no', 'settlements.id')
                                    ->orWhereColumn($detailTable . '.settlement_no', 'settlements.settlement_no');
                            });
                    });
                }
            })
            ->pluck('settlement_no')
            ->map(function ($settlementNo) {
                return $this->extractLastInteger($settlementNo);
            })
            ->max() ?? 0;

        return $prefix . ((int) $usedNumbers + 1);
    }

    protected function findReusableDirectDraftSettlement($business_id, ?string $settlementNo = null, ?int $locationId = null)
    {
        $query = Settlement::where('business_id', $business_id)
            ->where('status', 1)
            ->where('settlement_no', 'LIKE', $this->getDirectSettlementPrefix($business_id) . '%')
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%');

        if (! empty($settlementNo)) {
            $query->where('settlement_no', $settlementNo);
        }

        if (! empty($locationId)) {
            $query->where('location_id', $locationId);
        }

        return $query->orderBy('id')->first();
    }

    /**
     * Constructor

     *

     * @param  ProductUtils  $product
     * @return void
     */

    private function canEditDirectSettlements(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('petro.settlement.edit')
            || $user->can('settlement.edit');
    }

    /**
     * Return every valid active non-fuel product for Other Sales.
     *
     * Older tenant products may not carry the optional petro_settlements module
     * tag, so relying only on the tagged dropdown can leave Select Items empty.
     *
     * @return array<int, string>
     */

    private function normalizeDirectSettlementDate($value, $fallback = null): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            $value = trim((string) $fallback);
        }

        if ($value === '') {
            return now()->format('Y-m-d');
        }

        foreach (['Y-m-d', 'm/d/Y', 'd/m/Y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $exception) {
                // Try the next supported format.
            }
        }

        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Display a listing of the resource.







     * @return Response
     */

    private function getDirectSettlementPumpOperatorDropdown(?int $business_id = null)
    {
        $business_id = $business_id ?: $this->getCurrentBusinessIdForDirectSettlement();

        $query = DB::table('pump_operators')
            ->select('id', 'name')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name', 'asc');

        if (! empty($business_id) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operators', 'business_id')) {
            $query->where('business_id', $business_id);
        }
        \App\Utils\PetroPdIsolationUtil::excludeOperators($query, 'is_petro_pd_only');

        // Do not use the PumpOperator model here because its global active scope
        // can hide valid operators from Direct Settlement. Only exclude deleted
        // records if the table supports soft deletes.
        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operators', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $operators = $query->pluck('name', 'id');

        // Tenant databases should only contain the current business data. If an
        // older tenant session is missing business_id, still show the operators
        // instead of leaving the dropdown empty.
        if ($operators->isEmpty() && ! empty($business_id) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operators', 'business_id')) {
            $operators = DB::table('pump_operators')
                ->select('id', 'name')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->when(\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operators', 'deleted_at'), function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->when(\App\Utils\PetroPdIsolationUtil::supportsOperators(), function ($q) {
                    \App\Utils\PetroPdIsolationUtil::excludeOperators($q, 'is_petro_pd_only');
                })
                ->orderBy('name', 'asc')
                ->pluck('name', 'id');
        }

        return $operators;
    }

    private const SHOW_MECHANICAL_METER_SETTING = 'mech_mtr';
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $notificationUtil;

    private $barcode_types;

    private function getDirectSettlementHiddenPendingPumpOperatorIds($business_id): array
    {
        if ($this->shouldShowPendingShiftPumpOperatorsInDirectSettlement($business_id)) {
            return [];
        }

        if (! \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
            return [];
        }

        $configured_operator_ids = PumpOperator::where('business_id', $business_id)
            ->where('hide_in_direct_settlement_if_pending_shifts', 1)
            ->pluck('id')
            ->toArray();

        if (empty($configured_operator_ids)) {
            return [];
        }

        return PumpOperatorAssignment::where('business_id', $business_id)
            ->whereIn('pump_operator_id', $configured_operator_ids)
            ->whereNotNull('pump_operator_id')
            ->where(function ($query) {
                $query->whereNull('settlement_id')
                    ->orWhere('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement')
                    ->orWhere('status', '!=', 'close')
                    ->orWhereNull('close_date_and_time')
                    ->orWhereHas('Shift', function ($shiftQuery) {
                        $shiftQuery->whereNull('closed_time')
                            ->orWhere('status', '!=', 2);
                    });
            })
            ->pluck('pump_operator_id')
            ->unique()
            ->values()
            ->toArray();
    }

    private function shouldShowPendingShiftPumpOperatorsInDirectSettlement($business_id): bool
    {
        $subscription = Subscription::active_subscription($business_id);
        $package_details = ! empty($subscription) ? $subscription->package_details : [];

        return ! empty($package_details['show_pump_operators_when_shifts_pending_in_settlement']);
    }
}
