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
 * Methods specific to PetroGeneral that the shared grouping does not cover.
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
 * Methods here: shouldShowMechanicalMeterToo, getMechanicalMeterDifferenceSummary, excludePetroPdModuleAssignments, getDirectSettlementPrefix, getNextDirectSettlementNo, findReusableDirectDraftSettlement, getDirectSettlementHiddenPendingPumpOperatorIds, shouldShowPendingShiftPumpOperatorsInDirectSettlement, mechanicalMeter, getPumpOperatorOtherSaleTotalForSettlement, getPrintPumpOperatorOtherSales, extractShiftIdsFromSettlement, normalizeDirectSettlementShiftLabel, getNextAutoShiftNumber, createAutoIncrementedShiftForSettlement, resolveAutoShiftLocationId, incrementShiftNumber, storeManualShiftNumber, getPumpsByLocation, extractRequestedSettlementShiftIds, draftSettlementMatchesRequestedShifts, checkPreviousPumpSettlement, laterPumpSettlementExists, getMeterSaleTableHtml, attachUnsettledRteMeterSalesToSettlement, syncRealTimePaymentsToSettlement, updateSettlementTotalAmount
 */
trait HandlesModuleSpecifics
{
    protected function shouldShowMechanicalMeterToo($business_id): bool
    {
        /*
         * ZIP 050:
         * Superadmin / All Business / Manage / Petro Module / Show Mechanical Meter
         * controls whether the mechanical meter feature and its conditions apply.
         *
         * Default must be enabled for old businesses where the key is missing.
         */
        $subscription = Subscription::where('business_id', $business_id)
            ->orderBy('end_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $package_details = [];
        if (! empty($subscription) && ! empty($subscription->package_details)) {
            $package_details = is_array($subscription->package_details)
                ? $subscription->package_details
                : (json_decode($subscription->package_details, true) ?: []);
        }

        if (array_key_exists('show_mechanical_meter', $package_details) && empty($package_details['show_mechanical_meter'])) {
            return false;
        }

        $business_ids = array_values(array_unique(array_filter([
            $business_id,
            request()->session()->get('business.id'),
            request()->session()->get('user.business_id'),
        ])));

        $latest_setting = CustomerBillVatPrefix::whereIn('business_id', $business_ids)
            ->where('prefix', self::SHOW_MECHANICAL_METER_SETTING)
            ->latest('id')
            ->first();

        return empty($latest_setting) || (int) $latest_setting->starting_no === 1;
    }

    protected function getMechanicalMeterDifferenceSummary(Settlement $settlement): string
    {
        if (! $this->shouldShowMechanicalMeterToo($settlement->business_id)) {
            return '';
        }

        if (! Schema::hasColumn('meter_sales', 'mechanical_meter_difference')) {
            return '';
        }

        return MeterSale::leftJoin('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->where('meter_sales.settlement_no', $settlement->id)
            ->whereNotNull('meter_sales.mechanical_meter_difference')
            ->select('meter_sales.mechanical_meter_difference', 'pumps.pump_name', 'pumps.pump_no')
            ->get()
            ->map(function ($meter_sale) {
                $pump_name = $meter_sale->pump_name ?: $meter_sale->pump_no;

                return trim(($pump_name ?: 'Pump') . ': ' . number_format((float) $meter_sale->mechanical_meter_difference, 3, '.', ''));
            })
            ->filter()
            ->implode(', ');
    }

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

    private function getDirectSettlementHiddenPendingPumpOperatorIds($business_id): array
    {
        if ($this->shouldShowPendingShiftPumpOperatorsInDirectSettlement($business_id)) {
            return [];
        }

        if (! Schema::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
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

    public function mechanicalMeter($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $settlement = Settlement::where('business_id', $business_id)
            ->with(['meter_sales.pump'])
            ->findOrFail($id);

        $meter_sales = $settlement->meter_sales;

        return view('petrogeneral::settlement.mechanical_meter_modal')
            ->with(compact('settlement', 'meter_sales'));
    }

    /**
     * Display a listing of the resource.







     * @return Response
     */

    private function getPumpOperatorOtherSaleTotalForSettlement($settlement)
    {
        $shiftIds = $this->extractShiftIdsFromSettlement($settlement);

        if (empty($shiftIds) && ! empty($settlement->id)) {
            $shiftIds = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($shiftIds)) {
            return 0;
        }

        return PumpOperatorOtherSale::leftJoin('pump_operator_assignments', function ($join) {
            $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
                ->whereRaw(
                    'pump_operator_assignments.id = (
                        SELECT MAX(poa.id)
                        FROM pump_operator_assignments poa
                        WHERE poa.shift_id = pump_operator_other_sales.shift_id
                    )'
                );
        })
            ->whereIn('pump_operator_other_sales.shift_id', $shiftIds)
            ->sum('pump_operator_other_sales.sub_total');
    }

    private function getPrintPumpOperatorOtherSales($settlement, int $business_id, array $shift_ids = [])
    {
        if (empty($settlement)) {
            return collect();
        }

        $shift_ids = array_values(array_unique(array_filter(array_map('intval', $shift_ids))));

        $work_shift_ids = array_values(array_unique(array_filter(array_map('intval', $this->extractShiftIdsFromSettlement($settlement)))));

        $linked_shift_ids = MeterSale::where('business_id', $business_id)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn ($shift_id) => (int) $shift_id)
            ->toArray();

        $assignment_shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('settlement_id', $settlement->id)
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn ($shift_id) => (int) $shift_id)
            ->toArray();

        $shift_ids = array_values(array_unique(array_filter(array_merge(
            $shift_ids,
            $work_shift_ids,
            $linked_shift_ids,
            $assignment_shift_ids
        ))));

        $query = PumpOperatorOtherSale::where('business_id', $business_id);

        if (! empty($shift_ids)) {
            return $query->whereIn('shift_id', $shift_ids)->get();
        }

        if (Schema::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            return $query->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })->get();
        }

