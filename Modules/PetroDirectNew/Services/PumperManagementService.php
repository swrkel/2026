<?php

namespace Modules\PetroDirectNew\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirectNew\Entities\PdirectnewAdjustment;
use Modules\PetroDirectNew\Entities\PdirectnewAssignment;
use Modules\PetroDirectNew\Entities\PdirectnewDailyCollection;
use Modules\PetroDirectNew\Entities\PdirectnewDipReading;
use Modules\PetroDirectNew\Entities\PdirectnewMeterReading;
use Modules\PetroDirectNew\Entities\PdirectnewOperator;
use Modules\PetroDirectNew\Entities\PdirectnewPump;
use Modules\PetroDirectNew\Entities\PdirectnewPumperDayEntry;
use Modules\PetroDirectNew\Entities\PdirectnewSettlement;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementPayment;
use Modules\PetroDirectNew\Entities\PdirectnewShift;
use Modules\PetroDirectNew\Entities\PdirectnewTank;
use Modules\PetroDirectNew\Entities\PdirectnewTankTransfer;
use Modules\PetroDirectNew\Entities\PdirectnewUnloadStock;
use Modules\PetroDirectNew\Support\BusinessContext;

class PumperManagementService
{
    public function __construct(
        private BusinessContext $context,
        private NumberSequenceService $numbers,
        private AuditService $audit
    ) {
    }

    public function workspace(string $tab, ?int $locationId = null): array
    {
        $businessId = $this->context->requireBusiness();
        if ($locationId) {
            abort_unless($this->context->locationAllowed($locationId), 403);
        }

        $data = $this->emptyWorkspace($tab);

        switch ($tab) {
            case 'operators':
                // Keep the list page light: one optimized operator query only.
                // Tanks, pumps, shifts and assignments belong to their own tabs/workflows.
                $data['operators'] = $this->operators($businessId, $locationId);
                break;

            case 'payments':
                $data['operators'] = $this->operators($businessId, $locationId);
                $data['adjustments'] = $this->scoped(PdirectnewAdjustment::query(), $businessId, $locationId)
                    ->with(['operator', 'settlement'])->latest('id')->limit(300)->get();
                break;

            case 'pumper_day_entries':
                $data['operators'] = $this->operators($businessId, $locationId);
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['pumperDayEntries'] = $this->scoped(PdirectnewPumperDayEntry::query(), $businessId, $locationId)
                    ->with(['operator', 'shift'])->latest('entry_date')->latest('id')->limit(400)->get();
                break;

            case 'shift_summary':
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['paymentRows'] = $this->paymentRows($businessId, $locationId);
                $data['shiftSummaries'] = $this->shiftSummaries($data['shifts'], $data['paymentRows']);
                break;

            case 'payment_summary':
                $data['operators'] = $this->operators($businessId, $locationId);
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['paymentRows'] = $this->paymentRows($businessId, $locationId);
                $data['paymentSummary'] = $this->paymentSummary($data['paymentRows']);
                break;

            case 'meters_with_payments':
                $data['assignments'] = $this->assignments($businessId, $locationId);
                $data['paymentRows'] = $this->paymentRows($businessId, $locationId);
                $data['meterPaymentRows'] = $this->metersWithPayments($data['assignments'], $data['paymentRows']);
                break;

            case 'daily_pump_status':
                $data['pumps'] = $this->pumps($businessId, $locationId);
                $data['assignments'] = $this->assignments($businessId, $locationId);
                $data['dailyPumpStatus'] = $this->dailyPumpStatus($data['pumps'], $data['assignments']);
                break;

            case 'close_shift':
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['assignments'] = $this->assignments($businessId, $locationId);
                $data['openShifts'] = $data['shifts']->whereIn('status', ['open', 'active']);
                $data['openAssignments'] = $data['assignments']->whereIn('status', ['assigned', 'received']);
                break;

            case 'current_meter':
                $data['operators'] = $this->operators($businessId, $locationId);
                $data['tanks'] = $this->tanks($businessId, $locationId);
                $data['pumps'] = $this->pumps($businessId, $locationId);
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['openShifts'] = $data['shifts']->whereIn('status', ['open', 'active']);
                $data['meterReadings'] = $this->scoped(PdirectnewMeterReading::query(), $businessId, $locationId)
                    ->with(['operator', 'pump', 'shift'])->latest('recorded_at')->latest('id')->limit(400)->get();
                $data['currentMeters'] = $this->currentMeters($data['pumps'], $data['meterReadings']);
                break;

            case 'unload_stock':
                $data['operators'] = $this->operators($businessId, $locationId);
                $data['tanks'] = $this->tanks($businessId, $locationId);
                $data['shifts'] = $this->shifts($businessId, $locationId);
                $data['unloadStocks'] = $this->scoped(PdirectnewUnloadStock::query(), $businessId, $locationId)
                    ->with(['operator', 'shift', 'lines.tank'])->latest('unload_date')->latest('id')->limit(300)->get();
                break;
        }

        return $data;
    }

