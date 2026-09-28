<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\PumpOperatorAssignment;

class PDPumpOperatorAssignmentController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function create()
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizePetroPD($business_id);

        $shift_number = $this->nextShiftNumber($business_id) - 1;

        $work_shifts = $this->workShiftDropdown($business_id);
        $open_shift_assignments = $this->openShiftAssignments($business_id);
        $assigned_pump_ids = $open_shift_assignments->pluck('pump_id')->map(function ($id) { return (int) $id; })->filter()->unique()->values()->all();
        $assigned_operator_ids = $open_shift_assignments->pluck('pump_operator_id')->map(function ($id) { return (int) $id; })->filter()->unique()->values()->all();

        $pumps = $this->availablePumpsDropdown($business_id, [], $assigned_pump_ids);
        $selected_pump_ids = [];
        $pump_operator_records = $this->pumpOperatorsCollection($business_id, '', true)->values();
        $available_pump_operators = $pump_operator_records->reject(function ($operator) use ($assigned_operator_ids) {
            return in_array((int) $operator->id, $assigned_operator_ids, true);
        })->values();
        $assign_pump_operators = $available_pump_operators->pluck('name', 'id');
        $pump_operators = $available_pump_operators;
        $pump_assignments = $open_shift_assignments;

        return view('petropd::pd_operators.partials.bulk_pumper_assignment')
            ->with(compact(
                'shift_number',
                'work_shifts',
                'pumps',
                'selected_pump_ids',
                'pump_operators',
                'pump_operator_records',
                'available_pump_operators',
                'assign_pump_operators',
                'open_shift_assignments',
                'pump_assignments'
            ));
    }

    public function getPumperAssignment(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizePetroPD($business_id);

        $pumpId = (int) $request->pump_id;
        $shift_number = $this->nextShiftNumber($business_id) - 1;

        $work_shifts = $this->workShiftDropdown($business_id);
        $open_shift_assignments = $this->openShiftAssignments($business_id);
        $assigned_pump_ids = $open_shift_assignments->pluck('pump_id')->map(function ($id) { return (int) $id; })->filter()->unique()->values()->all();
        $assigned_operator_ids = $open_shift_assignments->pluck('pump_operator_id')->map(function ($id) { return (int) $id; })->filter()->unique()->values()->all();

        $pumps = $this->availablePumpsDropdown($business_id, [$pumpId], $assigned_pump_ids);
        $selected_pump_ids = $pumpId > 0 ? [$pumpId] : [];
        $pump_operator_records = $this->pumpOperatorsCollection($business_id)->values();
        $available_pump_operators = $pump_operator_records->reject(function ($operator) use ($assigned_operator_ids) {
            return in_array((int) $operator->id, $assigned_operator_ids, true);
        })->values();
        $assign_pump_operators = $available_pump_operators->pluck('name', 'id');
        $pump_operators = $available_pump_operators;
        $pump_assignments = $open_shift_assignments;

        return view('petropd::pd_operators.partials.bulk_pumper_assignment')
            ->with(compact(
                'shift_number',
                'work_shifts',
                'pumps',
                'selected_pump_ids',
                'pump_operators',
                'pump_operator_records',
                'available_pump_operators',
                'assign_pump_operators',
                'open_shift_assignments',
                'pump_assignments'
            ));
    }

    public function storeBulk(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizePetroPD($business_id);

        $request->validate([
            'date' => 'required',
            'pump_operator' => 'required|integer',
            'pump' => 'required|array|min:1',
            'pump.*' => 'integer',
        ]);

        try {
            $operatorQuery = DB::table('pump_operators')
                ->where('id', (int) $request->pump_operator);

            if (Schema::hasColumn('pump_operators', 'deleted_at')) {
                $operatorQuery->whereNull('deleted_at');
            }

            $operator = $operatorQuery->first();

            if (empty($operator)) {
                return $this->redirectBack(false, 'Selected pump operator was not found.');
            }

            $pumpIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->pump))));
            $validPumpQuery = DB::table('pumps')
                ->whereIn('id', $pumpIds);

            if (Schema::hasColumn('pumps', 'deleted_at')) {
                $validPumpQuery->whereNull('deleted_at');
            }

            $validPumpIds = $validPumpQuery
                ->pluck('id')
                ->map(function ($id) { return (int) $id; })
                ->all();

            if (empty($validPumpIds)) {
                return $this->redirectBack(false, 'Selected pumps were not found for this business.');
            }

            $operatorAlreadyOpen = DB::table('pump_operator_assignments')
                ->where('business_id', $business_id)
                ->where('pump_operator_id', (int) $request->pump_operator)
                ->where(function ($q) {
                    $q->where('status', 'open')
                      ->orWhereNull('status')
                      ->orWhere(function ($qq) {
                          $qq->whereNotNull('status')->where('status', '!=', 'close');
                      });
                })
                ->exists();

            if ($operatorAlreadyOpen) {
                return $this->redirectBack(false, 'Selected pump operator is already assigned to an open shift. Please close the current shift first.');
            }

            $alreadyOpen = DB::table('pump_operator_assignments')
                ->where('business_id', $business_id)
                ->whereIn('pump_id', $validPumpIds)
                ->where(function ($q) {
                    $q->where('status', 'open')
                      ->orWhereNull('status')
                      ->orWhere(function ($qq) {
                          $qq->whereNotNull('status')->where('status', '!=', 'close');
                      });
                })
                ->pluck('pump_id')
                ->map(function ($id) { return (int) $id; })
                ->all();

            $assignPumpIds = array_values(array_diff($validPumpIds, $alreadyOpen));
            if (empty($assignPumpIds)) {
                return $this->redirectBack(false, 'Selected pump(s) are already assigned in an open shift.');
            }

            $date = $this->parseDate($request->date);
            $nextShiftNumber = $this->nextShiftNumber($business_id);

            DB::beginTransaction();

            $shiftId = $this->createPetroShift($business_id, (int) $request->pump_operator, $nextShiftNumber, $request->work_shift, $date);
            $now = now();

            foreach ($assignPumpIds as $pumpId) {
                $row = [
                    'business_id' => $business_id,
                    'pump_operator_id' => (int) $request->pump_operator,
                    'pump_id' => $pumpId,
                    'shift_number' => $nextShiftNumber,
                    'status' => 'open',
                    'is_confirmed' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $this->setIfColumnExists('pump_operator_assignments', $row, 'shift_id', $shiftId);
                $this->setIfColumnExists('pump_operator_assignments', $row, 'work_shift_id', $request->work_shift ?: null);
                $this->setIfColumnExists('pump_operator_assignments', $row, 'date_and_time', $date);
                $this->setIfColumnExists('pump_operator_assignments', $row, 'transaction_date', $date->toDateString());
                $this->setIfColumnExists('pump_operator_assignments', $row, 'assignment_date', $date->toDateString());
                $this->setIfColumnExists('pump_operator_assignments', $row, 'created_by', Auth::id());
                $this->setIfColumnExists('pump_operator_assignments', $row, 'user_added', Auth::id());
                $this->setIfColumnExists('pump_operator_assignments', $row, 'closed_in_settlement', 0);
                $this->setIfColumnExists('pump_operator_assignments', $row, 'is_manually_closed', 0);

                DB::table('pump_operator_assignments')->insert($row);
            }

            DB::commit();

            return $this->redirectBack(true, count($assignPumpIds) . ' pump(s) assigned successfully to ' . ($operator->name ?? 'selected operator') . '.', 'daily_pump_status');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PetroPD assign pumps failed', [
                'business_id' => $business_id,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->redirectBack(false, 'Unable to assign pumps. Please check selected operator and pumps.');
        }
    }

    public function operatorsForSelect(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizePetroPD($business_id);

        $term = trim((string) $request->get('q', $request->get('term', '')));
        $operators = $this->pumpOperatorsCollection($business_id, $term)
            ->map(function ($operator) { return ['id' => $operator->id, 'text' => $operator->name]; })
            ->values();

        return response()->json(['results' => $operators]);
    }


    /**
     * S311: Next shift number must follow the last completed PD settlement/shift
     * in the current tenant database. Use several known sources defensively, then
     * return the next available number. This avoids old copied tenant data or open
     * assignments showing a stale/incorrect next shift number.
     */
    protected function nextShiftNumber(int $business_id): int
    {
        $max = 0;

        foreach ([
            ['table' => 'settlements', 'columns' => ['shift_number', 'shift_no']],
            ['table' => 'petro_shifts', 'columns' => ['shift_number', 'shift_no']],
            ['table' => 'pump_operator_assignments', 'columns' => ['shift_number', 'shift_no']],
        ] as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            foreach ($source['columns'] as $column) {
                if (! Schema::hasColumn($source['table'], $column)) {
                    continue;
                }

                $query = DB::table($source['table']);
                if (Schema::hasColumn($source['table'], 'business_id')) {
                    $query->where('business_id', $business_id);
                }
                if (Schema::hasColumn($source['table'], 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }

                // Completed/finalized settlements are the authoritative base, but
                // include assignment/open shift max so we never duplicate a live shift.
                if ($source['table'] === 'settlements' && Schema::hasColumn('settlements', 'status')) {
                    $query->where(function ($q) {
                        $q->where('status', 0)->orWhere('status', 'completed')->orWhere('status', 'finalized');
                    });
                }

                $value = (int) $query->max(DB::raw('CAST(' . $column . ' AS UNSIGNED)'));
                $max = max($max, $value);
            }
        }

        return $max + 1;
    }

    protected function authorizePetroPD($business_id): void
    {
        $user = Auth::user();

        if (empty($user)) {
            abort(403, 'Unauthorized Access');
        }

        if (! $user->can('superadmin') && ! $user->can('bulk_assign_pumps')) {
            abort(403, 'Unauthorized Access');
        }

        /*
         * IS1733:
         * Access to every /petropd route is already controlled by
         * EnsurePetroPDAccess. That middleware currently applies the system's
         * PetroPD recovery/subscription policy and separately enforces
         * bulk_assign_pumps for these assignment routes.
         *
         * Rechecking petro_pd_module here used a different subscription path
         * from the page middleware. Therefore the Daily Pump Status page could
         * correctly display Assign Pumps for an authorised user, while this
         * AJAX modal request was rejected with HTTP 403. Keep the page-level
         * permission check above, but do not add a conflicting second module
         * subscription gate inside this controller.
         */
    }

    protected function authorizeAssignmentMaintenance(string $permission): void
    {
        $user = Auth::user();

        // IS1799: Daily Pump Status assignment maintenance is reserved for
        // Super Admin. Business and Business Admin users may still assign new
        // pumps through the separate create/bulk-assignment actions.
        if (empty($user) || ! $user->can('superadmin')) {
            abort(403, 'Unauthorized Access');
        }
    }

    protected function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $business_id;
    }

    protected function pumpOperatorsCollection(int $business_id, string $term = '', bool $excludeOpenShifts = false)
    {
        /*
         * Tenant-safe operator loader.
         *
         * Some live tenant databases are restored/copied with pump_operators.business_id
         * values that do not match the currently logged-in business_id. Filtering only
         * by Auth::user()->business_id makes the Assign Pumps dropdown empty even
         * though the tenant database has operators. The dropdown must show operators
         * from the current tenant database first, and only use business_id as a
         * preferred filter when it returns rows.
         */
        if (!Schema::hasTable('pump_operators')) {
            return collect();
        }

        $nameColumn = $this->firstExistingColumn('pump_operators', [
            'name', 'operator_name', 'pump_operator_name', 'username', 'first_name', 'id'
        ]);

        $select = ['id'];
        if ($nameColumn === 'id') {
            $select[] = DB::raw("CONCAT('Operator ', id) as name");
        } elseif ($nameColumn === 'first_name' && Schema::hasColumn('pump_operators', 'last_name')) {
            $select[] = DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as name");
        } else {
            $select[] = DB::raw($nameColumn . ' as name');
        }

        $build = function ($filterBusiness) use ($business_id, $term, $nameColumn, $select, $excludeOpenShifts) {
            $query = DB::table('pump_operators');

            if ($filterBusiness && Schema::hasColumn('pump_operators', 'business_id')) {
                $query->where('business_id', $business_id);
            }

            if (Schema::hasColumn('pump_operators', 'deleted_at')) {
            if ($excludeOpenShifts) { $query->whereNotExists(function ($q) use ($business_id) { $q->select(DB::raw(1))->from("pump_operator_assignments as a")->whereColumn("a.pump_operator_id", "pump_operators.id")->where("a.business_id", $business_id)->where("a.status", "open")->whereNull("a.settlement_id"); }); }
                $query->whereNull('deleted_at');
            }

            if ($term !== '' && $nameColumn !== 'id') {
                $query->where($nameColumn, 'like', '%' . $term . '%');
            }

            return $query->select($select)->orderBy('name')->get()
                ->filter(function ($row) {
                    return !empty($row->id) && trim((string) ($row->name ?? '')) !== '';
                })->values();
        };

        $rows = $build(true);

        // Fallback for tenant databases where business_id values do not match.
        if ($rows->isEmpty()) {
            $rows = $build(false);
        }

        return $rows;
    }

    protected function availablePumpsDropdown(int $business_id, array $forceInclude = [], array $excludeIds = [])
    {
        /*
         * S312 final root-cause fix:
         *
         * The Assign Pumps modal must not depend on Select2/AJAX or a filtered
         * query that can return zero rows while the Daily Pump Status page already
         * shows pumps for the same tenant.  Load pumps directly from the tenant DB,
         * prefer the same label visible on the pump cards (pump_no), and only use
         * business_id as a preferred filter.  If copied tenant data has mismatched
         * business_id values, fallback to all tenant pumps so the dropdown is never
         * empty when pumps exist in the tenant database.
         */
        if (!Schema::hasTable('pumps')) {
            return collect();
        }

        $forceInclude = array_values(array_unique(array_filter(array_map('intval', $forceInclude))));
        $excludeIds = array_values(array_diff(array_unique(array_filter(array_map('intval', $excludeIds))), $forceInclude));

        $labelColumns = array_values(array_filter([
            Schema::hasColumn('pumps', 'pump_no') ? 'pump_no' : null,
            Schema::hasColumn('pumps', 'pump_number') ? 'pump_number' : null,
            Schema::hasColumn('pumps', 'pump_name') ? 'pump_name' : null,
            Schema::hasColumn('pumps', 'name') ? 'name' : null,
            Schema::hasColumn('pumps', 'code') ? 'code' : null,
        ]));

        $load = function (bool $filterBusiness) use ($business_id, $forceInclude, $excludeIds, $labelColumns) {
            $query = DB::table('pumps');

            if ($filterBusiness && Schema::hasColumn('pumps', 'business_id')) {
                $query->where(function ($q) use ($business_id, $forceInclude) {
                    $q->where('business_id', $business_id);
                    if (!empty($forceInclude)) {
                        $q->orWhereIn('id', $forceInclude);
                    }
                });
            }

            if (Schema::hasColumn('pumps', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if (!empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }

            // Do not hide normal pumps because of legacy nullable flags; only remove
            // definite other-sales pumps when the column is present.
            if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                $query->where(function ($q) {
                    $q->where('is_other_sales_pump', 0)->orWhereNull('is_other_sales_pump');
                });
            }

            $select = ['id'];
            foreach ($labelColumns as $column) {
                $select[] = $column;
            }

            return $query->select($select)->orderBy('id')->get();
        };

        $rows = $load(true);
        if ($rows->isEmpty()) {
            $rows = $load(false);
        }

        return $rows->mapWithKeys(function ($pump) use ($labelColumns) {
            $label = '';
            foreach ($labelColumns as $column) {
                $value = trim((string) ($pump->{$column} ?? ''));
                if ($value !== '') {
                    $label = $value;
                    break;
                }
            }

            return [(int) $pump->id => ($label !== '' ? $label : ('Pump ' . (int) $pump->id))];
        })->filter(function ($label, $id) {
            return (int) $id > 0 && trim((string) $label) !== '';
        });
    }

    protected function firstExistingColumn(string $table, array $columns): string
    {
        foreach ($columns as $column) {
            if ($column === 'id' || Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return 'id';
    }

    protected function workShiftDropdown(int $business_id)
    {
        if (Schema::hasTable('work_shifts')) {
            $label = Schema::hasColumn('work_shifts', 'shift_name') ? 'shift_name' : (Schema::hasColumn('work_shifts', 'name') ? 'name' : 'id');
            return DB::table('work_shifts')->where('business_id', $business_id)->orderBy($label)->pluck($label, 'id');
        }

        return collect();
    }

    protected function openShiftAssignments(int $business_id)
    {
        $pumpName = Schema::hasColumn('pumps', 'pump_no') ? 'pumps.pump_no' : (Schema::hasColumn('pumps', 'pump_name') ? 'pumps.pump_name' : 'pumps.id');

        $query = PumpOperatorAssignment::from('pump_operator_assignments as poa')
            ->leftJoin('pump_operators as po', 'po.id', '=', 'poa.pump_operator_id')
            ->leftJoin('pumps', 'pumps.id', '=', 'poa.pump_id')
            ->where('poa.business_id', $business_id)
            ->where('poa.status', 'open')
            ->select([
                'poa.*',
                'po.name as pumper_name',
                DB::raw($pumpName . ' as pump_name'),
            ])
            ->orderByRaw('CAST(poa.shift_number AS UNSIGNED) DESC')
            ->orderByDesc('poa.id');

        if (Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            $query->whereNull('poa.settlement_id');
        }

        if (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
            $query->where(function ($query) {
                $query->whereNull('poa.closed_in_settlement')
                    ->orWhere('poa.closed_in_settlement', 0);
            });
        }

        if (Schema::hasColumn('pump_operator_assignments', 'close_date_and_time')) {
            $query->whereNull('poa.close_date_and_time');
        }

        return $query->get();
    }

    protected function createPetroShift(int $business_id, int $pump_operator_id, int $shift_number, $work_shift_id, Carbon $date): ?int
    {
        if (!Schema::hasTable('petro_shifts')) {
            return null;
        }

        $row = [
            'business_id' => $business_id,
            'pump_operator_id' => $pump_operator_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->setIfColumnExists('petro_shifts', $row, 'shift_number', $shift_number);
        $this->setIfColumnExists('petro_shifts', $row, 'shift_no', $shift_number);
        $this->setIfColumnExists('petro_shifts', $row, 'work_shift_id', $work_shift_id ?: null);
        $this->setIfColumnExists('petro_shifts', $row, 'date', $date->toDateString());
        $this->setIfColumnExists('petro_shifts', $row, 'transaction_date', $date->toDateString());
        $this->setIfColumnExists('petro_shifts', $row, 'start_date_and_time', $date);
        $this->setIfColumnExists('petro_shifts', $row, 'date_and_time', $date);
        $this->setIfColumnExists('petro_shifts', $row, 'status', 'open');
        $this->setIfColumnExists('petro_shifts', $row, 'created_by', Auth::id());

        return DB::table('petro_shifts')->insertGetId($row);
    }

    protected function setIfColumnExists(string $table, array &$row, string $column, $value): void
    {
        if (Schema::hasColumn($table, $column)) {
            $row[$column] = $value;
        }
    }

    /**
     * Permit a legacy copied-tenant row only when the table has no rows for the
     * active business at all. This matches the dropdown fallback without opening
     * cross-business access in a correctly populated tenant.
     */
    protected function recordAvailableForBusiness(string $table, int $id, int $business_id): bool
    {
        if (! Schema::hasTable($table) || ! DB::table($table)->where('id', $id)->exists()) {
            return false;
        }

        if (! Schema::hasColumn($table, 'business_id')) {
            return true;
        }

        if (DB::table($table)->where('id', $id)->where('business_id', $business_id)->exists()) {
            return true;
        }

        return ! DB::table($table)->where('business_id', $business_id)->exists();
    }

    protected function parseDate($value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return now();
        }
    }

    protected function redirectBack(bool $success, string $message, string $tab = 'daily_pump_status')
    {
        return redirect()->back()->with('status', [
            'success' => $success ? 1 : 0,
            'msg' => $message,
            'tab' => $tab,
        ]);
    }

    /**
     * Edit only an unreceived, open assignment from the active business.
     */
    public function edit($id)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizeAssignmentMaintenance('daily_pump_status.edit');

        $pump_assignment = PumpOperatorAssignment::with('Shift')
            ->where('business_id', $business_id)
            ->findOrFail((int) $id);

        $this->ensureAssignmentIsEditable($pump_assignment);

        $pump_operators = $this->pumpOperatorsCollection($business_id)->pluck('name', 'id');
        $pumps = $this->availablePumpsDropdown(
            $business_id,
            [(int) $pump_assignment->pump_id],
            $this->openShiftAssignments($business_id)
                ->where('id', '<>', (int) $pump_assignment->id)
                ->pluck('pump_id')
                ->map(fn ($pumpId) => (int) $pumpId)
                ->all()
        );

        $layout = empty(session()->get('pump_operator_main_system')) ? 'pumper' : 'app';

        return view('petropd::pd_operators.partials.pumper_assignment_edit')
            ->with(compact('pump_assignment', 'layout', 'pump_operators', 'pumps'));
    }

    /**
     * Reject edits after a pump operator has received the assignment or after
     * its shift/assignment has been closed.
     */
    protected function ensureAssignmentIsEditable(PumpOperatorAssignment $assignment): void
    {
        if (! empty($assignment->is_confirmed) || ! empty($assignment->confirmed_at)) {
            abort(403, __('petropd::lang.edit_not_allowed_after_receive'));
        }

        if (in_array(strtolower(trim((string) $assignment->status)), ['close', 'closed'], true)) {
            abort(403, __('petropd::lang.edit_not_allowed_shift_closed'));
        }

        $shift = $assignment->relationLoaded('Shift')
            ? $assignment->Shift
            : $assignment->Shift()->first();

        if ($shift && in_array(strtolower(trim((string) $shift->status)), ['2', 'close', 'closed', 'finalized', 'finalised'], true)) {
            abort(403, __('petropd::lang.edit_not_allowed_shift_closed'));
        }
    }

    /**
     * Legacy form compatibility route for PetroPD receive/add pump forms.
     * Keeps old action() calls inside PetroPD and updates only tenant rows.
     */
    public function store(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizePetroPD($business_id);

        try {
            DB::beginTransaction();

            $pump_operator_id = (int) $request->input('pump_operator_id', Auth::user()->pump_operator_id);
            $pump_id = (int) $request->input('pump_id');
            $shift_number = $this->nextShiftNumber($business_id);
            $date = now();
            $shift_id = $this->createPetroShift($business_id, $pump_operator_id, $shift_number, $request->input('work_shift_id'), $date);

            $row = [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'pump_id' => $pump_id,
                'status' => $request->boolean('status', true) ? 'open' : 'close',
                'starting_meter' => $request->input('starting_meter'),
                'closing_meter' => $request->input('closing_meter'),
                'shift_number' => $shift_number,
                'date_and_time' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($shift_id) {
                $row['shift_id'] = $shift_id;
            }

            PumpOperatorAssignment::create($row);

            DB::commit();

            return $this->redirectBack(true, __('petropd::lang.success'), 'daily_pump_status');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::emergency('PetroPD pump assignment store failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return $this->redirectBack(false, __('messages.something_went_wrong'), 'daily_pump_status');
        }
    }

    /**
     * Legacy form compatibility update for old modal views now routed to PetroPD.
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizeAssignmentMaintenance('daily_pump_status.edit');

        try {
            $assignment = PumpOperatorAssignment::with('Shift')
                ->where('business_id', $business_id)
                ->findOrFail((int) $id);

            $this->ensureAssignmentIsEditable($assignment);

            $operatorId = (int) $request->input('pump_operator_id', $assignment->pump_operator_id);
            $pumpId = (int) $request->input('pump_id', $assignment->pump_id);

            if (! $this->recordAvailableForBusiness('pump_operators', $operatorId, $business_id)
                || ! $this->recordAvailableForBusiness('pumps', $pumpId, $business_id)) {
                return $this->redirectBack(
                    false,
                    'The selected pump operator or pump does not belong to the active business.',
                    'daily_pump_status'
                );
            }

            $duplicateOpenPump = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_id', $pumpId)
                ->where('id', '<>', (int) $assignment->id)
                ->whereNotIn('status', ['close', 'closed'])
                ->exists();

            if ($duplicateOpenPump) {
                return $this->redirectBack(
                    false,
                    'The selected pump is already assigned to another open shift.',
                    'daily_pump_status'
                );
            }

            $closingMeter = $request->input('closing_meter');
            if ($closingMeter !== null && $closingMeter !== ''
                && (float) $closingMeter < (float) $assignment->starting_meter) {
                return $this->redirectBack(
                    false,
                    __('petropd::lang.closing_meter_cannot_be_smaller'),
                    'daily_pump_status'
                );
            }

            $assignment->pump_operator_id = $operatorId;
            $assignment->pump_id = $pumpId;
            if ($request->has('closing_meter')) {
                $assignment->closing_meter = $closingMeter;
            }

            // This modal is available only before receive/close. Do not let an
            // unchecked HTML checkbox silently close an active assignment.
            $assignment->status = 'open';
            $assignment->save();

            return $this->redirectBack(true, __('petropd::lang.success'), 'daily_pump_status');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('PetroPD pump assignment update failed', [
                'business_id' => $business_id,
                'assignment_id' => (int) $id,
                'message' => $e->getMessage(),
            ]);

            return $this->redirectBack(false, __('messages.something_went_wrong'), 'daily_pump_status');
        }
    }

    /**
     * Delete only an untouched open assignment. Received, closed, settled, or
     * day-entry-linked records are protected from destructive removal.
     */
    public function destroy($id)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizeAssignmentMaintenance('daily_pump_status.delete');

        try {
            $assignment = PumpOperatorAssignment::with('Shift')
                ->where('business_id', $business_id)
                ->findOrFail((int) $id);

            $this->ensureAssignmentIsEditable($assignment);

            $hasDayEntry = Schema::hasTable('pumper_day_entries')
                && DB::table('pumper_day_entries')
                    ->where('business_id', $business_id)
                    ->where('pumper_assignment_id', (int) $assignment->id)
                    ->exists();

            $hasSettlement = ! empty($assignment->settlement_id)
                || (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')
                    && (int) $assignment->closed_in_settlement === 1);

            if ($hasDayEntry || $hasSettlement) {
                return response()->json([
                    'success' => false,
                    'msg' => 'This assignment already has operational or settlement data and cannot be deleted.',
                ]);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.success'),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('PetroPD pump assignment delete failed', [
                'business_id' => $business_id,
                'assignment_id' => (int) $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }
}
