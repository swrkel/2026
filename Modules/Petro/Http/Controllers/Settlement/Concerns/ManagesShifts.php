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
 * Shift numbering, auto-increment and the shift ids attached to a settlement.
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
 * Methods here: getNextAutoShiftNumber, createAutoIncrementedShiftForSettlement, resolveAutoShiftLocationId, incrementShiftNumber, storeManualShiftNumber, extractShiftIdsFromSettlement, extractLastInteger, normalizeDirectSettlementShiftLabel, getDirectSettlementShiftPrefix, getNextDirectSettlementShiftLabel
 */
trait ManagesShifts
{
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
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');
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

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
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
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

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

                if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
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

    public function extractLastInteger($text)
    {
        if (is_array($text)) {
            $text = implode(' ', array_filter(array_map(function ($value) {
                return is_scalar($value) ? (string) $value : '';
            }, $text)));
        }

        $text = (string) $text;

        if (preg_match_all('/\d+/', $text, $matches) && ! empty($matches[0])) {

            return intval(end($matches[0]));

        } else {

            return 0;

        }

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

    private function getDirectSettlementShiftPrefix(?int $business_id = null): string
    {
        $prefixes = request()->session()->get('business.ref_no_prefixes', []);

        if (empty($prefixes) && ! empty($business_id)) {
            $business = Business::find($business_id);
            $prefixes = $business->ref_no_prefixes ?? [];
        }

        return ! empty($prefixes['direct_settlement_shift'])
            ? $prefixes['direct_settlement_shift']
            : 'DST';
    }

    private function getNextDirectSettlementShiftLabel(int $business_id, ?string $currentLabel = null, ?int $currentOperatorId = null, ?int $selectedOperatorId = null): string
    {
        $prefix = $this->getDirectSettlementShiftPrefix($business_id);
        $currentLabel = $this->normalizeDirectSettlementShiftLabel($currentLabel, $business_id);

        if (
            ! empty($currentLabel)
            && str_starts_with($currentLabel, $prefix)
        ) {
            return $currentLabel;
        }

        $last_number = Settlement::where('business_id', $business_id)
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%')
            ->where('work_shift', 'LIKE', '%' . $prefix . '%')
            ->get(['work_shift'])
            ->map(function ($settlement) {
                return $this->extractLastInteger($settlement->work_shift);
            })
            ->max() ?? 0;

        if (! empty($currentLabel) && str_starts_with($currentLabel, $prefix)) {
            $last_number = max((int) $last_number, $this->extractLastInteger($currentLabel));
        }

        return $prefix . ($last_number + 1);
    }
}