    private function emptyWorkspace(string $tab): array
    {
        $empty = collect();

        return [
            'tab' => $tab,
            'operators' => $empty,
            'tanks' => $empty,
            'pumps' => $empty,
            'shifts' => $empty,
            'openShifts' => $empty,
            'assignments' => $empty,
            'openAssignments' => $empty,
            'meterReadings' => $empty,
            'currentMeters' => $empty,
            'adjustments' => $empty,
            'pumperDayEntries' => $empty,
            'unloadStocks' => $empty,
            'collections' => $empty,
            'paymentRows' => $empty,
            'paymentSummary' => $empty,
            'shiftSummaries' => $empty,
            'meterPaymentRows' => $empty,
            'dailyPumpStatus' => $empty,
            'dipReadings' => $empty,
            'tankTransfers' => $empty,
        ];
    }

    private function operators(int $businessId, ?int $locationId): Collection
    {
        $query = DB::table('pdirectnew_operators as o')
            ->where('o.business_id', $businessId);

        if ($locationId) {
            $query->where(function ($scope) use ($locationId) {
                $scope->where('o.location_id', $locationId)->orWhereNull('o.location_id');
            });
        }

        $hasLocations = Schema::hasTable('business_locations');
        $hasUsers = Schema::hasTable('users');
        $userColumns = $hasUsers ? array_flip(Schema::getColumnListing('users')) : [];

        if ($hasLocations) {
            $query->leftJoin('business_locations as bl', function ($join) use ($businessId) {
                $join->on('bl.id', '=', 'o.location_id');
                if (Schema::hasColumn('business_locations', 'business_id')) {
                    $join->where('bl.business_id', '=', $businessId);
                }
            });
        }

        if ($hasUsers) {
            $query->leftJoin('users as u', function ($join) use ($businessId, $userColumns) {
                $join->on('u.id', '=', 'o.user_id');
                if (isset($userColumns['business_id'])) {
                    $join->where('u.business_id', '=', $businessId);
                }
            });
        }

        $select = [
            'o.id', 'o.business_id', 'o.location_id', 'o.user_id', 'o.source_operator_id',
            'o.operator_no', 'o.name', 'o.address', 'o.mobile', 'o.landline', 'o.dob',
            'o.nic', 'o.email', 'o.username', 'o.opening_balance', 'o.commission_type',
            'o.commission_value', 'o.short_amount', 'o.excess_amount', 'o.transaction_date',
            'o.is_default', 'o.can_fullscreen', 'o.hide_in_direct_settlement_if_pending_shifts',
            'o.status', 'o.can_login', 'o.is_active', 'o.source_updated_at', 'o.metadata',
            'o.created_at', 'o.updated_at',
        ];
        $select[] = $hasLocations
            ? DB::raw("COALESCE(bl.name, 'All Locations') as location_name")
            : DB::raw("'All Locations' as location_name");

        if ($hasUsers) {
            $first = isset($userColumns['first_name']) ? "COALESCE(u.first_name, '')" : "''";
            $last = isset($userColumns['last_name']) ? "COALESCE(u.last_name, '')" : "''";
            $username = isset($userColumns['username']) ? "COALESCE(u.username, '')" : "''";
            $email = isset($userColumns['email']) ? "COALESCE(u.email, '')" : "''";
            $select[] = DB::raw("TRIM(CONCAT({$first}, ' ', {$last})) as linked_user_name");
            $select[] = DB::raw("{$username} as linked_username");
            $select[] = DB::raw("{$email} as linked_email");
        } else {
            $select[] = DB::raw("'' as linked_user_name");
            $select[] = DB::raw("'' as linked_username");
            $select[] = DB::raw("'' as linked_email");
        }

        return $query->select($select)->orderBy('o.name')->orderBy('o.id')->get();
    }

