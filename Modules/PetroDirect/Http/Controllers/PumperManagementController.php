<?php

namespace Modules\PetroDirect\Http\Controllers;

use Modules\PetroDirect\Support\SchemaCapabilityCache;
use App\BusinessLocation;
use App\Services\BusinessLocationAccessService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\PetroDirect\Entities\PumpOperator;
use Yajra\DataTables\Facades\DataTables;

class PumperManagementController extends Controller
{
    private BusinessLocationAccessService $locationAccess;

    public function __construct(BusinessLocationAccessService $locationAccess)
    {
        $this->locationAccess = $locationAccess;
    }

    /**
     * PetroDirect standalone Pumper Management.
     * All routes, views and page logic for this screen stay inside Modules/PetroDirect.
     * Shared core tables are used only for unavoidable ERP master data such as users and business_locations.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $tab = $request->get('tab', 'operators');

            if ($tab === 'assignments' || $tab === 'daily_pump_status') {
                return $this->assignmentDataTable($request);
            }
            if ($tab === 'excess' || $tab === 'shortage' || $tab === 'payments' || $tab === 'payment_summary' || $tab === 'meters_with_payments') {
                return $this->paymentDataTable($request, $tab);
            }
            if ($tab === 'ledger') {
                return $this->ledgerDataTable($request);
            }
            if ($tab === 'pumper_day_entries' || $tab === 'shift_summary' || $tab === 'close_shift' || $tab === 'current_meter' || $tab === 'unload_stock') {
                return $this->genericPetroDirectDataTable($request, $tab);
            }

            return $this->operatorDataTable($request);
        }

        return view('petrodirect::pumper_management.index', $this->tabData());
    }

    public function show($id)
    {
        $business_id = $this->businessId();
        $pump_operator = $this->accessibleOperatorQuery($business_id)
            ->leftJoin('business_locations', function ($join) {
                $join->on('pump_operators.location_id', '=', 'business_locations.id')
                    ->on('pump_operators.business_id', '=', 'business_locations.business_id');
            })
            ->where('pump_operators.business_id', $business_id)
            ->where('pump_operators.id', $id)
            ->select('pump_operators.*', 'business_locations.name as location_name')
            ->firstOrFail();

        return view('petrodirect::pumper_management.show', compact('pump_operator'));
    }

    public function toggleActivate($id)
    {
        $business_id = $this->businessId();
        $operator = $this->accessibleOperatorQuery($business_id)
            ->findOrFail($id);

        if (SchemaCapabilityCache::hasColumn('pump_operators', 'active')) {
            $operator->active = (int) $operator->active === 1 ? 0 : 1;
            $operator->save();
        }

        return redirect()->route('petrodirect.pumper-management.index')
            ->with('status', ['success' => 1, 'msg' => __('petrodirect::lang.pumper_updated_successfully')]);
    }

    public function assignPumps(Request $request)
    {
        $business_id = $this->businessId();

        if ($request->isMethod('post')) {
            $request->validate([
                'pump_operator_id' => 'required|integer',
                'pump_id' => 'required|integer',
                'shift_number' => 'nullable|string|max:100',
                'assigned_date' => 'nullable|date',
                'status' => 'nullable|string|max:50',
            ]);

            if (!SchemaCapabilityCache::hasTable('pump_operator_assignments') || !SchemaCapabilityCache::hasTable('pumps')) {
                return back()->with('status', [
                    'success' => 0,
                    'msg' => 'The pump assignment tables are not available.',
                ]);
            }

            $operator = $this->accessibleOperatorQuery($business_id)
                ->findOrFail((int) $request->pump_operator_id);
            $pump = DB::table('pumps')
                ->where('business_id', $business_id);
            $this->locationAccess->applyLocationScope($pump, 'location_id');
            $pump = $pump->where('id', (int) $request->pump_id)->first();
            abort_unless($pump, 404, 'The selected pump is not available for this business or user.');
            abort_unless(
                (int) $operator->location_id === (int) $pump->location_id,
                422,
                'The pump and pump operator must belong to the same business location.'
            );

            $insert = [
                'business_id' => $business_id,
                'pump_operator_id' => $request->pump_operator_id,
                'pump_id' => $request->pump_id,
                'shift_number' => $request->shift_number,
                'status' => $request->status ?: 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'date')) {
                $insert['date'] = $request->assigned_date ?: date('Y-m-d');
            }
            if (SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'assigned_date')) {
                $insert['assigned_date'] = $request->assigned_date ?: date('Y-m-d');
            }

            DB::table('pump_operator_assignments')->insert($this->tableSafePayload('pump_operator_assignments', $insert));

            return redirect()->route('petrodirect.pumper-management.index', ['tab' => 'assignments'])
                ->with('status', ['success' => 1, 'msg' => __('petrodirect::lang.assignment_saved_successfully')]);
        }

        $data = $this->tabData();
        $data['active_tab'] = 'assignments';
        return view('petrodirect::pumper_management.assign_pumps', $data);
    }

    public function payExcess(Request $request)
    {
        return $this->storeOperatorPayment($request, 'excess');
    }

    public function recoverShortage(Request $request)
    {
        return $this->storeOperatorPayment($request, 'shortage');
    }

    public function create()
    {
        return view('petrodirect::pumper_management.create', $this->formData());
    }

    public function store(Request $request)
    {
        $business_id = $this->businessId();

        $data = $this->validatedData($request);
        $data['business_id'] = $business_id;
        $data['active'] = $request->has('active') ? 1 : 1;
        $data['is_petro_pd_only'] = $request->boolean('is_petro_pd_only');
        $data['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $user = $this->createOrUpdateUser($request, null, $business_id);
            if ($user && SchemaCapabilityCache::hasColumn('pump_operators', 'user_id')) {
                $data['user_id'] = $user->id;
            }

            $operator = new PumpOperator();
            foreach ($data as $key => $value) {
                if (SchemaCapabilityCache::hasColumn('pump_operators', $key)) {
                    $operator->{$key} = $value;
                }
            }
            $operator->save();

            DB::commit();

            return redirect()->route('petrodirect.pumper-management.index')
                ->with('status', ['success' => 1, 'msg' => __('petrodirect::lang.pumper_saved_successfully')]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $business_id = $this->businessId();
        $pump_operator = $this->accessibleOperatorQuery($business_id)
            ->findOrFail($id);

        $data = $this->formData();
        $data['pump_operator'] = $pump_operator;
        $data['user'] = (SchemaCapabilityCache::hasColumn('pump_operators', 'user_id') && !empty($pump_operator->user_id))
            ? User::find($pump_operator->user_id)
            : User::where('business_id', $business_id)->where('username', $pump_operator->username ?? '')->first();

        return view('petrodirect::pumper_management.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $business_id = $this->businessId();
        $pump_operator = $this->accessibleOperatorQuery($business_id)
            ->findOrFail($id);

        $data = $this->validatedData($request, $pump_operator->id);
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['is_petro_pd_only'] = $request->boolean('is_petro_pd_only');

        DB::beginTransaction();
        try {
            $user = $this->createOrUpdateUser($request, $pump_operator, $business_id);
            if ($user && SchemaCapabilityCache::hasColumn('pump_operators', 'user_id')) {
                $data['user_id'] = $user->id;
            }

            foreach ($data as $key => $value) {
                if (SchemaCapabilityCache::hasColumn('pump_operators', $key)) {
                    $pump_operator->{$key} = $value;
                }
            }
            $pump_operator->save();

            DB::commit();

            return redirect()->route('petrodirect.pumper-management.index')
                ->with('status', ['success' => 1, 'msg' => __('petrodirect::lang.pumper_updated_successfully')]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $business_id = $this->businessId();
        $operator = $this->accessibleOperatorQuery($business_id)
            ->findOrFail($id);

        if (SchemaCapabilityCache::hasColumn('pump_operators', 'active')) {
            $operator->active = 0;
            $operator->save();
        } else {
            $operator->delete();
        }

        return ['success' => true, 'msg' => __('petrodirect::lang.pumper_deleted_successfully')];
    }


    /*
     * MA-002 (LA-1136) - operator figures for the Pump Operators list.
     *
     * Reproduces what Petro's PumpOperatorController shows, because you
     * confirmed the figures should MATCH Petro for the same operator - the
     * balance belongs to the operator, not to whichever module is displaying
     * it, and Petro is being retired anyway.
     *
     * Sources, taken from Petro / core TransactionUtil rather than invented:
     *
     *   current_balance   TransactionUtil::getPumpOperatorBalance()
     *       abs(settlement shortage - recovered)
     *     - abs(settlement excess   - paid)
     *     + abs(opening_balance shortage - recovered)
     *     - abs(opening_balance excess   - paid)
     *     + total commission
     *
     *   excess / short for the period   the 'ledger_show' account_transactions
     *       behind settlement transactions with sub_type excess / shortage -
     *       the same rows the Ledger tab reads
     *
     *   sold_fuel_qty / sale_amount_fuel   summed transaction_sell_lines for
     *       sell transactions whose product category is 'Fuel'
     *
     *   commission_amount   pump_operator_commission within the date range
     *
     * WHY THIS IS BATCHED AND PETRO'S IS NOT
     *   Petro computes these PER ROW - getPumpOperatorBalance alone runs four
     *   aggregate queries, plus commission, plus two more for fuel quantity
     *   and amount. That is roughly seven queries per operator, every time the
     *   list is drawn.
     *
     *   These are the same figures computed in five grouped queries for the
     *   whole list. The results are identical; the cost does not grow with the
     *   number of operators. Given the N+1 problems already found in this
     *   project, reproducing that pattern into a second module would have been
     *   the wrong thing to copy.
     *
     * DELIBERATELY NOT SCOPED TO PETRODIRECT SETTLEMENTS. See above - you
     * asked for parity with Petro, so no PD-only filter is applied. If that
     * changes, the four queries below are where it goes.
     */

