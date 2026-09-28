<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;

/**
 * SW Operators - the module's main working page.
 *
 * Eleven tabs, following the structure PetroGeneral uses. This controller
 * serves the page and the Pump Operators tab; the other tabs are added in
 * order, each with its own data endpoint.
 *
 * SHARED vs OWNED. pump_operators, pumps and business_locations are the
 * BUSINESS's records - read here, never duplicated. SW owns only what it
 * records itself.
 */
class OperatorController extends Controller
{
    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId();

        $business_locations = $this->locationsForDropdown($businessId);
        $default_location = $business_locations->keys()->first();
        $operator_list = $this->operatorsForDropdown($businessId);

        return view('sw::operators.index', compact(
            'business_locations',
            'default_location',
            'operator_list'
        ));
    }

    /**
     * Rows for the Pump Operators table.
     *
     * Server-side: an estate with many operators should not send the whole list
     * to the browser to be filtered there.
     */
    public function data(Request $request)
    {
        $businessId = $this->businessId();

        if (! Schema::hasTable('pump_operators')) {
            return response()->json(['data' => []]);
        }

        $query = DB::table('pump_operators');

        if (Schema::hasTable('business_locations') && Schema::hasColumn('pump_operators', 'location_id')) {
            $query->leftJoin('business_locations', 'business_locations.id', '=', 'pump_operators.location_id');
        }
        $query->where('pump_operators.business_id', $businessId);
        if ($request->filled('location_id') && Schema::hasColumn('pump_operators', 'location_id')) {
            $query->where('pump_operators.location_id', (int) $request->location_id);
        }

        $cols = ['pump_operators.id', 'pump_operators.name'];
        foreach (['mobile','commission_type','commission_ap','excess_amount','short_amount','active'] as $c) {
            $cols[] = Schema::hasColumn('pump_operators', $c) ? 'pump_operators.' . $c : DB::raw('NULL as ' . $c);
        }
        $cols[] = (Schema::hasTable('business_locations') && Schema::hasColumn('pump_operators', 'location_id'))
            ? 'business_locations.name as location_name' : DB::raw('NULL as location_name');

        $rows = $query->orderBy('pump_operators.name')->get($cols);

        /*
         | S716: Date Range on SW Operators.
         |
         | Keep the operator list itself stable, but when a date range is
         | selected show Excess/Shortage activity for that period. This matches
         | the purpose of a date filter on an operator summary without hiding an
         | operator merely because they were created before the selected dates.
         | With no range selected, retain the existing current balances.
        */
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));
        $hasPeriodFilter = $startDate !== '' && $endDate !== '';

        $periodAmounts = $this->operatorPeriodAmounts($businessId, $startDate, $endDate);

        $data = $rows->map(function ($r) use ($periodAmounts, $hasPeriodFilter) {
            return [
                'action' => view('sw::operators.partials.operator_actions', ['row' => $r])->render(),
                'name' => e($r->name ?? ''),
                'location_name' => e($r->location_name ?? '—'),
                'mobile' => e($r->mobile ?? ''),
                'commission_type' => e(ucfirst((string) ($r->commission_type ?? ''))),
                'commission_ap' => number_format((float) ($r->commission_ap ?? 0), 2),
                'excess_amount' => number_format((float) ($hasPeriodFilter
                    ? ($periodAmounts[$r->id]['excess'] ?? 0)
                    : ($r->excess_amount ?? 0)), 2),
                'short_amount' => number_format((float) ($hasPeriodFilter
                    ? ($periodAmounts[$r->id]['shortage'] ?? 0)
                    : ($r->short_amount ?? 0)), 2),
                'status' => ((int) ($r->active ?? 1) === 1)
                    ? '<span class="label label-success">' . __('sw::lang.active') . '</span>'
                    : '<span class="label label-default">' . __('sw::lang.inactive') . '</span>',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Rows for the Pumper Excess / Shortage Payments tab.
     *
     * Read from pump_operator_payments, which the business already keeps. SW
     * records no second copy of these - one place for a fact.
     *
     * Note that table stores payment_amount as varchar(191) - a money value as
     * text, which cannot be summed reliably in SQL. It also carries a proper
     * decimal net_amount, so that is what is used; payment_amount is only read
     * when net_amount is absent on older rows.
     */
    public function excessShortageData(Request $request)
    {
        $businessId = $this->businessId();

        if (! Schema::hasTable('pump_operator_payments')) {
            return response()->json(['data' => []]);
        }

        $has = fn ($c) => Schema::hasColumn('pump_operator_payments', $c);
        $amount = $has('net_amount') && $has('payment_amount')
            ? 'COALESCE(NULLIF(pump_operator_payments.net_amount, 0), CAST(pump_operator_payments.payment_amount AS DECIMAL(22,4)))'
            : ($has('net_amount') ? 'pump_operator_payments.net_amount' : ($has('payment_amount') ? 'CAST(pump_operator_payments.payment_amount AS DECIMAL(22,4))' : '0'));

        $query = DB::table('pump_operator_payments');
        if (Schema::hasTable('pump_operators') && $has('pump_operator_id')) {
            $query->leftJoin('pump_operators', 'pump_operators.id', '=', 'pump_operator_payments.pump_operator_id');
        }

        if ($has('business_id')) $query->where('pump_operator_payments.business_id', $businessId);
        if ($has('payment_type')) $query->whereIn('pump_operator_payments.payment_type', ['excess', 'shortage']);
        if ($has('deleted_at')) $query->whereNull('pump_operator_payments.deleted_at');

        if ($request->filled('location_id') && Schema::hasTable('pump_operators') && Schema::hasColumn('pump_operators', 'location_id'))
            $query->where('pump_operators.location_id', (int) $request->location_id);
        if ($request->filled('pump_operator') && $has('pump_operator_id'))
            $query->where('pump_operator_payments.pump_operator_id', (int) $request->pump_operator);
        if ($request->filled('type') && $has('payment_type'))
            $query->where('pump_operator_payments.payment_type', $request->type);

        if ($request->filled('date_range') && $has('date_and_time')) {
            $parts = array_map('trim', explode('-', (string) $request->date_range));
            if (count($parts) === 2) {
                try {
                    $query->whereBetween('pump_operator_payments.date_and_time', [\Carbon\Carbon::parse($parts[0])->startOfDay(), \Carbon\Carbon::parse($parts[1])->endOfDay()]);
                } catch (\Throwable $e) {}
            }
        }

        $select = ['pump_operator_payments.id', DB::raw($amount . ' as amount_value')];
        foreach (['transaction_date','date_and_time','payment_type','settlement_no','shift_number','note'] as $c)
            $select[] = $has($c) ? 'pump_operator_payments.' . $c : DB::raw('NULL as ' . $c);
        $select[] = Schema::hasTable('pump_operators') ? 'pump_operators.name as operator_name' : DB::raw('NULL as operator_name');

        $orderColumn = $has('date_and_time') ? 'pump_operator_payments.date_and_time' : 'pump_operator_payments.id';
        $rows = $query->orderByDesc($orderColumn)->limit(5000)->get($select);

        $data = $rows->map(function ($r) {
            $when = $r->transaction_date ?? $r->date_and_time ?? null;
            return [
                'date' => $when ? \Carbon\Carbon::parse($when)->format('d/m/Y') : '—',
                'operator' => e($r->operator_name ?? '—'),
                'type' => ($r->payment_type ?? '') === 'excess'
                    ? '<span class="label label-success">' . __('sw::lang.excess') . '</span>'
                    : '<span class="label label-warning">' . __('sw::lang.shortage') . '</span>',
                'settlement_no' => e($r->settlement_no ?? '—'),
                'shift_no' => e($r->shift_number ?? '—'),
                'amount' => number_format((float) ($r->amount_value ?? 0), 2),
                'note' => e($r->note ?? ''),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Shifts for one operator, for the daily entry forms.
     *
     * OPEN shifts by default: an entry belongs to a shift still being worked,
     * and a closed shift's figures have been agreed.
     *
     * `all=1` lifts that, for Daily Shortage Excess only. A difference is found
     * when the cash is counted, which is usually AFTER the shift has closed -
     * refusing closed shifts there would make it impossible to record the very
     * thing the tab exists for.
     */
    public function shifts(Request $request)
    {
        $businessId = $this->businessId();
        $operatorId = (int) $request->input('pump_operator_id');

        if ($operatorId <= 0) {
            return response()->json([]);
        }

        // Do not depend on Request::boolean().  Some of the tenant estates
        // are on an older Illuminate request implementation; when that helper
        // is unavailable the AJAX call dies and the modal remains on
        // "Loading..." forever.
        $all = in_array(
            strtolower((string) $request->input('all', '0')),
            ['1', 'true', 'yes', 'on'],
            true
        );

        $shiftColumns = [
            'sw_shifts.id',
            'sw_shifts.sw_shift_no',
            'sw_shifts.shift_date',
            'sw_shifts.status',
        ];
        if (Schema::hasColumn('sw_shifts', 'closed_at')) {
            $shiftColumns[] = 'sw_shifts.closed_at';
        }

        $rows = DB::table('sw_shifts')
            ->join('sw_shift_operators', 'sw_shift_operators.sw_shift_id', '=', 'sw_shifts.id')
            ->where('sw_shifts.business_id', $businessId)
            ->where('sw_shift_operators.pump_operator_id', $operatorId)
            ->when($request->filled('location_id'),
                fn ($q) => $q->where('sw_shifts.location_id', (int) $request->location_id))
            ->when(! $all, function ($q) {
                $q->whereRaw("LOWER(TRIM(CAST(sw_shifts.status AS CHAR))) IN ('0', 'open', 'opened')");
                if (Schema::hasColumn('sw_shifts', 'closed_at')) {
                    $q->whereNull('sw_shifts.closed_at');
                }
            })
            ->orderByDesc('sw_shifts.shift_date')
            ->orderByDesc('sw_shifts.id')
            ->limit(200)
            ->get($shiftColumns);

        return response()->json($rows->map(function ($r) {
            $label = $r->sw_shift_no;

            if ($r->shift_date) {
                $label .= '  ·  ' . \Carbon\Carbon::parse($r->shift_date)->format('d/m/Y');
            }

            // A closed shift is marked, so it is obvious which is being chosen.
            if (Shift::normalizeStatusValue($r->status, $r->closed_at ?? null) !== Shift::STATUS_OPEN) {
                $label .= '  (' . __('sw::lang.closed') . ')';
            }

            return ['id' => $r->id, 'label' => $label];
        }));
    }

    // ------------------------------------------------------------------
    // Add, edit, activate
    //
    // These write to the BUSINESS's pump_operators table, shared with every
    // other module that reads operators. That is deliberate - operators are the
    // business's people, and a second list would drift - but it does mean an
    // edit here is visible everywhere.
    // ------------------------------------------------------------------

    public function create()
    {
        $businessId = $this->businessId();

        return view('sw::operators.partials.operator_form', [
            'operator' => null,
            'business_locations' => $this->locationsForDropdown($businessId),
            'pumps' => $this->pumpsForDropdown($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateOperator($request);
        $businessId = $this->businessId();

        DB::table('pump_operators')->insert($data + [
            'business_id' => $businessId,
            'active' => (int) $request->boolean('active', true),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('sw.operators.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.operator_added')]);
    }

    public function edit($id)
    {
        $businessId = $this->businessId();

        $operator = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', (int) $id)
            ->first();

        abort_if(! $operator, 404);

        return view('sw::operators.partials.operator_form', [
            'operator' => $operator,
            'business_locations' => $this->locationsForDropdown($businessId),
            'pumps' => $this->pumpsForDropdown($businessId),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateOperator($request);
        $businessId = $this->businessId();

        $updated = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', (int) $id)
            ->update($data + [
                'active' => (int) $request->boolean('active', true),
                'updated_at' => now(),
            ]);

        abort_if(! $updated && ! DB::table('pump_operators')
            ->where('business_id', $businessId)->where('id', (int) $id)->exists(), 404);

        return redirect()->route('sw.operators.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.operator_updated')]);
    }

    public function toggle($id)
    {
        $businessId = $this->businessId();

        $operator = DB::table('pump_operators')
            ->where('business_id', $businessId)->where('id', (int) $id)->first();

        abort_if(! $operator, 404);

        DB::table('pump_operators')
            ->where('id', (int) $id)
            ->update(['active' => $operator->active ? 0 : 1, 'updated_at' => now()]);

        return redirect()->route('sw.operators.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.operator_updated')]);
    }

    /**
     * Only the fields this screen owns.
     *
     * short_amount, excess_amount and settlement_no are deliberately NOT here:
     * they are set by settlement, not by hand, and letting this form write them
     * would let a typo silently change what an operator owes.
     */
    protected function validateOperator(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'location_id' => 'required|integer',
            'mobile' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'dob' => 'nullable|date',
            'landline' => 'nullable|string|max:20',
            'assigned_pump_id' => 'nullable|integer',
            'commission_type' => 'required|in:fixed,percentage,none',
            'commission_ap' => 'nullable|numeric|min:0',
        ]);

        $data['commission_ap'] = $data['commission_ap'] ?? 0;

        return $data;
    }

    /**
     * Excess/shortage activity for the selected operator date range.
     *
     * pump_operator_payments is already the shared source used by the
     * Excess/Shortage Payments tab.  Read it once, grouped by operator, rather
     * than running one query per row.  Older tenant schemas are tolerated by
     * probing only the columns that exist.
     */
    protected function operatorPeriodAmounts(int $businessId, string $startDate, string $endDate): array
    {
        $startDate = trim($startDate);
        $endDate = trim($endDate);

        if ($startDate === '' || $endDate === '' || ! Schema::hasTable('pump_operator_payments')) {
            return [];
        }

        try {
            $from = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $to = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
        } catch (\Throwable $e) {
            return [];
        }

        $has = static fn (string $column): bool => Schema::hasColumn('pump_operator_payments', $column);

        if (! $has('pump_operator_id') || ! $has('payment_type')) {
            return [];
        }

        $dateColumn = $has('transaction_date') ? 'transaction_date' : ($has('date_and_time') ? 'date_and_time' : null);
        if (! $dateColumn) {
            return [];
        }

        $amountExpression = $has('net_amount') && $has('payment_amount')
            ? 'COALESCE(NULLIF(net_amount, 0), CAST(payment_amount AS DECIMAL(22,4)))'
            : ($has('net_amount')
                ? 'net_amount'
                : ($has('payment_amount') ? 'CAST(payment_amount AS DECIMAL(22,4))' : '0'));

        $query = DB::table('pump_operator_payments')
            ->whereIn('payment_type', ['excess', 'shortage']);

        if ($has('business_id')) {
            $query->where('business_id', $businessId);
        }
        if ($has('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($dateColumn === 'transaction_date' && $has('date_and_time')) {
            // Newer rows carry transaction_date; older rows may have it NULL
            // while date_and_time is populated. Include both without applying a
            // SQL function to the indexed transaction_date column.
            $query->where(function ($dateQuery) use ($from, $to) {
                $dateQuery->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($legacyDateQuery) use ($from, $to) {
                        $legacyDateQuery->whereNull('transaction_date')
                            ->whereBetween('date_and_time', [$from, $to]);
                    });
            });
        } elseif ($dateColumn === 'transaction_date') {
            $query->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
        } else {
            $query->whereBetween('date_and_time', [$from, $to]);
        }

        $rows = $query
            ->select('pump_operator_id')
            ->selectRaw("SUM(CASE WHEN payment_type = 'excess' THEN ABS({$amountExpression}) ELSE 0 END) AS excess_total")
            ->selectRaw("SUM(CASE WHEN payment_type = 'shortage' THEN ABS({$amountExpression}) ELSE 0 END) AS shortage_total")
            ->groupBy('pump_operator_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->pump_operator_id] = [
                'excess' => (float) ($row->excess_total ?? 0),
                'shortage' => (float) ($row->shortage_total ?? 0),
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Shared lookups
    // ------------------------------------------------------------------

    protected function locationsForDropdown(int $businessId)
    {
        return DB::table('business_locations')
            ->where('business_id', $businessId)
            ->when(Schema::hasColumn('business_locations', 'is_active'),
                fn ($q) => $q->where('is_active', 1))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function pumpsForDropdown(int $businessId)
    {
        if (! Schema::hasTable('pumps')) {
            return collect();
        }

        return DB::table('pumps')
            ->where('business_id', $businessId)
            ->orderBy('pump_name')
            ->pluck('pump_name', 'id');
    }

    protected function operatorsForDropdown(int $businessId)
    {
        if (! Schema::hasTable('pump_operators')) {
            return collect();
        }

        return DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
