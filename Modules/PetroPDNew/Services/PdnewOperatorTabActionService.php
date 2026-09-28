<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Services\PoneDayEntryService;
use Modules\PumperDashboardNew\Services\PonePaymentService;
use Modules\PumperDashboardNew\Services\PonePumpService;
use Modules\PumperDashboardNew\Services\PoneShiftService;
use Modules\PumperDashboardNew\Services\PoneUnloadStockService;

class PdnewOperatorTabActionService
{
    public function __construct(
        private PdnewPoneAdminContextService $poneContext,
        private PoneDayEntryService $dayEntries,
        private PonePaymentService $payments,
        private PonePumpService $pumps,
        private PoneShiftService $shifts,
        private PoneUnloadStockService $unloads,
        private PdnewOperatorWorkspaceCacheService $workspaceCache
    ) {}

    /** @return array<string,mixed> */
    public function modalData(string $type, int $id, string $action, int $businessId, ?int $locationId): array
    {
        $allowed = ['day-entry', 'payment', 'shift', 'assignment', 'unload', 'variance', 'meter-payment', 'day-end'];
        abort_unless(in_array($type, $allowed, true), 404);
        abort_unless(in_array($action, ['view', 'create', 'edit', 'void', 'current', 'close', 'print'], true), 404);

        $data = [
            'type' => $type,
            'action' => $action,
            'businessId' => $businessId,
            'locationId' => $locationId,
            'record' => null,
            'rows' => collect(),
            'options' => [],
        ];

        if ($action === 'create') {
            $data['options'] = $this->createOptions($type, $businessId, $locationId);
            return $data;
        }

        $data['record'] = match ($type) {
            'day-entry' => $this->dayEntry($businessId, $locationId, $id),
            'payment' => $this->payment($businessId, $locationId, $id),
            'shift' => $this->shift($businessId, $locationId, $id),
            'assignment', 'meter-payment' => $this->assignment($businessId, $locationId, $id),
            'unload' => $this->unload($businessId, $locationId, $id),
            'variance' => $this->variance($businessId, $locationId, $id),
            'day-end' => $this->dayEnd($businessId, $locationId, $id),
        };

        if ($type === 'payment') {
            $data['rows'] = $this->paymentDetails($businessId, $id);
        } elseif ($type === 'shift') {
            $data['rows'] = $this->shiftDetails($businessId, $id);
        } elseif ($type === 'unload') {
            $data['rows'] = $this->unloadLines($businessId, $id);
            $data['options'] = $this->createOptions('unload', $businessId, $locationId, (int) $data['record']->shift_id);
        } elseif (in_array($type, ['assignment', 'meter-payment'], true)) {
            $data['rows'] = $this->assignmentDetails($businessId, $id);
        } elseif ($type === 'day-end') {
            $data['rows'] = $this->dayEndSettlements($businessId, $id);
        }

        if ($type === 'day-entry') {
            $data['options'] = $this->createOptions('day-entry', $businessId, $locationId, (int) $data['record']->shift_id);
        } elseif ($type === 'payment') {
            $data['options'] = $this->createOptions('payment', $businessId, $locationId, (int) $data['record']->shift_id);
        }

        return $data;
    }

    public function createDayEntry(int $businessId, ?int $locationId, array $data): void
    {
        $shiftId = (int) $data['shift_id'];
        unset($data['shift_id']);
        $this->poneContext->run($businessId, $locationId, $shiftId, fn () => $this->dayEntries->create($data));
        $this->workspaceCache->flush($businessId);
    }

    public function updateDayEntry(int $businessId, ?int $locationId, int $entryId, array $data): void
    {
        $row = $this->dayEntry($businessId, $locationId, $entryId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->dayEntries->update($entryId, $data));
        $this->workspaceCache->flush($businessId);
    }

    public function voidDayEntry(int $businessId, ?int $locationId, int $entryId, string $reason): void
    {
        $row = $this->dayEntry($businessId, $locationId, $entryId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->dayEntries->void($entryId, $reason));
        $this->workspaceCache->flush($businessId);
    }