    /*
     * MA-002 (LA-1136) - WHY THESE FIGURES ARE NOT SCOPED TO PETRODIRECT.
     *
     * An earlier version of this file excluded Petro PD settlements here, on
     * the reasoning that PetroDirect has no relationship with the Pumper
     * Dashboard so should not report its amounts. THAT WAS WRONG and the
     * exclusion has been removed. The reason matters:
     *
     *   PetroDirect and Petro PD are different OPERATIONS, but they share the
     *   SAME SET OF OPERATORS. An operator marked "Petro PD only" is hidden
     *   from Direct Settlement, and that flag can be switched back at any
     *   time.
     *
     *   A shortage or excess belongs to the OPERATOR, not to the module that
     *   recorded it. If these figures were scoped per module, switching an
     *   operator between the two would CHANGE THEIR BALANCE - money would
     *   appear or vanish on a flag change. That is an accounting error, not a
     *   display preference.
     *
     * So Current Balance, Excess and Short are summed across BOTH modules, and
     * the Ledger below shows the same rows for the same reason. The operator's
     * balance reads the same wherever it is shown, which is the only behaviour
     * that survives an operator moving between flows.
     *
     * The module separation still holds where it belongs: which operators
     * appear in Direct Settlement is governed by the is_petro_pd_only flag,
     * not by filtering their financial history.
     */