    private function tanks(int $businessId, ?int $locationId): Collection
    {
        return $this->scoped(PdirectnewTank::query(), $businessId, $locationId)
            ->orderBy('tank_no')->get();
    }

    private function pumps(int $businessId, ?int $locationId): Collection
    {
        return $this->scoped(PdirectnewPump::query(), $businessId, $locationId)
            ->orderBy('pump_no')->get();
    }

    private function shifts(int $businessId, ?int $locationId): Collection
    {
        return $this->scoped(PdirectnewShift::query(), $businessId, $locationId)
            ->with(['operator', 'assignments.pump'])->latest('id')->limit(250)->get();
    }

    private function assignments(int $businessId, ?int $locationId): Collection
    {
        return $this->scoped(PdirectnewAssignment::query(), $businessId, $locationId)
            ->with(['operator', 'pump', 'shift'])->latest('id')->limit(400)->get();
    }

    private function scoped(Builder $query, int $businessId, ?int $locationId = null): Builder
    {
        $query->where('business_id', $businessId);
        if ($locationId) {
            $query->where(function (Builder $builder) use ($locationId) {
                $builder->where('location_id', $locationId)->orWhereNull('location_id');
            });
        }
        return $query;
    }

    private function paymentRows(int $businessId, ?int $locationId): Collection
    {
        return DB::table('pdirectnew_settlement_payments as p')
            ->join('pdirectnew_settlements as s', 's.id', '=', 'p.settlement_id')
            ->leftJoin('pdirectnew_operators as o', 'o.id', '=', 's.operator_id')
            ->where('p.business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('p.location_id', $locationId))
            ->where('p.status', 'active')
            ->select([
                'p.id', 'p.location_id', 'p.settlement_id', 'p.payment_type', 'p.reference_no',
                'p.amount', 'p.payment_date', 'p.created_at', 's.settlement_no', 's.shift_id',
                's.operator_id', 's.transaction_date', 'o.name as operator_name',
            ])
            ->orderByDesc('p.id')->limit(1000)->get();
    }

    private function paymentSummary(Collection $rows): Collection
    {
        return $rows->groupBy(fn ($row) => strtolower((string) $row->payment_type))
            ->map(function (Collection $group, string $type) {
                return (object) [
                    'payment_type' => $type,
                    'record_count' => $group->count(),
                    'amount' => (float) $group->sum('amount'),
                ];
            })->sortByDesc('amount')->values();
    }

    private function shiftSummaries(Collection $shifts, Collection $payments): Collection
    {
        $paymentsByShift = $payments->groupBy('shift_id');
        return $shifts->map(function (PdirectnewShift $shift) use ($paymentsByShift) {
            $assignmentSales = (float) $shift->assignments->sum('sales_amount');
            $received = (float) ($paymentsByShift->get($shift->id, collect())->sum('amount'));
            return (object) [
                'id' => $shift->id,
                'shift_no' => $shift->shift_no,
                'operator_name' => optional($shift->operator)->name ?: '—',
                'opened_at' => $shift->opened_at,
                'closed_at' => $shift->closed_at,
                'status' => $shift->status,
                'pump_count' => $shift->assignments->count(),
                'closed_pump_count' => $shift->assignments->where('status', 'closed')->count(),
                'sold_qty' => (float) $shift->assignments->sum('sold_qty'),
                'sales_amount' => $assignmentSales,
                'received_amount' => $received,
                'variance' => $received - $assignmentSales,
            ];
        });
    }

    private function metersWithPayments(Collection $assignments, Collection $payments): Collection
    {
        $paymentsByShift = $payments->groupBy('shift_id');
        $assignmentsByShift = $assignments->groupBy('shift_id');

        return $assignments->map(function (PdirectnewAssignment $assignment) use ($paymentsByShift, $assignmentsByShift) {
            $shiftPayments = (float) $paymentsByShift->get($assignment->shift_id, collect())->sum('amount');
            $shiftSales = (float) $assignmentsByShift->get($assignment->shift_id, collect())->sum('sales_amount');
            $allocation = $shiftSales > 0
                ? $shiftPayments * ((float) $assignment->sales_amount / $shiftSales)
                : 0.0;
            return (object) [
                'assignment_id' => $assignment->id,
                'shift_no' => optional($assignment->shift)->shift_no ?: '—',
                'operator_name' => optional($assignment->operator)->name ?: '—',
                'pump_name' => optional($assignment->pump)->name ?: optional($assignment->pump)->pump_no ?: '—',
                'opening_meter' => (float) $assignment->opening_meter,
                'closing_meter' => $assignment->closing_meter,
                'testing_qty' => (float) $assignment->testing_qty,
                'sold_qty' => (float) $assignment->sold_qty,
                'sales_amount' => (float) $assignment->sales_amount,
                'allocated_payment' => $allocation,
                'variance' => $allocation - (float) $assignment->sales_amount,
                'status' => $assignment->status,
            ];
        });
    }

    private function currentMeters(Collection $pumps, Collection $readings): Collection
    {
        $latest = $readings->groupBy('pump_id')->map(fn (Collection $rows) => $rows->first());
        return $pumps->map(function (PdirectnewPump $pump) use ($latest) {
            $reading = $latest->get($pump->id);
            return (object) [
                'pump_id' => $pump->id,
                'pump_no' => $pump->pump_no,
                'pump_name' => $pump->name,
                'tank_id' => $pump->tank_id,
                'product_id' => $pump->product_id,
                'current_meter' => (float) $pump->current_meter,
                'last_reading_type' => $reading?->reading_type,
                'last_recorded_at' => $reading?->recorded_at,
                'status' => $pump->status,
            ];
        });
    }

    private function dailyPumpStatus(Collection $pumps, Collection $assignments): Collection
    {
        $latest = $assignments->groupBy('pump_id')->map(fn (Collection $rows) => $rows->first());
        return $pumps->map(function (PdirectnewPump $pump) use ($latest) {
            $assignment = $latest->get($pump->id);
            return (object) [
                'pump_no' => $pump->pump_no,
                'pump_name' => $pump->name,
                'current_meter' => (float) $pump->current_meter,
                'pump_status' => $pump->status,
                'shift_no' => $assignment?->shift?->shift_no,
                'operator_name' => $assignment?->operator?->name,
                'assignment_status' => $assignment?->status ?: 'unassigned',
                'opening_meter' => $assignment?->opening_meter,
                'closing_meter' => $assignment?->closing_meter,
            ];
        });
    }

    public function storeOperator(array $data): PdirectnewOperator
    {
        $businessId = $this->context->requireBusiness();
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        $this->validateLinkedUser($data['user_id'] ?? null, $businessId);

        $data['business_id'] = $businessId;
        $data['operator_no'] = trim((string) ($data['operator_no'] ?? ''))
            ?: $this->numbers->next('operator', $data['location_id'] ?? null, 'OP-');
        $data['status'] = !empty($data['is_active']) ? 'active' : 'inactive';
        $data['is_active'] = !empty($data['is_active']);
        $data['can_login'] = !empty($data['can_login']);
        $data['is_default'] = !empty($data['is_default']);
        $data['can_fullscreen'] = !empty($data['can_fullscreen']);
        $data['hide_in_direct_settlement_if_pending_shifts'] = !empty($data['hide_in_direct_settlement_if_pending_shifts']);
        $data['commission_type'] = $data['commission_type'] ?? 'none';
        $data['commission_value'] = $data['commission_value'] ?? 0;
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['short_amount'] = $data['short_amount'] ?? 0;
        $data['excess_amount'] = $data['excess_amount'] ?? 0;
        $data['metadata'] = ['created_in' => 'petro_direct_new'];

        if (!empty($data['passcode'])) {
            $data['passcode_hash'] = Hash::make((string) $data['passcode']);
        }
        unset($data['passcode']);

        abort_if(
            PdirectnewOperator::where('business_id', $businessId)->where('operator_no', $data['operator_no'])->exists(),
            422,
            'The operator number is already in use.'
        );

        $operator = PdirectnewOperator::create($data);
        $this->audit->record('created', 'operator', $operator->id, [], $operator->toArray());
        return $operator;
    }

    public function updateOperator(PdirectnewOperator $operator, array $data): PdirectnewOperator
    {
        abort_unless((int) $operator->business_id === $this->context->requireBusiness(), 404);
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        $this->validateLinkedUser($data['user_id'] ?? null, (int) $operator->business_id);

        $before = $operator->toArray();
        $operatorNo = trim((string) ($data['operator_no'] ?? $operator->operator_no));
        abort_if(
            PdirectnewOperator::where('business_id', $operator->business_id)
                ->where('operator_no', $operatorNo)
                ->where('id', '<>', $operator->id)
                ->exists(),
            422,
            'The operator number is already in use.'
        );

        $data['operator_no'] = $operatorNo;
        $data['status'] = !empty($data['is_active']) ? 'active' : 'inactive';
        foreach (['is_active', 'can_login', 'is_default', 'can_fullscreen', 'hide_in_direct_settlement_if_pending_shifts'] as $flag) {
            $data[$flag] = !empty($data[$flag]);
        }
        $data['commission_type'] = $data['commission_type'] ?? 'none';
        $data['commission_value'] = $data['commission_value'] ?? 0;
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['short_amount'] = $data['short_amount'] ?? 0;
        $data['excess_amount'] = $data['excess_amount'] ?? 0;

        if (!empty($data['passcode'])) {
            $data['passcode_hash'] = Hash::make((string) $data['passcode']);
        }
        unset($data['passcode']);

        $metadata = (array) $operator->metadata;
        $metadata['local_override'] = true;
        $metadata['locally_updated_at'] = now()->toIso8601String();
        $data['metadata'] = $metadata;

        $operator->fill($data)->save();
        $this->audit->record('updated', 'operator', $operator->id, $before, $operator->fresh()->toArray());
        return $operator->fresh();
    }

    public function toggleOperator(PdirectnewOperator $operator): PdirectnewOperator
    {
        abort_unless((int) $operator->business_id === $this->context->requireBusiness(), 404);
        abort_unless($this->context->locationAllowed((int) ($operator->location_id ?? 0)), 403);
        $before = $operator->toArray();
        $operator->is_active = !$operator->is_active;
        $operator->status = $operator->is_active ? 'active' : 'inactive';
        $operator->save();
        $this->audit->record('status_changed', 'operator', $operator->id, $before, $operator->toArray());
        return $operator;
    }

    public function deactivateOperator(PdirectnewOperator $operator): PdirectnewOperator
    {
        abort_unless((int) $operator->business_id === $this->context->requireBusiness(), 404);
        abort_unless($this->context->locationAllowed((int) ($operator->location_id ?? 0)), 403);
        $before = $operator->toArray();
        $operator->is_active = false;
        $operator->status = 'inactive';
        $operator->can_login = false;
        $operator->save();
        $this->audit->record('deactivated', 'operator', $operator->id, $before, $operator->toArray());
        return $operator;
    }

    public function operatorDetails(int $id): object
    {
        $businessId = $this->context->requireBusiness();
        $operator = $this->operators($businessId, null)->firstWhere('id', $id);
        abort_unless($operator, 404);
        abort_unless($this->context->locationAllowed((int) ($operator->location_id ?? 0)), 403);
        return $operator;
    }

    private function validateLinkedUser(mixed $userId, int $businessId): void
    {
        $userId = (int) $userId;
        if ($userId < 1) {
            return;
        }

        abort_unless(Schema::hasTable('users'), 422, 'The users table is unavailable.');
        $query = DB::table('users')->where('id', $userId);
        if (Schema::hasColumn('users', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        abort_unless($query->exists(), 422, 'The selected linked user is outside the active business.');
    }

    public function storeTank(array $data): PdirectnewTank
    {
        $data['business_id'] = $this->context->requireBusiness();
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        $data['tank_no'] = $data['tank_no'] ?? $this->numbers->next('tank', $data['location_id'] ?? null, 'TN-');
        return PdirectnewTank::create($data);
    }

    public function storePump(array $data): PdirectnewPump
    {
        $data['business_id'] = $this->context->requireBusiness();
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        if (!empty($data['tank_id'])) {
            abort_unless(
                PdirectnewTank::where('business_id', $data['business_id'])
                    ->where('location_id', $data['location_id'])->whereKey($data['tank_id'])->exists(),
                422,
                'Selected tank is outside the active business/location.'
            );
        }
        $data['pump_no'] = $data['pump_no'] ?? $this->numbers->next('pump', $data['location_id'] ?? null, 'PM-');
        $data['current_meter'] = $data['opening_meter'] ?? 0;
        return PdirectnewPump::create($data);
    }

    public function assign(array $data): PdirectnewAssignment
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
            abort_unless(
                PdirectnewOperator::where('business_id', $businessId)->whereKey($data['operator_id'])
                    ->where(function ($q) use ($data) {
                        $q->where('location_id', $data['location_id'])->orWhereNull('location_id');
                    })->where('is_active', 1)->exists(),
                422,
                'Operator is outside the active business/location or inactive.'
            );

            $shiftId = $data['shift_id'] ?? null;
            if (!$shiftId) {
                $shift = PdirectnewShift::create([
                    'business_id' => $businessId,
                    'location_id' => $data['location_id'],
                    'shift_no' => $this->numbers->next('shift', $data['location_id'], 'SH-'),
                    'operator_id' => $data['operator_id'],
                    'opened_at' => Carbon::now(),
                    'status' => 'open',
                    'created_by' => $this->context->userId(),
                ]);
                $shiftId = $shift->id;
            } else {
                abort_unless(
                    PdirectnewShift::where('business_id', $businessId)->where('location_id', $data['location_id'])
                        ->whereKey($shiftId)->whereIn('status', ['open', 'active'])->exists(),
                    422,
                    'The selected shift is not open for this location.'
                );
            }

            $pump = PdirectnewPump::where('business_id', $businessId)
                ->where('location_id', $data['location_id'])->where('status', 'active')
                ->lockForUpdate()->findOrFail($data['pump_id']);

            abort_if(
                PdirectnewAssignment::where('business_id', $businessId)->where('pump_id', $pump->id)
                    ->whereIn('status', ['assigned', 'received'])->exists(),
                422,
                'This pump already has an open assignment.'
            );

            return PdirectnewAssignment::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'],
                'shift_id' => $shiftId,
                'pump_id' => $pump->id,
                'operator_id' => $data['operator_id'],
                'opening_meter' => $pump->current_meter,
                'status' => 'assigned',
            ]);
        });
    }

    public function recordMeter(array $data): PdirectnewMeterReading
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
            $pump = PdirectnewPump::where('business_id', $businessId)->where('location_id', $data['location_id'])
                ->lockForUpdate()->findOrFail($data['pump_id']);
            $reading = (float) $data['reading'];
            abort_if($reading < (float) $pump->current_meter, 422, 'Meter reading cannot be below the current meter.');
            $entry = PdirectnewMeterReading::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'],
                'shift_id' => $data['shift_id'] ?? null,
                'assignment_id' => $data['assignment_id'] ?? null,
                'pump_id' => $pump->id,
                'operator_id' => $data['operator_id'] ?? null,
                'reading_type' => $data['reading_type'] ?? 'current',
                'reading' => $reading,
                'testing_qty' => (float) ($data['testing_qty'] ?? 0),
                'recorded_at' => Carbon::now(),
                'created_by' => $this->context->userId(),
            ]);
            $pump->current_meter = $reading;
            $pump->save();
            return $entry;
        });
    }

    public function closeAssignment(PdirectnewAssignment $assignment, array $data): PdirectnewAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $assignment = PdirectnewAssignment::where('business_id', $this->context->requireBusiness())
                ->lockForUpdate()->findOrFail($assignment->id);
            $closing = (float) $data['closing_meter'];
            abort_if($closing < (float) $assignment->opening_meter, 422, 'Closing meter cannot be below opening meter.');
            $testing = (float) ($data['testing_qty'] ?? 0);
            $sold = max(0, $closing - (float) $assignment->opening_meter - $testing);
            $price = (float) ($data['unit_price'] ?? 0);
            $assignment->fill([
                'closing_meter' => $closing,
                'testing_qty' => $testing,
                'sold_qty' => $sold,
                'unit_price' => $price,
                'sales_amount' => $sold * $price,
                'status' => 'closed',
                'closed_at' => Carbon::now(),
            ])->save();
            $pump = PdirectnewPump::where('business_id', $assignment->business_id)->lockForUpdate()->find($assignment->pump_id);
            if ($pump) {
                $pump->current_meter = $closing;
                $pump->save();
            }
            return $assignment;
        });
    }

    public function storeAdjustment(array $data): PdirectnewAdjustment
    {
        $businessId = $this->context->requireBusiness();
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        abort_unless(
            PdirectnewOperator::where('business_id', $businessId)->whereKey($data['operator_id'])
                ->where(function ($q) use ($data) {
                    $q->where('location_id', $data['location_id'])->orWhereNull('location_id');
                })->exists(),
            422,
            'Operator is outside the active business/location.'
        );
        $record = PdirectnewAdjustment::create([
            'business_id' => $businessId,
            'location_id' => $data['location_id'],
            'settlement_id' => $data['settlement_id'] ?? null,
            'operator_id' => $data['operator_id'],
            'adjustment_type' => $data['adjustment_type'],
            'amount' => abs((float) $data['amount']),
            'reason' => $data['reason'],
            'status' => $data['status'] ?? 'approved',
            'requested_by' => $this->context->userId(),
            'approved_by' => ($data['status'] ?? 'approved') === 'approved' ? $this->context->userId() : null,
            'approved_at' => ($data['status'] ?? 'approved') === 'approved' ? Carbon::now() : null,
        ]);
        $this->audit->record('created', 'adjustment', $record->id, [], $record->toArray());
        return $record;
    }

    public function storePumperDayEntry(array $data): PdirectnewPumperDayEntry
    {
        $businessId = $this->context->requireBusiness();
        abort_unless($this->context->locationAllowed((int) $data['location_id']), 403);
        abort_unless(PdirectnewOperator::where('business_id', $businessId)->whereKey($data['operator_id'])->exists(), 422);
        if (!empty($data['shift_id'])) {
            abort_unless(PdirectnewShift::where('business_id', $businessId)->whereKey($data['shift_id'])->exists(), 422);
        }
        return PdirectnewPumperDayEntry::create([
            'business_id' => $businessId,
            'location_id' => $data['location_id'],
            'operator_id' => $data['operator_id'],
            'shift_id' => $data['shift_id'] ?? null,
            'entry_date' => $data['entry_date'],
            'entry_type' => $data['entry_type'] ?? 'general',
            'reference_no' => $data['reference_no'] ?? null,
            'amount' => (float) ($data['amount'] ?? 0),
            'quantity' => (float) ($data['quantity'] ?? 0),
            'note' => $data['note'] ?? null,
            'created_by' => $this->context->userId(),
        ]);
    }

    public function storeUnloadStock(array $data): PdirectnewUnloadStock
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            $locationId = (int) $data['location_id'];
            abort_unless($this->context->locationAllowed($locationId), 403);

            $lines = collect($data['lines'] ?? [])->filter(fn ($line) => !empty($line['tank_id']) && (float) ($line['quantity'] ?? 0) > 0);
            abort_if($lines->isEmpty(), 422, 'At least one unload-stock line is required.');

            $record = PdirectnewUnloadStock::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'unload_no' => $this->numbers->next('unload_stock', $locationId, 'US-'),
                'operator_id' => $data['operator_id'] ?? null,
                'shift_id' => $data['shift_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'unload_date' => $data['unload_date'],
                'total_qty' => 0,
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'created_by' => $this->context->userId(),
            ]);

            $total = 0.0;
            foreach ($lines as $line) {
                $tank = PdirectnewTank::where('business_id', $businessId)->where('location_id', $locationId)
                    ->lockForUpdate()->findOrFail($line['tank_id']);
                $quantity = (float) $line['quantity'];
                $unitCost = (float) ($line['unit_cost'] ?? 0);
                $record->lines()->create([
                    'tank_id' => $tank->id,
                    'product_id' => $tank->product_id ?: ($line['product_id'] ?? null),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $quantity * $unitCost,
                ]);
                $tank->current_stock = (float) $tank->current_stock + $quantity;
                $tank->save();
                $total += $quantity;
            }
            $record->total_qty = $total;
            $record->save();
            return $record;
        });
    }

    public function storeDipReading(array $data): PdirectnewDipReading
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            $tank = PdirectnewTank::where('business_id', $businessId)->lockForUpdate()->findOrFail($data['tank_id']);
            $calculated = (float) ($data['calculated_stock'] ?? $data['actual_stock']);
            $actual = (float) $data['actual_stock'];
            $reading = PdirectnewDipReading::create([
                'business_id' => $businessId,
                'location_id' => $tank->location_id,
                'tank_id' => $tank->id,
                'dip_chart_id' => $data['dip_chart_id'] ?? null,
                'reading_date' => $data['reading_date'],
                'dip_value' => (float) $data['dip_value'],
                'calculated_stock' => $calculated,
                'actual_stock' => $actual,
                'variance' => $actual - $calculated,
                'note' => $data['note'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
            $tank->current_stock = $actual;
            $tank->save();
            return $reading;
        });
    }

    public function storeTankTransfer(array $data): PdirectnewTankTransfer
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            $from = PdirectnewTank::where('business_id', $businessId)->lockForUpdate()->findOrFail($data['from_tank_id']);
            $to = PdirectnewTank::where('business_id', $businessId)->lockForUpdate()->findOrFail($data['to_tank_id']);
            abort_if($from->id === $to->id, 422, 'From and To tanks must be different.');
            abort_if($from->location_id != $to->location_id, 422, 'Both tanks must belong to the same business location.');
            if ($from->product_id && $to->product_id) {
                abort_if($from->product_id != $to->product_id, 422, 'Tank products do not match.');
            }
            $qty = (float) $data['quantity'];
            abort_if($qty > (float) $from->current_stock, 422, 'Insufficient stock in source tank.');
            $from->current_stock = (float) $from->current_stock - $qty;
            $to->current_stock = (float) $to->current_stock + $qty;
            $from->save();
            $to->save();
            return PdirectnewTankTransfer::create([
                'business_id' => $businessId,
                'location_id' => $from->location_id,
                'transfer_no' => $this->numbers->next('tank_transfer', $from->location_id, 'TT-'),
                'from_tank_id' => $from->id,
                'to_tank_id' => $to->id,
                'product_id' => $from->product_id ?: $to->product_id,
                'quantity' => $qty,
                'transfer_date' => $data['transfer_date'],
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
        });
    }

    public function generateCollection(array $data): PdirectnewDailyCollection
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            $locationId = (int) $data['location_id'];
            abort_unless($this->context->locationAllowed($locationId), 403);
            $date = $data['collection_date'];
            $operatorId = $data['operator_id'] ?? null;
            $shiftId = $data['shift_id'] ?? null;
            $settlements = PdirectnewSettlement::query()
                ->where('business_id', $businessId)->where('location_id', $locationId)
                ->whereDate('transaction_date', $date)->where('status', 'finalized')
                ->when($operatorId, fn ($q) => $q->where('operator_id', $operatorId))
                ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId))->get();
            $payments = PdirectnewSettlementPayment::query()
                ->whereIn('settlement_id', $settlements->pluck('id'))->where('status', 'active')->get();
            $sum = fn ($types) => (float) $payments->whereIn('payment_type', (array) $types)->sum('amount');
            $cash = $sum('cash');
            $card = $sum(['card', 'pos_sale']);
            $cheque = $sum('cheque');
            $credit = $sum('credit_sale');
            $shortage = $sum('shortage');
            $excess = $sum('excess');
            $other = (float) $payments->reject(fn ($p) => in_array($p->payment_type, ['cash', 'card', 'pos_sale', 'cheque', 'credit_sale', 'shortage', 'excess'], true))->sum('amount');
            $collection = PdirectnewDailyCollection::firstOrNew([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'collection_date' => $date,
                'operator_id' => $operatorId,
                'shift_id' => $shiftId,
            ]);
            if (!$collection->exists) {
                $collection->collection_no = $this->numbers->next('collection', $locationId, 'DC-');
            }
            $collection->fill([
                'status' => 'draft',
                'cash_total' => $cash,
                'card_total' => $card,
                'cheque_total' => $cheque,
                'credit_total' => $credit,
                'other_total' => $other,
                'shortage' => $shortage,
                'excess' => $excess,
                'grand_total' => $cash + $card + $cheque + $credit + $other,
                'note' => $data['note'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
            $collection->save();
            return $collection;
        });
    }

    public function closeShift(PdirectnewShift $shift): PdirectnewShift
    {
        $shift = PdirectnewShift::where('business_id', $this->context->requireBusiness())->findOrFail($shift->id);
        abort_if($shift->assignments()->whereIn('status', ['assigned', 'received'])->exists(), 422, 'All assigned pumps must be closed first.');
        $shift->status = 'closed';
        $shift->closed_at = Carbon::now();
        $shift->closed_by = $this->context->userId();
        $shift->save();
        return $shift;
    }
}