    public function createPayment(int $businessId, ?int $locationId, array $data): void
    {
        $shiftId = (int) $data['shift_id'];
        unset($data['shift_id']);
        $this->poneContext->run($businessId, $locationId, $shiftId, fn () => $this->payments->create($data));
        $this->workspaceCache->flush($businessId);
    }

    public function updatePayment(int $businessId, ?int $locationId, int $paymentId, array $data): void
    {
        $row = $this->payment($businessId, $locationId, $paymentId);
        $payload = $this->paymentPayload($businessId, $paymentId, $data);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->payments->update($paymentId, $payload));
        $this->workspaceCache->flush($businessId);
    }

    public function voidPayment(int $businessId, ?int $locationId, int $paymentId, string $reason): void
    {
        $row = $this->payment($businessId, $locationId, $paymentId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->payments->void($paymentId, $reason));
        $this->workspaceCache->flush($businessId);
    }

    public function recordCurrentMeter(int $businessId, ?int $locationId, int $assignmentId, array $data): void
    {
        $row = $this->assignment($businessId, $locationId, $assignmentId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->pumps->recordCurrent(
            $assignmentId,
            (float) $data['meter'],
            (float) ($data['testing_quantity'] ?? 0),
            array_key_exists('unit_price', $data) && $data['unit_price'] !== null ? (float) $data['unit_price'] : null,
            $data['note'] ?? null
        ));
        $this->workspaceCache->flush($businessId);
    }

    public function closePump(int $businessId, ?int $locationId, int $assignmentId, array $data): void
    {
        $row = $this->assignment($businessId, $locationId, $assignmentId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->pumps->close(
            $assignmentId,
            (float) $data['meter'],
            (float) ($data['testing_quantity'] ?? 0),
            array_key_exists('unit_price', $data) && $data['unit_price'] !== null ? (float) $data['unit_price'] : null,
            $data['note'] ?? null
        ));
        $this->workspaceCache->flush($businessId);
    }

    public function closeShift(int $businessId, ?int $locationId, int $shiftId, ?string $note): void
    {
        $this->poneContext->run($businessId, $locationId, $shiftId, fn () => $this->shifts->close($note));
        $this->workspaceCache->flush($businessId);
    }

    public function createUnload(int $businessId, ?int $locationId, array $data): void
    {
        $shiftId = (int) $data['shift_id'];
        unset($data['shift_id']);
        $this->poneContext->run($businessId, $locationId, $shiftId, fn () => $this->unloads->create($data));
        $this->workspaceCache->flush($businessId);
    }

    public function updateUnload(int $businessId, ?int $locationId, int $unloadId, array $data): void
    {
        $row = $this->unload($businessId, $locationId, $unloadId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->unloads->update($unloadId, $data));
        $this->workspaceCache->flush($businessId);
    }

    public function voidUnload(int $businessId, ?int $locationId, int $unloadId, string $reason): void
    {
        $row = $this->unload($businessId, $locationId, $unloadId);
        $this->poneContext->run($businessId, $locationId, (int) $row->shift_id, fn () => $this->unloads->void($unloadId, $reason));
        $this->workspaceCache->flush($businessId);
    }

    public function voidVariance(int $businessId, ?int $locationId, string $kind, int $id, string $reason): void
    {
        abort_unless(in_array($kind, ['shortage', 'commission'], true), 404);
        $table = $kind === 'shortage' ? 'pone_shortage_recoveries' : 'pone_excess_commissions';
        $sourceType = $kind === 'shortage' ? 'shortage_recovery' : 'excess_commission';
        $this->requireTable($table);

        DB::transaction(function () use ($businessId, $locationId, $table, $sourceType, $id, $reason): void {
            $query = DB::table($table)->where('business_id', $businessId)->where('id', $id);
            if ($locationId) {
                $query->where(function ($q) use ($locationId): void {
                    $q->where('location_id', $locationId)->orWhereNull('location_id');
                });
            }
            $row = $query->lockForUpdate()->first();
            abort_unless($row, 404);
            if ((string) $row->status === 'void') return;

            DB::table($table)->where('id', $id)->update([
                'status' => 'void',
                'note' => trim((string) ($row->note ?? '') . "\nVoid reason: " . trim($reason)),
                'voided_by' => (int) auth()->id() ?: null,
                'voided_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('pone_operator_ledger_entries')) {
                DB::table('pone_operator_ledger_entries')
                    ->where('business_id', $businessId)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $id)
                    ->update(['status' => 'void', 'updated_at' => now()]);
            }
        }, 3);
        $this->workspaceCache->flush($businessId);
    }

    /** @return array<string,mixed> */
    private function createOptions(string $type, int $businessId, ?int $locationId, ?int $shiftId = null): array
    {
        $shifts = collect();
        if (Schema::hasTable('pone_shifts')) {
            $shifts = DB::table('pone_shifts as shift')
                ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                    $join->on('map.business_id', '=', 'shift.business_id')
                        ->on('map.pone_operator_profile_id', '=', 'shift.operator_profile_id');
                })
                ->where('shift.business_id', $businessId)
                ->when($locationId, fn ($q) => $q->where('shift.location_id', $locationId))
                ->whereIn('shift.status', ['open', 'closing'])
                ->orderByDesc('shift.opened_at')
                ->limit(150)
                ->get(['shift.id', 'shift.shift_number', 'shift.operator_profile_id', 'shift.location_id', 'map.display_name as operator_name']);
        }

        $selectedShiftId = $shiftId ?: (int) optional($shifts->first())->id;
        $assignments = collect();
        if ($selectedShiftId && Schema::hasTable('pone_pump_assignments')) {
            $assignments = DB::table('pone_pump_assignments')
                ->where('business_id', $businessId)
                ->where('shift_id', $selectedShiftId)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('pump_id')
                ->get(['id', 'pump_id', 'product_id', 'opening_meter', 'current_meter', 'closing_meter', 'testing_quantity', 'unit_price', 'status']);
        }

        $products = collect();
        if (Schema::hasTable('products')) {
            $query = DB::table('products')->where('business_id', $businessId);
            if (Schema::hasColumn('products', 'name')) {
                $products = $query->orderBy('name')->limit(1000)->get(['id', 'name']);
            }
        }

        $stores = collect();
        if (Schema::hasTable('business_locations')) {
            $stores = DB::table('business_locations')->where('business_id', $businessId)
                ->orderBy('name')->get(['id', 'name']);
        }

        $tanks = collect();
        foreach (['fuel_tanks', 'tanks'] as $table) {
            if (! Schema::hasTable($table)) continue;
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'business_id')) $query->where('business_id', $businessId);
            if ($locationId && Schema::hasColumn($table, 'location_id')) $query->where('location_id', $locationId);
            $columns = ['id'];
            foreach (['fuel_tank_number', 'name', 'tank_name'] as $column) {
                if (Schema::hasColumn($table, $column)) $columns[] = $column;
            }
            $tanks = $query->limit(500)->get(array_values(array_unique($columns)));
            break;
        }

        return compact('shifts', 'assignments', 'products', 'stores', 'tanks', 'selectedShiftId');
    }

    private function dayEntry(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pone_day_entries');
        $query = DB::table('pone_day_entries as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId)->where('item.id', $id);
        $this->scopeLocation($query, 'item', $locationId);
        $row = $query->first(['item.*', 'shift.shift_number', 'shift.status as shift_status', 'map.display_name as operator_name']);
        abort_unless($row, 404);
        return $row;
    }

    private function payment(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pone_payments');
        $query = DB::table('pone_payments as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId)->where('item.id', $id);
        $this->scopeLocation($query, 'item', $locationId);
        $row = $query->first(['item.*', 'shift.shift_number', 'shift.status as shift_status', 'map.display_name as operator_name']);
        abort_unless($row, 404);
        return $row;
    }

    private function shift(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pone_shifts');
        $query = DB::table('pone_shifts as shift')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'shift.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'shift.operator_profile_id');
            })
            ->where('shift.business_id', $businessId)->where('shift.id', $id);
        $this->scopeLocation($query, 'shift', $locationId);
        $row = $query->first(['shift.*', 'map.display_name as operator_name']);
        abort_unless($row, 404);
        return $row;
    }

    private function assignment(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pone_pump_assignments');
        $query = DB::table('pone_pump_assignments as item')
            ->join('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId)->where('item.id', $id);
        $this->scopeLocation($query, 'item', $locationId);
        $row = $query->first(['item.*', 'shift.shift_number', 'shift.status as shift_status', 'map.display_name as operator_name']);
        abort_unless($row, 404);
        return $row;
    }

    private function unload(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pone_unload_stocks');
        $query = DB::table('pone_unload_stocks as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId)->where('item.id', $id);
        $this->scopeLocation($query, 'item', $locationId);
        $row = $query->first(['item.*', 'shift.shift_number', 'shift.status as shift_status', 'map.display_name as operator_name']);
        abort_unless($row, 404);
        return $row;
    }

    private function variance(int $businessId, ?int $locationId, int $id): object
    {
        $kind = request()->query('kind', 'shortage');
        $table = $kind === 'commission' ? 'pone_excess_commissions' : 'pone_shortage_recoveries';
        $this->requireTable($table);
        $query = DB::table($table . ' as item')->where('item.business_id', $businessId)->where('item.id', $id);
        $this->scopeLocation($query, 'item', $locationId, true);
        $row = $query->first();
        abort_unless($row, 404);
        $row->variance_kind = $kind === 'commission' ? 'commission' : 'shortage';
        return $row;
    }

    private function dayEnd(int $businessId, ?int $locationId, int $id): object
    {
        $this->requireTable('pdnew_day_ends');
        $query = DB::table('pdnew_day_ends')->where('business_id', $businessId)->where('id', $id);
        if ($locationId) $query->where('location_id', $locationId);
        $row = $query->first();
        abort_unless($row, 404);
        return $row;
    }

    /** @return Collection<int,object> */
    private function paymentDetails(int $businessId, int $paymentId): Collection
    {
        $rows = collect();
        if (Schema::hasTable('pone_payment_cash_denominations')) {
            $rows = $rows->concat(DB::table('pone_payment_cash_denominations')->where('payment_id', $paymentId)
                ->get()->map(fn ($r) => (object) ['detail_type' => 'cash denomination', 'description' => $r->denomination . ' × ' . $r->quantity, 'amount' => $r->amount]));
        }
        if (Schema::hasTable('pone_payment_card_lines')) {
            $rows = $rows->concat(DB::table('pone_payment_card_lines')->where('payment_id', $paymentId)
                ->get()->map(fn ($r) => (object) ['detail_type' => 'card', 'description' => trim(($r->card_type ?: 'Card') . ' ' . ($r->slip_no ?: '') . ' ' . ($r->reference_no ?: '')), 'amount' => $r->amount]));
        }
        if (Schema::hasTable('pone_credit_sales')) {
            $credit = DB::table('pone_credit_sales')->where('business_id', $businessId)->where('payment_id', $paymentId)->first();
            if ($credit) {
                $amount = Schema::hasTable('pone_credit_sale_lines') ? (float) DB::table('pone_credit_sale_lines')->where('credit_sale_id', $credit->id)->sum('amount') : 0;
                $rows->push((object) ['detail_type' => 'credit', 'description' => trim('Order ' . $credit->order_number . ' / Vehicle ' . $credit->vehicle_number), 'amount' => $amount]);
            }
        }
        return $rows;
    }

    /** @return Collection<int,object> */
    private function shiftDetails(int $businessId, int $shiftId): Collection
    {
        if (! Schema::hasTable('pone_pump_assignments')) return collect();
        return DB::table('pone_pump_assignments')->where('business_id', $businessId)->where('shift_id', $shiftId)
            ->where('status', '<>', 'cancelled')->orderBy('pump_id')->get();
    }

    /** @return Collection<int,object> */
    private function unloadLines(int $businessId, int $unloadId): Collection
    {
        if (! Schema::hasTable('pone_unload_stock_lines')) return collect();
        return DB::table('pone_unload_stock_lines')->where('unload_stock_id', $unloadId)->orderBy('id')->get();
    }

    /** @return Collection<int,object> */
    private function assignmentDetails(int $businessId, int $assignmentId): Collection
    {
        if (! Schema::hasTable('pone_meter_readings')) return collect();
        return DB::table('pone_meter_readings')->where('business_id', $businessId)->where('assignment_id', $assignmentId)
            ->orderByDesc('recorded_at')->limit(100)->get();
    }

    /** @return Collection<int,object> */
    private function dayEndSettlements(int $businessId, int $dayEndId): Collection
    {
        if (! Schema::hasTable('pdnew_day_end_settlements') || ! Schema::hasTable('pdnew_settlements')) return collect();
        return DB::table('pdnew_day_end_settlements as line')
            ->join('pdnew_settlements as settlement', 'settlement.id', '=', 'line.settlement_id')
            ->where('line.business_id', $businessId)->where('line.day_end_id', $dayEndId)
            ->orderBy('settlement.settlement_number')->get(['settlement.*']);
    }

    /** @return array<string,mixed> */
    private function paymentPayload(int $businessId, int $paymentId, array $changes): array
    {
        $payment = DB::table('pone_payments')->where('business_id', $businessId)->where('id', $paymentId)->first();
        abort_unless($payment, 404);
        $payload = array_merge((array) $payment, $changes);
        $payload['payment_type'] = $payment->payment_type;
        $payload['edit_reason'] = trim((string) ($changes['edit_reason'] ?? ''));

        if (Schema::hasTable('pone_payment_cash_denominations')) {
            $payload['cash_denominations'] = DB::table('pone_payment_cash_denominations')->where('payment_id', $paymentId)
                ->get(['denomination', 'quantity'])->map(fn ($r) => (array) $r)->all();
        }
        if (Schema::hasTable('pone_payment_card_lines')) {
            $payload['card_lines'] = DB::table('pone_payment_card_lines')->where('payment_id', $paymentId)
                ->get(['card_type', 'last_four', 'slip_no', 'account_id', 'reference_no', 'amount'])->map(fn ($r) => (array) $r)->all();
        }
        if ($payment->payment_type === 'credit' && Schema::hasTable('pone_credit_sales')) {
            $credit = DB::table('pone_credit_sales')->where('business_id', $businessId)->where('payment_id', $paymentId)->first();
            if ($credit) {
                $payload = array_merge($payload, (array) $credit, [
                    'customer_confirmed' => true,
                    'order_confirmed' => true,
                    'vehicle_confirmed' => true,
                    'confirmation_rounds' => max(2, (int) $credit->confirmation_rounds),
                ]);
                if (Schema::hasTable('pone_credit_sale_lines')) {
                    $payload['lines'] = DB::table('pone_credit_sale_lines')->where('credit_sale_id', $credit->id)
                        ->get(['product_id', 'quantity', 'unit_price', 'discount_amount'])->map(fn ($r) => (array) $r)->all();
                }
            }
        }
        return $payload;
    }

    private function scopeLocation($query, string $alias, ?int $locationId, bool $allowNull = false): void
    {
        if (! $locationId) return;
        if ($allowNull) {
            $query->where(function ($q) use ($alias, $locationId): void {
                $q->where($alias . '.location_id', $locationId)->orWhereNull($alias . '.location_id');
            });
            return;
        }
        $query->where($alias . '.location_id', $locationId);
    }

    private function requireTable(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw ValidationException::withMessages(['database' => 'Required table is missing: ' . $table]);
        }
    }
}