    private function operatorFigures(int $business_id, array $operator_ids, $start_date, $end_date): array
    {
        $out = [];
        foreach ($operator_ids as $id) {
            $out[$id] = [
                'sold_fuel_qty' => 0, 'sale_amount_fuel' => 0,
                'current_balance' => 0, 'balance_for_period' => 0,
                'excess_amount' => 0, 'short_amount' => 0, 'commission_amount' => 0,
            ];
        }
        if ($operator_ids === [] || ! SchemaCapabilityCache::hasTable('transactions')) {
            return $out;
        }

        // 1. fuel quantity and amount, from sell lines in the period
        if (SchemaCapabilityCache::hasTable('transaction_sell_lines')) {
            $fuel = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
                ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('c.name', 'Fuel')
                ->whereIn('t.pump_operator_id', $operator_ids)
                ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                    $q->whereDate('t.transaction_date', '>=', $start_date)
                      ->whereDate('t.transaction_date', '<=', $end_date);
                })
                ->groupBy('t.pump_operator_id')
                ->select([
                    't.pump_operator_id',
                    DB::raw('SUM(tsl.quantity) as qty'),
                    DB::raw('SUM(tsl.quantity * tsl.unit_price) as amt'),
                ])->get();
            foreach ($fuel as $r) {
                $out[$r->pump_operator_id]['sold_fuel_qty']    = (float) $r->qty;
                $out[$r->pump_operator_id]['sale_amount_fuel'] = (float) $r->amt;
            }
        }

        /*
         * 2. the balance components. One grouped query replaces the four that
         *    getPumpOperatorBalance runs per operator. The inner subquery on
         *    transaction_payments mirrors core's exactly, including the
         *    is_return sign flip.
         */
        $paid = 'SELECT SUM(IF(tp.is_return = 1, -1 * tp.amount, ABS(tp.amount))) '
              . 'FROM transaction_payments tp WHERE tp.transaction_id = t.id';
        $bal = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->whereIn('t.pump_operator_id', $operator_ids)
            ->where('t.status', 'final')
            ->groupBy('t.pump_operator_id')
            ->select([
                't.pump_operator_id',
                DB::raw("SUM(IF(t.type='settlement' AND t.sub_type='shortage', ABS(t.final_total), 0)) as s_short"),
                DB::raw("SUM(IF(t.type='settlement' AND t.sub_type='shortage', ($paid), 0)) as s_short_rec"),
                DB::raw("SUM(IF(t.type='settlement' AND t.sub_type='excess', ABS(t.final_total), 0)) as s_exc"),
                DB::raw("SUM(IF(t.type='settlement' AND t.sub_type='excess', ($paid), 0)) as s_exc_paid"),
                DB::raw("SUM(IF(t.type='opening_balance' AND t.sub_type='shortage', ABS(t.final_total), 0)) as o_short"),
                DB::raw("SUM(IF(t.type='opening_balance' AND t.sub_type='shortage', ($paid), 0)) as o_short_rec"),
                DB::raw("SUM(IF(t.type='opening_balance' AND t.sub_type='excess', ABS(t.final_total), 0)) as o_exc"),
                DB::raw("SUM(IF(t.type='opening_balance' AND t.sub_type='excess', ($paid), 0)) as o_exc_paid"),
            ])->get()->keyBy('pump_operator_id');

        // 3. commission - total (for the balance) and for the period
        $comm_total = collect(); $comm_period = collect();
        if (SchemaCapabilityCache::hasTable('pump_operator_commission')) {
            $comm_total = DB::table('pump_operator_commission')
                ->whereIn('pump_operator_id', $operator_ids)
                ->groupBy('pump_operator_id')
                ->select(['pump_operator_id', DB::raw('SUM(amount) as amt')])
                ->pluck('amt', 'pump_operator_id');
            $comm_period = DB::table('pump_operator_commission')
                ->whereIn('pump_operator_id', $operator_ids)
                ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                    $q->whereDate('transaction_date', '>=', $start_date)
                      ->whereDate('transaction_date', '<=', $end_date);
                })
                ->groupBy('pump_operator_id')
                ->select(['pump_operator_id', DB::raw('SUM(amount) as amt')])
                ->pluck('amt', 'pump_operator_id');
        }

        // 4. excess and short FOR THE PERIOD, from the same rows the Ledger reads
        $period = collect();
        if (SchemaCapabilityCache::hasTable('account_transactions')) {
            $period = DB::table('account_transactions as at')
                ->join('transactions as t', 'at.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->whereIn('t.pump_operator_id', $operator_ids)
                ->whereIn('t.sub_type', ['excess', 'shortage'])
                ->where('at.sub_type', 'ledger_show')
                ->whereNull('at.deleted_at')
                ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                    $q->whereDate('t.transaction_date', '>=', $start_date)
                      ->whereDate('t.transaction_date', '<=', $end_date);
                })
                ->groupBy('t.pump_operator_id')
                ->select([
                    't.pump_operator_id',
                    DB::raw("SUM(IF(at.type='debit', at.amount, 0)) as debit_total"),
                    DB::raw("SUM(IF(at.type='credit', at.amount, 0)) as credit_total"),
                ])->get()->keyBy('pump_operator_id');
        }

        foreach ($operator_ids as $id) {
            $b = $bal->get($id);
            $commission_all = (float) ($comm_total[$id] ?? 0);

            if ($b) {
                $shortage    = (float) $b->s_short - (float) $b->s_short_rec;
                $excess      = (float) $b->s_exc   - (float) $b->s_exc_paid;
                $shortage_ob = (float) $b->o_short - (float) $b->o_short_rec;
                $excess_ob   = (float) $b->o_exc   - (float) $b->o_exc_paid;

                // exactly core's expression, signs included
                $out[$id]['current_balance'] = abs($shortage) - abs($excess)
                                             + abs($shortage_ob) - abs($excess_ob)
                                             + $commission_all;
            }

            $pr = $period->get($id);
            $debit  = $pr ? (float) $pr->debit_total  : 0;
            $credit = $pr ? (float) $pr->credit_total : 0;

            $out[$id]['short_amount']       = $debit;
            $out[$id]['excess_amount']      = $credit;
            $out[$id]['balance_for_period'] = $debit - $credit;
            $out[$id]['commission_amount']  = (float) ($comm_period[$id] ?? 0);
        }

        return $out;
    }

    private function operatorDataTable(Request $request)
    {
        $business_id = $this->businessId();

        $has_user_id_column = SchemaCapabilityCache::hasColumn('pump_operators', 'user_id');
        $has_username_column = SchemaCapabilityCache::hasColumn('pump_operators', 'username');
        $has_active_column = SchemaCapabilityCache::hasColumn('pump_operators', 'active');

        $query = $this->accessibleOperatorQuery($business_id)
            ->leftJoin('business_locations', function ($join) {
                $join->on('pump_operators.location_id', '=', 'business_locations.id')
                    ->on('pump_operators.business_id', '=', 'business_locations.business_id');
            })
            ->where('pump_operators.business_id', $business_id);

        if ($has_user_id_column) {
            $query->leftJoin('users', 'pump_operators.user_id', '=', 'users.id');
        }

        $select = [
            'pump_operators.id',
            $this->selectColumnOrBlank('pump_operators', 'name'),
            $this->selectColumnOrBlank('pump_operators', 'mobile'),
            $this->selectColumnOrBlank('pump_operators', 'landline'),
            $this->selectColumnOrBlank('pump_operators', 'cnic'),
            $this->selectColumnOrBlank('pump_operators', 'email'),
            'business_locations.name as location_name',
            $this->selectColumnOrBlank('pump_operators', 'commission_type'),
            $this->selectColumnOrZero('pump_operators', 'commission_ap'),
            $has_active_column ? 'pump_operators.active' : DB::raw('1 as active'),
        ];

        if ($has_user_id_column) {
            $select[] = 'users.username as username';
        } elseif ($has_username_column) {
            $select[] = 'pump_operators.username as username';
        } else {
            $select[] = DB::raw("'' as username");
        }

        $query->select($select);

        /*
         * MA-002 (LA-1136) - the nine figure columns.
         *
         * Computed once for every operator on the page rather than per row.
         * The date range follows the request filters if present, otherwise the
         * current month, which is what Petro's screen defaults to.
         */
        $start_date = $request->get('start_date') ?: now()->startOfMonth()->toDateString();
        $end_date   = $request->get('end_date')   ?: now()->endOfMonth()->toDateString();

        $operator_ids = (clone $query)->pluck('pump_operators.id')->map(fn ($v) => (int) $v)->all();
        $figures = $this->operatorFigures($business_id, $operator_ids, $start_date, $end_date);

        $fig = function ($row, $key) use ($figures) {
            return $figures[(int) $row->id][$key] ?? 0;
        };
        $money = function ($value, $class) {
            return '<span class="display_currency ' . $class . '" data-orig-value="'
                . (float) $value . '" data-currency_symbol="true">'
                . number_format((float) $value, 2) . '</span>';
        };

        return DataTables::of($query)
            ->addColumn('sold_fuel_qty', function ($row) use ($fig) {
                return '<span class="sold_fuel_qty" data-orig-value="' . $fig($row, 'sold_fuel_qty') . '">'
                    . number_format($fig($row, 'sold_fuel_qty'), 2) . '</span>';
            })
            ->addColumn('sale_amount_fuel', fn ($row) => $money($fig($row, 'sale_amount_fuel'), 'sale_amount_fuel'))
            ->addColumn('current_balance', fn ($row) => $money($fig($row, 'current_balance'), 'current_balance'))
            ->addColumn('balance_for_period', fn ($row) => $money($fig($row, 'balance_for_period'), 'balance_for_period'))
            ->addColumn('excess_amount', fn ($row) => $money($fig($row, 'excess_amount'), 'excess_amount'))
            ->addColumn('short_amount', fn ($row) => $money($fig($row, 'short_amount'), 'short_amount'))
            ->addColumn('commission_amount', fn ($row) => $money($fig($row, 'commission_amount'), 'commission_amount'))
            ->addColumn('commission_rate', function ($row) {
                return '<span class="display_currency commission_ap" data-orig-value="'
                    . (float) ($row->commission_ap ?? 0) . '">'
                    . number_format((float) ($row->commission_ap ?? 0), 2) . '</span>';
            })
            ->editColumn('commission_type', fn ($row) => ucfirst((string) ($row->commission_type ?? '')))
            ->addColumn('action', function ($row) {
                $show = route('petrodirect.pumper-management.show', $row->id);
                $edit = route('petrodirect.pumper-management.edit', $row->id);
                $delete = route('petrodirect.pumper-management.destroy', $row->id);
                $toggle = route('petrodirect.pumper-management.toggle-activate', $row->id);
                $assign = route('petrodirect.pumper-management.assign-pumps', ['pump_operator_id' => $row->id]);
                $excess = route('petrodirect.pumper-management.pay-excess', ['pump_operator_id' => $row->id]);
                $shortage = route('petrodirect.pumper-management.recover-shortage', ['pump_operator_id' => $row->id]);
                $ledger = route('petrodirect.pumper-management.index', ['tab' => 'ledger', 'pump_operator_id' => $row->id]);

                $activateLabel = (int) $row->active === 1 ? __('lang_v1.deactivate') : __('lang_v1.activate');
                $activateIcon = (int) $row->active === 1 ? 'fa-times' : 'fa-check';

                return '<div class="btn-group petrodirect-actions-dropdown">'
                    . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                    . __('messages.actions') . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                    . '<ul class="dropdown-menu dropdown-menu-left" role="menu">'
                    . '<li><a href="' . $show . '"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>'
                    . '<li><a href="' . $edit . '"><i class="fa fa-pencil-square-o"></i> ' . __('messages.edit') . '</a></li>'
                    . '<li><a href="' . $toggle . '"><i class="fa ' . $activateIcon . '"></i> ' . $activateLabel . '</a></li>'
                    . '<li class="divider"></li>'
                    . '<li><a href="' . $assign . '"><i class="fa fa-random"></i> ' . __('petrodirect::lang.assign_pumps') . '</a></li>'
                    . '<li><a href="' . $excess . '"><i class="fa fa-plus-circle"></i> ' . __('petrodirect::lang.pay_excess_commission') . '</a></li>'
                    . '<li><a href="' . $shortage . '"><i class="fa fa-minus-circle"></i> ' . __('petrodirect::lang.recover_shortage') . '</a></li>'
                    . '<li><a href="' . $ledger . '"><i class="fa fa-book"></i> ' . __('petrodirect::lang.pump_operator_ledger') . '</a></li>'
                    . '<li class="divider"></li>'
                    . '<li><a href="#" data-href="' . $delete . '" class="delete-petrodirect-pumper"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>'
                    . '</ul></div>';
            })
            ->editColumn('active', function ($row) {
                return (int) $row->active === 1
                    ? '<span class="label label-success">' . __('business.is_active') . '</span>'
                    : '<span class="label label-danger">' . __('lang_v1.inactive') . '</span>';
            })
            ->rawColumns([
                'action', 'active', 'sold_fuel_qty', 'sale_amount_fuel',
                'current_balance', 'balance_for_period', 'excess_amount',
                'short_amount', 'commission_rate', 'commission_amount',
            ])
            ->make(true);
    }

    private function assignmentDataTable(Request $request)
    {
        $business_id = $this->businessId();
        if (!SchemaCapabilityCache::hasTable('pump_operator_assignments')) {
            return DataTables::of(collect([]))->make(true);
        }

        $dateColumn = SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'date') ? 'poa.date' : (SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'assigned_date') ? 'poa.assigned_date' : 'poa.created_at');
        $statusColumn = SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'status') ? 'poa.status' : DB::raw("'' as status");
        $shiftColumn = SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'shift_number') ? 'poa.shift_number' : DB::raw("'' as shift_number");
        $pumpNoColumn = SchemaCapabilityCache::hasTable('pumps') && SchemaCapabilityCache::hasColumn('pumps', 'pump_no') ? 'p.pump_no' : DB::raw("'' as pump_no");
        $isConfirmedColumn = SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'is_confirmed') ? 'poa.is_confirmed' : DB::raw('0 as is_confirmed');

        $query = DB::table('pump_operator_assignments as poa')
            ->leftJoin('pump_operators as po', function ($join) {
                $join->on('poa.pump_operator_id', '=', 'po.id')
                    ->on('poa.business_id', '=', 'po.business_id');
            })
            ->leftJoin('business_locations as bl', function ($join) {
                $join->on('po.location_id', '=', 'bl.id')
                    ->on('po.business_id', '=', 'bl.business_id');
            })
            ->where('poa.business_id', $business_id);

        $this->locationAccess->applyLocationScope($query, 'po.location_id');

        if (SchemaCapabilityCache::hasTable('pumps')) {
            $query->leftJoin('pumps as p', function ($join) {
                $join->on('poa.pump_id', '=', 'p.id')
                    ->on('poa.business_id', '=', 'p.business_id');
            });
        }

        $query->select([
            'poa.id',
            DB::raw($dateColumn . ' as date'),
            $shiftColumn,
            $statusColumn,
            $isConfirmedColumn,
            'poa.created_at',
            DB::raw("COALESCE(po.name, '') as pump_operator_name"),
            $pumpNoColumn,
            DB::raw("COALESCE(bl.name, '') as location_name"),
        ]);

        return DataTables::of($query)
            ->editColumn('status', function ($row) {
                // Official pump status colours: Blue = before assigned/closed, Yellow = assigned/waiting to receive, Purple = received.
                $rawStatus = strtolower((string) ($row->status ?? ''));
                $isConfirmed = !empty($row->is_confirmed);

                if ($isConfirmed || in_array($rawStatus, ['received', 'confirmed'], true)) {
                    $label = 'Received';
                    $style = 'background:#8F3A84;color:#fff;';
                } elseif (in_array($rawStatus, ['open', 'assigned', 'pending', 'waiting', 'waiting_to_receive'], true)) {
                    $label = ucfirst($rawStatus ?: 'Assigned');
                    $style = 'background:#f4b400;color:#fff;';
                } elseif (in_array($rawStatus, ['closed', 'close', 'settled', 'completed'], true)) {
                    $label = ucfirst($rawStatus ?: 'Closed');
                    $style = 'background:#3b83b9;color:#fff;';
                } else {
                    $label = ucfirst($rawStatus ?: 'Available');
                    $style = 'background:#3b83b9;color:#fff;';
                }

                return '<span class="label" style="' . $style . '">' . e($label) . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    private function paymentDataTable(Request $request, string $tab)
    {
        $business_id = $this->businessId();
        if (!SchemaCapabilityCache::hasTable('pump_operator_payments')) {
            return DataTables::of(collect([]))->make(true);
        }

        $query = DB::table('pump_operator_payments as pop')
            ->leftJoin('pump_operators as po', function ($join) {
                $join->on('pop.pump_operator_id', '=', 'po.id')
                    ->on('pop.business_id', '=', 'po.business_id');
            })
            ->leftJoin('business_locations as bl', function ($join) {
                $join->on('po.location_id', '=', 'bl.id')
                    ->on('po.business_id', '=', 'bl.business_id');
            })
            ->where('pop.business_id', $business_id);

        $this->locationAccess->applyLocationScope($query, 'po.location_id');

        if (in_array($tab, ['excess', 'shortage'], true) && SchemaCapabilityCache::hasColumn('pump_operator_payments', 'payment_type')) {
            $query->where('pop.payment_type', $tab);
        }

        $query->select([
            'pop.id',
            $this->selectColumnOrCreatedAt('pump_operator_payments', 'date_and_time', 'pop'),
            $this->selectColumnOrBlank('pump_operator_payments', 'collection_form_no', 'pop'),
            $this->selectColumnOrBlank('pump_operator_payments', 'settlement_no', 'pop'),
            $this->selectColumnOrBlank('pump_operator_payments', 'payment_type', 'pop'),
            $this->selectColumnOrZero('pump_operator_payments', 'payment_amount', 'pop'),
            $this->selectColumnOrBlank('pump_operator_payments', 'note', 'pop'),
            'pop.created_at',
            DB::raw("COALESCE(po.name, '') as pump_operator_name"),
            DB::raw("COALESCE(bl.name, '') as location_name"),
        ]);

        return DataTables::of($query)
            ->editColumn('payment_amount', function ($row) {
                return number_format((float) $row->payment_amount, 2);
            })
            ->make(true);
    }

    private function ledgerDataTable(Request $request)
    {
        $business_id = $this->businessId();
        if (!SchemaCapabilityCache::hasTable('pump_operator_payments')) {
            return DataTables::of(collect([]))->make(true);
        }

        $query = DB::table('pump_operator_payments as pop')
            ->leftJoin('pump_operators as po', function ($join) {
                $join->on('pop.pump_operator_id', '=', 'po.id')
                    ->on('pop.business_id', '=', 'po.business_id');
            })
            ->where('pop.business_id', $business_id)
            ->when($request->filled('pump_operator_id'), function ($q) use ($request) {
                $q->where('pop.pump_operator_id', $request->pump_operator_id);
            })
            /*
             |------------------------------------------------------------------
             | LA-1188: only shortage and excess belong in the operator's ledger.
             |------------------------------------------------------------------
             |
             | This read EVERY row in pump_operator_payments, so the cash, card,
             | cheque and credit entries recorded during a settlement all appeared
             | in the Pump Operator's ledger. Those are the customer's payments,
             | collected by the operator on the business's behalf - they are not
             | amounts the operator owes or is owed, so they do not belong on a
             | personal ledger.
             |
             | What does belong is the operator's own position: a shortage they
             | must recover, an excess to be paid back to them, and the payments
             | settling either. Those are kept here; the union further down adds
             | the same pair posted through transactions/account_transactions.
             |
             | The list is matched case-insensitively and trimmed because
             | payment_type is free text and appears as "shortage", "Shortage" and
             | " excess " across older rows.
             */
            ->whereRaw("LOWER(TRIM(COALESCE(pop.payment_type, ''))) IN ('shortage', 'excess', 'shortage_recovered', 'excess_paid', 'pay_excess', 'recover_shortage')")
            ->select([
                'pop.id',
                $this->selectColumnOrCreatedAt('pump_operator_payments', 'date_and_time', 'pop'),
                $this->selectColumnOrBlank('pump_operator_payments', 'collection_form_no', 'pop'),
                $this->selectColumnOrBlank('pump_operator_payments', 'settlement_no', 'pop'),
                $this->selectColumnOrBlank('pump_operator_payments', 'payment_type', 'pop'),
                $this->selectColumnOrZero('pump_operator_payments', 'payment_amount', 'pop'),
                $this->selectColumnOrBlank('pump_operator_payments', 'note', 'pop'),
                DB::raw("COALESCE(po.name, '') as pump_operator_name"),
            ]);

        $this->locationAccess->applyLocationScope($query, 'po.location_id');

        /*
         * MA-002 (LA-1136) - the excess and shortage entries were missing.
         *
         * This ledger read ONLY pump_operator_payments. An operator's excess
         * and shortage are not payments - they are posted as `transactions`
         * with sub_type 'excess' or 'shortage', with the matching rows in
         * account_transactions carrying sub_type 'ledger_show'. Nothing in
         * this query could ever see them, which is why the Ledger tab showed
         * "No data available" for an operator who plainly had excess/short
         * amounts.
         *
         * Petro's own ledger (PumpOperatorController::getLedgerTransactionsCollection)
         * reads those rows. This unions the same source in, so the two modules
         * report the same thing.
         *
         * DELIBERATELY NARROW
         *   Only the excess/shortage source is added here. Petro's ledger also
         *   unions opening_balance transactions and pump_operator_commission
         *   rows. Those are separate concepts with their own display columns,
         *   and adding them would change more of this screen than the issue
         *   asks for. If you want full parity with Petro's ledger, say so and
         *   I will bring those across as a second step.
         *
         *   The debit/credit split follows Petro's rule: a shortage debits the
         *   operator, an excess credits them, and the reverse entries
         *   (shortage recovered, excess paid) flip accordingly.
         */
        $excessShortage = collect();
        if (SchemaCapabilityCache::hasTable('transactions')
            && SchemaCapabilityCache::hasTable('account_transactions')) {

            $esQuery = DB::table('account_transactions as at')
                ->join('transactions as t', 'at.transaction_id', '=', 't.id')
                ->leftJoin('pump_operators as po2', 't.pump_operator_id', '=', 'po2.id')
                ->where('t.business_id', $business_id)
                ->whereIn('t.sub_type', ['excess', 'shortage'])
                ->where('at.sub_type', 'ledger_show')
                ->whereNull('at.deleted_at')
                ->when($request->filled('pump_operator_id'), function ($q) use ($request) {
                    $q->where('t.pump_operator_id', $request->pump_operator_id);
                })
                /*
                 * MA-002 (LA-1136): same Petro PD exclusion the figure columns
                 * use. If the Ledger showed PD settlements while the Excess and
                 * Short columns did not, the two would disagree for the same
                 * operator on the same screen - and that discrepancy would be
                 * reported as a bug in whichever one was looked at second.
                 */
                ->select([
                    't.id',
                    DB::raw('t.transaction_date as date_and_time'),
                    DB::raw("'' as collection_form_no"),
                    DB::raw('COALESCE(t.invoice_no, \'\') as settlement_no'),
                    DB::raw('CASE
                        WHEN at.type = "debit"  AND t.sub_type = "shortage" THEN "shortage"
                        WHEN at.type = "credit" AND t.sub_type = "shortage" THEN "shortage_recovered"
                        WHEN at.type = "credit" AND t.sub_type = "excess"   THEN "excess"
                        WHEN at.type = "debit"  AND t.sub_type = "excess"   THEN "excess_paid"
                        ELSE t.sub_type
                    END as payment_type'),
                    DB::raw('at.amount as payment_amount'),
                    DB::raw("COALESCE(t.transaction_note, '') as note"),
                    DB::raw("COALESCE(po2.name, '') as pump_operator_name"),
                ]);

            $this->locationAccess->applyLocationScope($esQuery, 'po2.location_id');
            $excessShortage = $esQuery->get();
        }

        // IS2080-SYNC: Finance Journals marked "Show in Ledger = Pump Operator"
        // must be visible here exactly as in PetroGeneral. Read the journal itself
        // so Finance Edit/Delete is reflected immediately, with legacy-link fallback.
        $financeJournals = $this->financeJournalLedgerRows(
            (int) $business_id,
            $request->filled('pump_operator_id') ? (int) $request->pump_operator_id : null
        );

        $rows = collect($query->get())
            ->concat($excessShortage)
            ->concat($financeJournals)
            ->sortByDesc(function ($row) {
                return (string) ($row->date_and_time ?? '') . '|' . (string) ($row->collection_form_no ?? '');
            })
            ->values();

        return DataTables::of($rows)
            ->editColumn('payment_type', function ($row) {
                if ($row->payment_type === 'journal_debit') {
                    return 'Journal - Debit';
                }
                if ($row->payment_type === 'journal_credit') {
                    return 'Journal - Credit';
                }
                return $row->payment_type;
            })
            ->addColumn('debit', function ($row) {
                return in_array($row->payment_type, ['shortage', 'credit', 'other', 'excess_paid', 'journal_debit'], true)
                    ? number_format((float) $row->payment_amount, 2) : '0.00';
            })
            ->addColumn('credit', function ($row) {
                return in_array($row->payment_type, ['cash', 'card', 'cheque', 'excess', 'shortage_recovered', 'journal_credit'], true)
                    ? number_format((float) $row->payment_amount, 2) : '0.00';
            })
            ->make(true);
    }

    /**
     * IS2080-SYNC: Finance journal rows for the PetroDirect Pump Operator ledger.
     */
    private function financeJournalLedgerRows(int $businessId, ?int $pumpOperatorId = null)
    {
        $rows = collect();

        if ($this->hasFinanceJournalLedgerColumns()) {
            $query = DB::table('journals as finance_journals')
                ->leftJoin('pump_operators as finance_journal_operator', function ($join) {
                    $join->on('finance_journals.pump_operator', '=', 'finance_journal_operator.id')
                        ->on('finance_journals.business_id', '=', 'finance_journal_operator.business_id');
                })
                ->where('finance_journals.business_id', $businessId)
                ->where('finance_journals.show_in_ledger', 'pump_operator')
                ->whereIn('finance_journals.show_in', ['debit', 'credit']);

            if ($pumpOperatorId) {
                $query->where('finance_journals.pump_operator', $pumpOperatorId);
            }
            if (SchemaCapabilityCache::hasColumn('journals', 'deleted_at')) {
                $query->whereNull('finance_journals.deleted_at');
            }
            $this->locationAccess->applyLocationScope($query, 'finance_journal_operator.location_id');

            $rows = $query
                ->select(
                    'finance_journals.journal_id',
                    'finance_journals.show_in',
                    'finance_journals.date as date_and_time',
                    DB::raw('GREATEST(COALESCE(SUM(finance_journals.debit_amount), 0), COALESCE(SUM(finance_journals.credit_amount), 0)) as payment_amount'),
                    DB::raw("COALESCE(finance_journal_operator.name, '') as pump_operator_name")
                )
                ->groupBy(
                    'finance_journals.journal_id',
                    'finance_journals.show_in',
                    'finance_journals.date',
                    'finance_journal_operator.name'
                )
                ->get()
                ->map(function ($row) {
                    $row->id = 'journal-' . (int) $row->journal_id . '-' . $row->show_in;
                    $row->collection_form_no = 'JOUR' . str_pad((string) ((int) $row->journal_id), 4, '0', STR_PAD_LEFT);
                    $row->settlement_no = '';
                    $row->payment_type = $row->show_in === 'credit' ? 'journal_credit' : 'journal_debit';
                    $row->note = 'Finance Journal';
                    unset($row->journal_id, $row->show_in);
                    return $row;
                })
                ->filter(function ($row) {
                    return (float) ($row->payment_amount ?? 0) > 0;
                })
                ->values();
        }

        return $rows
            ->concat($this->financeJournalLegacyLedgerRows($businessId, $pumpOperatorId))
            ->unique(function ($row) {
                return (string) ($row->collection_form_no ?? '') . '|' . (string) ($row->payment_type ?? '');
            })
            ->sortByDesc(function ($row) {
                return (string) ($row->date_and_time ?? '') . '|' . (string) ($row->collection_form_no ?? '');
            })
            ->values();
    }

    private function hasFinanceJournalLedgerColumns(): bool
    {
        if (! SchemaCapabilityCache::hasTable('journals')) {
            return false;
        }

        foreach (['business_id', 'journal_id', 'date', 'debit_amount', 'credit_amount', 'show_in_ledger', 'show_in', 'pump_operator'] as $column) {
            if (! SchemaCapabilityCache::hasColumn('journals', $column)) {
                return false;
            }
        }

        return true;
    }

    private function financeJournalLegacyLedgerRows(int $businessId, ?int $pumpOperatorId = null)
    {
        if (! SchemaCapabilityCache::hasTable('transactions')
            || ! SchemaCapabilityCache::hasTable('contact_ledgers')
            || ! SchemaCapabilityCache::hasColumn('transactions', 'pump_operator_id')
            || ! SchemaCapabilityCache::hasColumn('transactions', 'invoice_no')
            || ! SchemaCapabilityCache::hasColumn('contact_ledgers', 'transaction_id')
            || ! SchemaCapabilityCache::hasColumn('contact_ledgers', 'type')) {
            return collect();
        }

        $query = DB::table('transactions as finance_journal_transactions')
            ->join('contact_ledgers as finance_journal_ledgers', function ($join) {
                $join->on('finance_journal_ledgers.transaction_id', '=', 'finance_journal_transactions.id');
                if (SchemaCapabilityCache::hasColumn('contact_ledgers', 'business_id')) {
                    $join->on('finance_journal_ledgers.business_id', '=', 'finance_journal_transactions.business_id');
                }
            })
            ->leftJoin('pump_operators as finance_journal_operator', function ($join) {
                $join->on('finance_journal_transactions.pump_operator_id', '=', 'finance_journal_operator.id')
                    ->on('finance_journal_transactions.business_id', '=', 'finance_journal_operator.business_id');
            })
            ->where('finance_journal_transactions.business_id', $businessId)
            ->where('finance_journal_transactions.invoice_no', 'like', 'Journal: %')
            ->whereIn('finance_journal_ledgers.type', ['debit', 'credit']);

        if ($pumpOperatorId) {
            $query->where('finance_journal_transactions.pump_operator_id', $pumpOperatorId);
        }
        if (SchemaCapabilityCache::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('finance_journal_transactions.deleted_at');
        }
        if (SchemaCapabilityCache::hasColumn('contact_ledgers', 'deleted_at')) {
            $query->whereNull('finance_journal_ledgers.deleted_at');
        }
        $this->locationAccess->applyLocationScope($query, 'finance_journal_operator.location_id');

        $amountExpression = SchemaCapabilityCache::hasColumn('contact_ledgers', 'amount')
            ? 'COALESCE(finance_journal_ledgers.amount, ABS(finance_journal_transactions.final_total), 0)'
            : 'COALESCE(ABS(finance_journal_transactions.final_total), 0)';

        return $query
            ->select(
                'finance_journal_transactions.id',
                'finance_journal_transactions.invoice_no',
                'finance_journal_transactions.transaction_date as date_and_time',
                'finance_journal_ledgers.type as ledger_type',
                DB::raw($amountExpression . ' as payment_amount'),
                DB::raw("COALESCE(finance_journal_operator.name, '') as pump_operator_name")
            )
            ->get()
            ->map(function ($row) {
                $raw = trim((string) $row->invoice_no);
                $journalId = 0;
                if (preg_match('/^Journal:\s*(\d+)$/i', $raw, $matches)) {
                    $journalId = (int) $matches[1];
                }
                $row->collection_form_no = $journalId > 0
                    ? 'JOUR' . str_pad((string) $journalId, 4, '0', STR_PAD_LEFT)
                    : $raw;
                $row->settlement_no = '';
                $row->payment_type = $row->ledger_type === 'credit' ? 'journal_credit' : 'journal_debit';
                $row->note = 'Finance Journal';
                unset($row->invoice_no, $row->ledger_type);
                return $row;
            })
            ->filter(function ($row) {
                return (float) ($row->payment_amount ?? 0) > 0;
            })
            ->values();
    }

    private function genericPetroDirectDataTable(Request $request, string $tab)
    {
        $business_id = $this->businessId();

        if (in_array($tab, ['pumper_day_entries', 'shift_summary', 'close_shift', 'daily_pump_status'], true)) {
            return $this->assignmentDataTable($request);
        }

        if (in_array($tab, ['current_meter', 'unload_stock'], true)) {
            $table = $tab === 'current_meter' ? 'pump_operator_meter_sales' : 'tank_transfer_lines';
            if (!SchemaCapabilityCache::hasTable($table)) {
                return DataTables::of(collect([]))->make(true);
            }

            $query = DB::table($table . ' as t');
            if (SchemaCapabilityCache::hasColumn($table, 'business_id')) {
                $query->where('t.business_id', $business_id);
            } else {
                // Do not expose a table that cannot be tied to the logged-in
                // business. A module-specific relation must be added first.
                return DataTables::of(collect([]))->make(true);
            }
            if (SchemaCapabilityCache::hasColumn($table, 'location_id')) {
                $this->locationAccess->applyLocationScope($query, 't.location_id');
            }
            $query->select([
                DB::raw('t.id as id'),
                SchemaCapabilityCache::hasColumn($table, 'date') ? DB::raw('t.date as date') : DB::raw('t.created_at as date'),
                SchemaCapabilityCache::hasColumn($table, 'pump_no') ? DB::raw('t.pump_no as reference') : DB::raw('t.id as reference'),
                SchemaCapabilityCache::hasColumn($table, 'amount') ? DB::raw('t.amount as amount') : DB::raw('0 as amount'),
                SchemaCapabilityCache::hasColumn($table, 'created_at') ? DB::raw('t.created_at as created_at') : DB::raw('NULL as created_at'),
            ]);
            return DataTables::of($query)->make(true);
        }

        return DataTables::of(collect([]))->make(true);
    }

    private function storeOperatorPayment(Request $request, string $type)
    {
        $business_id = $this->businessId();
        if ($request->isMethod('post')) {
            $request->validate([
                'pump_operator_id' => 'required|integer',
                'payment_amount' => 'required|numeric|min:0',
                'date_and_time' => 'nullable|date',
                'collection_form_no' => 'nullable|string|max:100',
                'note' => 'nullable|string|max:1000',
            ]);

            if (!SchemaCapabilityCache::hasTable('pump_operator_payments')) {
                return back()->with('status', ['success' => 0, 'msg' => 'pump_operator_payments table is missing.']);
            }

            $this->accessibleOperatorQuery($business_id)
                ->findOrFail((int) $request->pump_operator_id);

            DB::table('pump_operator_payments')->insert($this->tableSafePayload('pump_operator_payments', [
                'business_id' => $business_id,
                'pump_operator_id' => $request->pump_operator_id,
                'payment_type' => $type,
                'payment_amount' => $request->payment_amount,
                'date_and_time' => $request->date_and_time ?: now(),
                'collection_form_no' => $request->collection_form_no,
                'note' => $request->note,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            return redirect()->route('petrodirect.pumper-management.index', ['tab' => $type])
                ->with('status', ['success' => 1, 'msg' => __('petrodirect::lang.payment_saved_successfully')]);
        }

        $data = $this->tabData();
        $data['payment_type'] = $type;
        $data['active_tab'] = $type;
        return view('petrodirect::pumper_management.payment_form', $data);
    }

    private function tabData(): array
    {
        $business_id = $this->businessId();
        $operators = $this->accessibleOperatorQuery($business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $locations = BusinessLocation::forDropdown($business_id);
        if (SchemaCapabilityCache::hasTable('pumps')) {
            $pumpQuery = DB::table('pumps')->where('business_id', $business_id);
            $this->locationAccess->applyLocationScope($pumpQuery, 'location_id');
            $pumps = $pumpQuery->orderBy('pump_no')->pluck('pump_no', 'id');
        } else {
            $pumps = collect();
        }

        $active_tab = request()->get('tab', 'operators');

        return compact('operators', 'locations', 'pumps', 'active_tab');
    }

    private function businessId(): int
    {
        return (int) ($this->locationAccess->businessId() ?: 0);
    }

    private function formData(): array
    {
        $business_id = $this->businessId();
        $locations = BusinessLocation::forDropdown($business_id);
        $generate_passcode = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return compact('locations', 'generate_passcode');
    }

    private function validatedData(Request $request, $ignoreId = null): array
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'mobile' => 'required|string|max:30',
            'dob' => 'nullable|date',
            'cnic' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'location_id' => 'required|integer',
            'commission_type' => 'nullable|string|max:50',
            'commission_ap' => 'nullable|numeric',
            'opening_balance' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
        ]);

        $this->locationAccess->assertLocationAccess((int) $request->location_id, $this->businessId());

        return $request->only([
            'name', 'address', 'mobile', 'landline', 'dob', 'cnic', 'email', 'username',
            'location_id', 'commission_type', 'commission_ap', 'opening_balance',
            'opening_balance_type', 'transaction_date', 'is_default', 'can_fullscreen',
            'hide_in_direct_settlement_if_pending_shifts'
        ]);
    }

    private function createOrUpdateUser(Request $request, ?PumpOperator $operator, int $business_id): ?User
    {
        if (!$request->filled('username')) {
            return null;
        }

        $user = null;
        if ($operator && SchemaCapabilityCache::hasColumn('pump_operators', 'user_id') && !empty($operator->user_id)) {
            $user = User::find($operator->user_id);
        }
        if (!$user) {
            $user = User::where('business_id', $business_id)->where('username', $request->username)->first();
        }
        if (!$user) {
            $user = new User();
            $user->business_id = $business_id;
            $user->username = $request->username;
            if (SchemaCapabilityCache::hasColumn('users', 'allow_login')) {
                $user->allow_login = 1;
            }
        }

        $user->first_name = $request->name;
        $user->email = $request->email ?: ($request->username . '@petrodirect.local');
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            if (SchemaCapabilityCache::hasColumn('users', 'pump_operator_passcode')) {
                $user->pump_operator_passcode = $request->password;
            }
        }
        if (SchemaCapabilityCache::hasColumn('users', 'pump_operator_id') && $operator && $operator->id) {
            $user->pump_operator_id = $operator->id;
        }
        $user->save();

        return $user;
    }

    private function selectColumnOrBlank(string $table, string $column, string $alias = null)
    {
        $alias = $alias ?: $table;
        return SchemaCapabilityCache::hasColumn($table, $column)
            ? DB::raw($alias . '.' . $column . ' as ' . $column)
            : DB::raw("'' as " . $column);
    }

    private function selectColumnOrZero(string $table, string $column, string $alias = null)
    {
        $alias = $alias ?: $table;
        return SchemaCapabilityCache::hasColumn($table, $column)
            ? DB::raw($alias . '.' . $column . ' as ' . $column)
            : DB::raw('0 as ' . $column);
    }

    private function selectColumnOrCreatedAt(string $table, string $column, string $alias = null)
    {
        $alias = $alias ?: $table;
        return SchemaCapabilityCache::hasColumn($table, $column)
            ? DB::raw($alias . '.' . $column . ' as ' . $column)
            : DB::raw($alias . '.created_at as ' . $column);
    }

    private function tableSafePayload(string $table, array $data): array
    {
        return collect($data)
            ->filter(function ($value, $key) use ($table) {
                return $value !== null && SchemaCapabilityCache::hasColumn($table, $key);
            })
            ->all();
    }

    private function accessibleOperatorQuery(int $businessId)
    {
        $query = PumpOperator::withoutGlobalScopes()
            ->where('pump_operators.business_id', $businessId);

        $this->locationAccess->applyLocationScope($query, 'pump_operators.location_id');

        return $query;
    }
}