        return collect();
    }

    private function extractShiftIdsFromSettlement($settlement)
    {
        $workShift = $settlement->work_shift;

        if (empty($workShift)) {
            return [];
        }

        if (is_array($workShift)) {
            return array_values(array_filter($workShift));
        }

        $decoded = json_decode($workShift, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded));
        }

        return [];
    }

    private function normalizeDirectSettlementShiftLabel($value, ?int $business_id = null): ?string
    {
        $prefix = $this->getDirectSettlementShiftPrefix($business_id);

        if (is_array($value)) {
            foreach ($value as $item) {
                $label = $this->normalizeDirectSettlementShiftLabel($item, $business_id);
                if (! empty($label)) {
                    return $label;
                }
            }

            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        for ($i = 0; $i < 3; $i++) {
            if (preg_match('/' . preg_quote($prefix, '/') . '\s*(\d+)/i', $text, $matches)) {
                return $prefix . $matches[1];
            }

            $decoded = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === $text) {
                break;
            }

            if (is_array($decoded)) {
                return $this->normalizeDirectSettlementShiftLabel($decoded, $business_id);
            }

            $text = trim((string) $decoded);
        }

        return null;
    }

    private function getNextAutoShiftNumber($business_id, $location_id = null): string
    {
        $query = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id);

        if (! empty($location_id)) {
            $query->leftJoin('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
                ->where('pumps.location_id', $location_id);
        }

        $last_shift_number = $query
            ->whereNotNull('pump_operator_assignments.shift_number')
            ->where('pump_operator_assignments.shift_number', '!=', '')
            ->orderBy('pump_operator_assignments.id', 'desc')
            ->value('pump_operator_assignments.shift_number');

        if (empty($last_shift_number)) {
            return '1';
        }

        return $this->incrementShiftNumber((string) $last_shift_number);
    }

    private function createAutoIncrementedShiftForSettlement(Request $request): array
    {
        $business_id = $request->session()->get('business.id');
        $pump_operator_id = (int) $request->input('pump_operator_id');
        $location_id = (int) $request->input('location_id');

        if (empty($business_id) || empty($pump_operator_id)) {
            throw new \RuntimeException('Please select business location and pump operator.');
        }

        if (empty($location_id)) {
            $location_id = $this->resolveAutoShiftLocationId($business_id, $pump_operator_id);
        }

        if (empty($location_id)) {
            throw new \RuntimeException('Unable to identify a business location for the selected pump operator.');
        }

        return DB::transaction(function () use ($request, $business_id, $pump_operator_id, $location_id) {
            $shift_number = $this->getNextAutoShiftNumber($business_id, $location_id);
            $attempts = 0;

            while (
                $attempts < 100 &&
                PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('shift_number', $shift_number)
                    ->exists()
            ) {
                $shift_number = $this->incrementShiftNumber($shift_number);
                $attempts++;
            }

            if ($attempts >= 100) {
                throw new \RuntimeException('Unable to generate the next shift number.');
            }

            $work_shift = $request->input('work_shift');
            if (is_array($work_shift)) {
                $work_shift = reset($work_shift);
            }

            $shift_date = $request->input('transaction_date')
                ? date('Y-m-d', strtotime($request->input('transaction_date')))
                : date('Y-m-d');

            $petro_shift = PetroShift::create([
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'status' => 0,
                'shift_date' => $shift_date,
                'work_shift_id' => ! empty($work_shift) ? $work_shift : null,
            ]);

            $pumps_query = Pump::where('business_id', $business_id)
                ->where('location_id', $location_id);

            if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                $pumps_query->where('is_other_sales_pump', 0);
            }

            $pumps = $pumps_query->get(['id', 'last_meter_reading', 'pod_last_meter']);

            if ($pumps->isEmpty()) {
                throw new \RuntimeException('No pumps found for the selected business location.');
            }

            foreach ($pumps as $pump) {
                $starting_meter = ! empty($pump->pod_last_meter)
                    ? max((float) $pump->pod_last_meter, (float) $pump->last_meter_reading)
                    : (float) $pump->last_meter_reading;

                PumpOperatorAssignment::create([
                    'business_id' => $business_id,
                    'pump_id' => $pump->id,
                    'pump_operator_id' => $pump_operator_id,
                    'starting_meter' => $starting_meter,
                    'date_and_time' => $shift_date,
                    'status' => 'open',
                    'assigned_by' => auth()->id(),
                    'shift_id' => $petro_shift->id,
                    'shift_number' => $shift_number,
                ]);
            }

            return [
                'shift_id' => $petro_shift->id,
                'shift_number' => $shift_number,
            ];
        });
    }

    private function resolveAutoShiftLocationId($business_id, $pump_operator_id): ?int
    {
        $pump_operator_location_id = PumpOperator::where('business_id', $business_id)
            ->where('id', $pump_operator_id)
            ->value('location_id');

        if (! empty($pump_operator_location_id)) {
            return (int) $pump_operator_location_id;
        }

        $assigned_pump_location_id = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->leftJoin('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
            ->whereNotNull('pumps.location_id')
            ->orderBy('pump_operator_assignments.id', 'desc')
            ->value('pumps.location_id');

        if (! empty($assigned_pump_location_id)) {
            return (int) $assigned_pump_location_id;
        }

        $pump_location_id = Pump::where('business_id', $business_id)
            ->whereNotNull('location_id')
            ->orderBy('id')
            ->value('location_id');

        if (! empty($pump_location_id)) {
            return (int) $pump_location_id;
        }

        $business_location_id = BusinessLocation::where('business_id', $business_id)
            ->orderBy('id')
            ->value('id');

        return ! empty($business_location_id) ? (int) $business_location_id : null;
    }

    private function incrementShiftNumber(string $shift_number): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $shift_number, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            $next_number = (string) (((int) $number) + 1);

            if (strlen($number) > 1 && substr($number, 0, 1) === '0') {
                $next_number = str_pad($next_number, strlen($number), '0', STR_PAD_LEFT);
            }

            return $prefix.$next_number;
        }

        return $shift_number.'1';
    }

    /**
     * Remove the specified resource from storage.

     *
     * @return Response
     */

    public function storeManualShiftNumber(Request $request)
    {
        $business_id = $request->session()->get('business.id');

        $shift_number = strtoupper(trim((string) $request->input('shift_number')));
        $pump_operator_id = (int) $request->input('pump_operator_id');
        $location_id = (int) $request->input('location_id');

        if (empty($business_id) || empty($pump_operator_id) || empty($location_id)) {
            return response()->json([
                'success' => false,
                'msg' => __('Please select business location and pump operator before entering a manual shift number.'),
            ], 422);
        }

        if ($shift_number === '') {
            return response()->json([
                'success' => false,
                'msg' => __('Please enter a manual shift number.'),
            ], 422);
        }

        if (! preg_match('/^[A-Z0-9_-]+$/', $shift_number)) {
            return response()->json([
                'success' => false,
                'msg' => __('Manual shift number can contain only letters, numbers, dash, and underscore.'),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $business_id, $shift_number, $pump_operator_id, $location_id) {
                $existing_assignment = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', $shift_number)
                    ->where(function ($query) {
                        $query->whereNull('settlement_id')
                            ->orWhere('closed_in_settlement', 0)
                            ->orWhereNull('closed_in_settlement');
                    })
                    ->orderBy('id', 'desc')
                    ->first();

                if (! empty($existing_assignment)) {
                    return [
                        'shift_id' => $existing_assignment->shift_id,
                        'shift_number' => $existing_assignment->shift_number,
                        'created' => false,
                    ];
                }

                $work_shift = $request->input('work_shift');
                if (is_array($work_shift)) {
                    $work_shift = reset($work_shift);
                }

                $shift_date = $request->input('transaction_date')
                    ? date('Y-m-d', strtotime($request->input('transaction_date')))
                    : date('Y-m-d');

                $petro_shift = PetroShift::create([
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'status' => 0,
                    'shift_date' => $shift_date,
                    'work_shift_id' => ! empty($work_shift) ? $work_shift : null,
                ]);

                $pumps_query = Pump::where('business_id', $business_id)
                    ->where('location_id', $location_id);

                if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                    $pumps_query->where('is_other_sales_pump', 0);
                }

                $pumps = $pumps_query->get(['id', 'last_meter_reading', 'pod_last_meter']);

                if ($pumps->isEmpty()) {
                    throw new \RuntimeException('No pumps found for the selected business location.');
                }

                foreach ($pumps as $pump) {
                    $starting_meter = ! empty($pump->pod_last_meter)
                        ? max((float) $pump->pod_last_meter, (float) $pump->last_meter_reading)
                        : (float) $pump->last_meter_reading;

                    PumpOperatorAssignment::create([
                        'business_id' => $business_id,
                        'pump_id' => $pump->id,
                        'pump_operator_id' => $pump_operator_id,
                        'starting_meter' => $starting_meter,
                        'date_and_time' => $shift_date,
                        'status' => 'open',
                        'assigned_by' => auth()->id(),
                        'shift_id' => $petro_shift->id,
                        'shift_number' => $shift_number,
                    ]);
                }

                return [
                    'shift_id' => $petro_shift->id,
                    'shift_number' => $shift_number,
                    'created' => true,
                ];
            });

            return response()->json([
                'success' => true,
                'shift_id' => $result['shift_id'],
                'shift_number' => $result['shift_number'],
                'msg' => __($result['created'] ? 'Manual shift number assigned.' : 'Manual shift number already exists.'),
            ]);
        } catch (\Exception $e) {
            Log::emergency('File: '.$e->getFile().' Line: '.$e->getLine().' Message: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => $e instanceof \RuntimeException ? $e->getMessage() : __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * get details for pump id







     * @return Response
     */

    public function getPumpsByLocation(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $location_id = $request->input('location_id');

            if (empty($business_id) || empty($location_id)) {
                return [
                    'success' => true,
                    'pumps' => collect(),
                ];
            }

            $pumps_query = Pump::where('business_id', $business_id)
                ->where('location_id', $location_id);

            // Keep existing behavior: exclude other-sales pumps when the column exists,
            // unless caller explicitly requests to include them.
            $include_other_sales_pump = $request->boolean('include_other_sales_pump');
            if (Schema::hasColumn('pumps', 'is_other_sales_pump') && ! $include_other_sales_pump) {
                $pumps_query->where('is_other_sales_pump', 0);
            }

            $pumps = $pumps_query->pluck('pump_name', 'id');

            return [
                'success' => true,
                'pumps' => $pumps,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    //     public function getPumps(Request $request, $id)

    //     {

    //         try {

    //             $business_id = request()

    //                 ->session()

    //                 ->get("business.id");
    //  $shift_id = $request->input('work_shift');

    //             $assigned_pumps = PumpOperatorAssignment::where(

    //                 "pump_operator_id",

    //                 $id

    //             )

    //                 ->where("settlement_id", null)
    //  ->where("shift_id", $shift_id) // 👈 add this filter
    //                 ->whereDate("date_and_time", date("Y-m-d"))

    //                 ->pluck("pump_id");

    //             if (!empty($assigned_pumps) && sizeof($assigned_pumps) > 0) {

    //                 $pumps = Pump::where("business_id", $business_id)

    //                     ->whereIn("id", $assigned_pumps)

    //                     ->pluck("pump_name", "id");

    //             } else {

    //                 $pumps = Pump::where("business_id", $business_id)->pluck(

    //                     "pump_name",

    //                     "id"

    //                 );

    //             }

    //             $output = [

    //                 "success" => true,

    //                 "pumps" => $pumps,

    //             ];

    //         } catch (\Exception $e) {

    //             \Log::emergency(

    //                 "File: " .

    //                     $e->getFile() .

    //                     "Line: " .

    //                     $e->getLine() .

    //                     "Message: " .

    //                     $e->getMessage()

    //             );

    //             $output = [

    //                 "success" => false,

    //                 "msg" => __("messages.something_went_wrong"),

    //             ];

    //         }

    //         return $output;

    //     }

    private function extractRequestedSettlementShiftIds(Request $request): array
    {
        $shift_ids = [];
        $shift_id_string = $request->shift_ids ?? $request->shift_id ?? null;

        if ($shift_id_string !== null && $shift_id_string !== '') {
            $shift_ids = is_array($shift_id_string)
                ? $shift_id_string
                : array_map('trim', explode(',', $shift_id_string));
        } elseif (! empty($request->work_shift)) {
            $work_shifts = is_array($request->work_shift)
                ? $request->work_shift
                : json_decode($request->work_shift, true);
            $shift_ids = is_array($work_shifts)
                ? $work_shifts
                : array_map('trim', explode(',', $request->work_shift));
        }

        $shift_ids = array_filter((array) $shift_ids, function ($value) {
            return $value !== null && $value !== '';
        });

        return array_values(array_unique(array_map('intval', $shift_ids)));
    }

    private function draftSettlementMatchesRequestedShifts(Settlement $settlement, array $shift_ids): bool
    {
        if (empty($shift_ids)) {
            return true;
        }

        $linked_shift_ids = MeterSale::where('settlement_no', $settlement->id)
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        if (Schema::hasColumn('daily_collections', 'shift_id')) {
            $linked_shift_ids = array_merge(
                $linked_shift_ids,
                DailyCollection::where('settlement_id', $settlement->id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray()
            );
        }

        if (Schema::hasColumn('pump_operator_other_sales', 'shift_id') && Schema::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            $linked_shift_ids = array_merge(
                $linked_shift_ids,
                PumpOperatorOtherSale::where('settlement_no', $settlement->id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray()
            );
        }

        $linked_shift_ids = array_values(array_unique(array_filter($linked_shift_ids, function ($value) {
            return $value !== null && $value !== '';
        })));

        if (empty($linked_shift_ids)) {
            return true;
        }

        return ! empty(array_intersect($shift_ids, $linked_shift_ids));
    }

    /**
     * print resources







     * @param settlement_id







     * @return Response
     */

    public function checkPreviousPumpSettlement()
    {

        try {

            $shift_id = request()->shift_id;
            $pump_operator_id = request()->pump_operator_id;

            if (! $shift_id) {

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'Shift ID is required.',

                    ],

                    400

                );

            }

            // Get the current assignment for the selected shift/operator.
            // This avoids picking an unrelated assignment when multiple rows share the same shift.
            $currentAssignmentQuery = PumpOperatorAssignment::where(
                'shift_id',
                $shift_id
            );
            if (! empty($pump_operator_id)) {
                $currentAssignmentQuery->where('pump_operator_id', $pump_operator_id);
            }
            $currentAssignment = $currentAssignmentQuery->orderBy('id', 'desc')->first();

            if (! $currentAssignment) {

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'Shift not found.',

                    ],

                    404

                );

            }

            // Find any previous unsettled assignment for the same pump/operator.
            // Only block if the assignment shift was CLOSED but never settled.
            // Open/active assignments are not blocking.

            $previousUnsettled = PumpOperatorAssignment::where(
                'pump_id',
                $currentAssignment->pump_id
            )
                ->where('pump_operator_id', $currentAssignment->pump_operator_id)
                ->where('id', '<', $currentAssignment->id)
                ->where('status', 'close')
                ->where(function ($q) {
                    $q->where('closed_in_settlement', 0)
                        ->orWhereNull('closed_in_settlement');
                })
                ->whereNull('settlement_id')
                ->orderBy('id', 'desc')
                ->first();

            if ($previousUnsettled) {

                $pump = Pump::select('pump_name')->find(

                    $previousUnsettled->pump_id

                );

                $operator = PumpOperator::select('name')->find(

                    $previousUnsettled->pump_operator_id

                );

                $pump_name = $pump ? $pump->pump_name : 'Pump';

                $operator_name = $operator ? $operator->name : 'Operator';

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'This pump '.

                            $pump_name.

                            ' has a previous unsettled shift '.

                            $previousUnsettled->shift_number.

                            ' with operator '.

                            $operator_name.

                            '. Please settle it first.',

                    ],

                    200

                );

            }

            // No unsettled records found

            return response()->json(

                [

                    'status' => true,

                    'msg' => 'No previous unsettled shifts found.',

                ],

                200

            );

        } catch (\Exception $e) {

            // Handle unexpected errors

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

            return response()->json($output, 500);

        }

    }

    private function laterPumpSettlementExists(MeterSale $meter_sale): bool
    {
        $pump = Pump::find($meter_sale->pump_id);

        if (! empty($pump) && ! empty($pump->bulk_tank)) {
            return false;
        }

        return MeterSale::where('business_id', $meter_sale->business_id)
            ->where('pump_id', $meter_sale->pump_id)
            ->where('id', '>', $meter_sale->id)
            ->where('settlement_no', '!=', $meter_sale->settlement_no)
            ->exists();
    }

    public function getMeterSaleTableHtml($settlement_id, array $shift_ids = [])
    {
        $active_settlement = Settlement::with([
            'meter_sales' => function ($query) use ($shift_ids) {
                if (! empty($shift_ids)) {
                    $query->whereIn('shift_id', $shift_ids);
                }
            },
            'meter_sales.pump',
            'meter_sales.product',
        ])->find($settlement_id);
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $currency_precision = !empty($pos_settings['currency_precision']) ? $pos_settings['currency_precision'] : 2;

        $discount_types = [
            '' => __('petrogeneral::lang.none'),
            'fixed' => __('petrogeneral::lang.fixed'),
            'percentage' => __('petrogeneral::lang.percentage'),
        ];

        $already_pumps = MeterSale::where('settlement_no', $settlement_id)
            ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                $query->whereIn('shift_id', $shift_ids);
            })
            ->pluck('pump_id')
            ->toArray();

        $pump_nos = Pump::where('business_id', $business_id)
            ->whereNotIn('id', $already_pumps)
            ->pluck('pump_name', 'id');

        $meeter_precision = 3;

        return view('petrogeneral::settlement.partials.meter_sale', compact('active_settlement', 'currency_precision', 'discount_types', 'pump_nos', 'meeter_precision'))->render();
    }

    /**
     * Link meter_sales created from Real Time / payment Enter Meters (settlement_no NULL) to this draft settlement
     * when pump/shift matches an assignment for the settlement's pump operator.
     */

    protected function attachUnsettledRteMeterSalesToSettlement(?Settlement $settlement): void
    {
        if (! $settlement || empty($settlement->id) || empty($settlement->pump_operator_id) || empty($settlement->business_id)) {
            return;
        }

        $business_id = (int) $settlement->business_id;

        MeterSale::where('business_id', $business_id)
            ->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            })
            ->whereExists(function ($sub) use ($settlement, $business_id) {
                $sub->select(DB::raw(1))
                    ->from('pump_operator_assignments')
                    ->whereColumn('pump_operator_assignments.pump_id', 'meter_sales.pump_id')
                    ->whereColumn('pump_operator_assignments.shift_id', 'meter_sales.shift_id')
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_operator_id', $settlement->pump_operator_id);
            })
            ->update(['settlement_no' => $settlement->id]);
    }

    private function syncRealTimePaymentsToSettlement(Settlement $settlement, array $shift_ids, int $business_id): void
    {
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids), function ($shift_id) {
            return $shift_id > 0;
        }));

        if (empty($shift_ids)) {
            $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($query) use ($settlement) {
                    $query->whereNull('settlement_id')
                        ->orWhere('settlement_id', $settlement->id);
                })
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($shift_ids)) {
            return;
        }

        $has_card_pump_payment_column = Schema::hasColumn('settlement_card_payments', 'pump_payment_id');
        $default_customer_id = null;

        $card_payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('shift_id', $shift_ids)
            ->whereIn('payment_type', ['card', 'pos'])
            ->get();

        foreach ($card_payments as $pump_payment) {
            if (! empty($pump_payment->settlement_no) && ! in_array((string) $pump_payment->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($pump_payment) {
                        $query->where('id', $pump_payment->settlement_no)
                            ->orWhere('settlement_no', $pump_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $daily_card = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('collection_no', $pump_payment->collection_form_no)
                ->where('amount', $pump_payment->payment_amount)
                ->when(Schema::hasColumn('daily_cards', 'shift_id'), function ($query) use ($pump_payment) {
                    $query->where('shift_id', $pump_payment->shift_id);
                })
                ->orderByDesc('id')
                ->first();

            $existing_query = SettlementCardPayment::where('business_id', $business_id);
            if ($has_card_pump_payment_column) {
                $existing_query->where('pump_payment_id', $pump_payment->id);
            } elseif (! empty($daily_card)) {
                $existing_query->where('daily_card_id', $daily_card->id);
            } else {
                $existing_query->where('amount', $pump_payment->payment_amount)
                    ->where('card_number', $pump_payment->card_number)
                    ->where('slip_no', $pump_payment->slip_no);
            }

            $settlement_card_payment = $existing_query->first();

            if (! empty($settlement_card_payment) && (string) $settlement_card_payment->settlement_no !== (string) $settlement->id) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($settlement_card_payment) {
                        $query->where('id', $settlement_card_payment->settlement_no)
                            ->orWhere('settlement_no', $settlement_card_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $customer_id = $pump_payment->customer_id ?? (! empty($daily_card) ? $daily_card->customer_id : null);
            if (empty($customer_id)) {
                if ($default_customer_id === null) {
                    $customers = Contact::customersDropdown($business_id, false, true, 'customer');
                    $default_customer_id = array_key_first($customers->toArray());
                }

                $customer_id = $default_customer_id;
            }

            if (empty($customer_id)) {
                continue;
            }

            $data = [
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $pump_payment->payment_amount,
                'customer_id' => $customer_id,
                'card_type' => $pump_payment->card_type ?? (! empty($daily_card) ? $daily_card->card_type : null),
                'card_number' => $pump_payment->card_number ?? (! empty($daily_card) ? $daily_card->card_number : null),
                'daily_card_id' => ! empty($daily_card) ? $daily_card->id : null,
                'note' => $pump_payment->note ?? (! empty($daily_card) ? $daily_card->note : null),
                'slip_no' => $pump_payment->slip_no ?? (! empty($daily_card) ? $daily_card->slip_no : null),
            ];

            if ($has_card_pump_payment_column) {
                $data['pump_payment_id'] = $pump_payment->id;
            }

            // Upsert via Reconciler keyed on pump_payment_id. If $settlement_card_payment
            // already loaded from a prior step, the Reconciler still finds + updates by key.
            $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', $data);

            if (! empty($daily_card)) {
                $daily_card->used_status = 1;
                $daily_card->settlement_no = $settlement->id;
                $daily_card->save();
            }

            $pump_payment->is_used = 1;
            $pump_payment->parent_id = $settlement_card_payment->id;
            $pump_payment->settlement_no = $settlement->id;
            $pump_payment->save();
        }

        $credit_payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('shift_id', $shift_ids)
            ->where('payment_type', 'credit')
            ->get();

        foreach ($credit_payments as $pump_payment) {
            if (! empty($pump_payment->settlement_no) && ! in_array((string) $pump_payment->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($pump_payment) {
                        $query->where('id', $pump_payment->settlement_no)
                            ->orWhere('settlement_no', $pump_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($query) use ($pump_payment) {
                    $query->where('collection_form_no', $pump_payment->collection_form_no)
                        ->orWhere(function ($amount_query) use ($pump_payment) {
                            $amount_query->where('amount', $pump_payment->payment_amount)
                                ->whereNull('settlement_no');
                        });
                })
                ->get();

            foreach ($credit_sales as $credit_sale) {
                if (! empty($credit_sale->settlement_no) && ! in_array((string) $credit_sale->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                    $finalized_owner = Settlement::where('business_id', $business_id)
                        ->where('status', 0)
                        ->where(function ($query) use ($credit_sale) {
                            $query->where('id', $credit_sale->settlement_no)
                                ->orWhere('settlement_no', $credit_sale->settlement_no);
                        })
                        ->exists();

                    if ($finalized_owner) {
                        continue;
                    }
                }

                $credit_sale->settlement_no = $settlement->settlement_no;
                $credit_sale->save();

                if (! empty($credit_sale->daily_voucher_id)) {
                    DailyVoucher::where('id', $credit_sale->daily_voucher_id)
                        ->update(['settlement_no' => $settlement->settlement_no]);
                }
            }

            if ($credit_sales->isNotEmpty()) {
                $pump_payment->is_used = 1;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }
        }
    }

    private function updateSettlementTotalAmount($settlement_id)
    {
        $settlement = Settlement::find($settlement_id);
        if (!$settlement) return;

        $meter_sale_total = $settlement->meter_sales
            ->unique(function ($item) {
                return implode('|', [
                    $item->settlement_no,
                    $item->shift_id,
                    $item->pump_id,
                    $item->starting_meter,
                    $item->closing_meter,
                    $item->qty,
                    $item->price,
                    $item->discount,
                    $item->discount_type,
                ]);
            })
            ->sum('discount_amount');
        
        $other_sale_total_raw = $settlement->other_sales->sum('sub_total');
        $other_sale_discount = $settlement->other_sales->sum('discount_amount');
        $other_sale_total = $other_sale_total_raw - $other_sale_discount;
        
        $other_income_total = $settlement->other_incomes->sum('sub_total');
        $customer_payment_total = $settlement->customer_payments->sum('sub_total');
        
        $total_amount = $meter_sale_total + $other_sale_total + $other_income_total + $customer_payment_total;
        
        $settlement->total_amount = $total_amount;
        $settlement->save();
        
        return $total_amount;
    }
}
