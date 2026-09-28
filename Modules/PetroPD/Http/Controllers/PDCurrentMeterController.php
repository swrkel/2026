<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\CurrentMeter;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Yajra\DataTables\Facades\DataTables;

class PDCurrentMeterController extends Controller
{
    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();

        if (
            ! $user->can('pump_operator.dashboard') &&
            ! $user->can($permission) &&
            (empty($user->is_pump_operator) || empty($user->pump_operator_id))
        ) {
            abort(403, 'Unauthorized Access');
        }
    }


    private function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $business_id;
    }


    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (! request()->ajax()) {
            return response()->noContent();
        }

        $business_id = $this->resolveBusinessId();
        $business_details = Business::findOrFail($business_id);

        if (! Schema::hasTable('current_meters')) {
            return DataTables::of(collect())->make(true);
        }

        $hasPumpId = Schema::hasColumn('current_meters', 'pump_id');
        $hasPumpNo = Schema::hasColumn('current_meters', 'pump_no');
        $hasDateTime = Schema::hasColumn('current_meters', 'date_and_time');
        $dateColumn = $hasDateTime ? 'current_meters.date_and_time' : 'current_meters.created_at';

        $query = CurrentMeter::query()
            ->leftJoin('pump_operators', 'current_meters.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('business_locations', 'pump_operators.location_id', '=', 'business_locations.id');

        if ($hasPumpId && Schema::hasTable('pumps')) {
            $query->leftJoin('pumps', 'current_meters.pump_id', '=', 'pumps.id');
        }

        /*
         * IS1752-5: copied/legacy tenants may contain a valid current-meter row
         * whose old current_meters.business_id was not refreshed when the pump
         * operator was moved to the active business.  The operator record is the
         * authoritative business owner, so accept either exact link while still
         * preventing records from another operator/business from leaking in.
         */
        $hasDirectBusinessRows = DB::table('current_meters')
            ->where('business_id', $business_id)
            ->exists();

        if ($hasDirectBusinessRows || ! Schema::hasColumn('pump_operators', 'business_id')) {
            $query->where('current_meters.business_id', $business_id);
        } else {
            // Copied legacy tenant fallback: use operator ownership only when
            // this business has no directly owned current-meter rows at all.
            $query->where(function ($businessQuery) use ($business_id) {
                $businessQuery->where('current_meters.business_id', $business_id)
                    ->orWhere('pump_operators.business_id', $business_id);
            });
        }

        $pumpNoExpression = "'-'";
        if ($hasPumpId && Schema::hasTable('pumps') && $hasPumpNo) {
            $pumpNoExpression = "COALESCE(pumps.pump_no, current_meters.pump_no, '-')";
        } elseif ($hasPumpId && Schema::hasTable('pumps')) {
            $pumpNoExpression = "COALESCE(pumps.pump_no, '-')";
        } elseif ($hasPumpNo) {
            $pumpNoExpression = "COALESCE(current_meters.pump_no, '-')";
        }

        $query->select(
            'pump_operators.name',
            'current_meters.*',
            'business_locations.name as location_name',
            DB::raw($pumpNoExpression . ' as pump_no_display')
        );

        if (! empty(request()->pump_operator_id)) {
            $query->where('current_meters.pump_operator_id', (int) request()->pump_operator_id);
        }

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $query->whereDate($dateColumn, '>=', request()->start_date)
                ->whereDate($dateColumn, '<=', request()->end_date);
        } else {
            $query->whereDate($dateColumn, date('Y-m-d'));
        }

        if (! empty(request()->pump_id)) {
            $pumpId = (int) request()->pump_id;
            $selectedPumpNo = Schema::hasTable('pumps')
                ? DB::table('pumps')->where('id', $pumpId)->value('pump_no')
                : null;

            $query->where(function ($pumpQuery) use ($hasPumpId, $hasPumpNo, $pumpId, $selectedPumpNo) {
                $applied = false;

                if ($hasPumpId) {
                    $pumpQuery->where('current_meters.pump_id', $pumpId);
                    $applied = true;
                }

                if ($hasPumpNo && ! empty($selectedPumpNo)) {
                    if ($applied) {
                        $pumpQuery->orWhere('current_meters.pump_no', (string) $selectedPumpNo);
                    } else {
                        $pumpQuery->where('current_meters.pump_no', (string) $selectedPumpNo);
                        $applied = true;
                    }
                }

                if (! $applied) {
                    // Never ignore a requested pump filter merely because an
                    // older tenant schema has no usable pump ownership column.
                    $pumpQuery->whereRaw('1 = 0');
                }
            });
        }

        if (! empty(request()->location_id)) {
            $query->where('pump_operators.location_id', (int) request()->location_id);
        }

        $query->orderByDesc($dateColumn)
            ->orderByDesc('current_meters.id');

        $current_meters = DataTables::of($query)
            ->editColumn('date_and_time', function ($row) use ($hasDateTime) {
                $value = $hasDateTime ? ($row->date_and_time ?? null) : ($row->created_at ?? null);

                return ! empty($value)
                    ? $this->commonUtil->format_date($value, true)
                    : '—';
            })
            ->addColumn('pump_no', function ($row) {
                return $row->pump_no_display ?? $row->pump_no ?? '—';
            })
            ->editColumn('sold_ltr', function ($row) use ($business_details) {
                $value = (float) ($row->sold_ltr ?? 0);
                return '<span class="display_currency sold_ltr" data-orig-value="' . $value . '" data-currency_symbol="false">'
                    . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
            })
            ->editColumn('current_meter', function ($row) use ($business_details) {
                $value = (float) ($row->current_meter ?? 0);
                return '<span class="display_currency current_meter" data-orig-value="' . $value . '" data-currency_symbol="false">'
                    . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
            })
            ->editColumn('last_time_meter', function ($row) use ($business_details) {
                $value = (float) ($row->last_time_meter ?? 0);
                return '<span class="display_currency last_time_meter" data-orig-value="' . $value . '" data-currency_symbol="false">'
                    . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
            })
            ->editColumn('amount', function ($row) use ($business_details) {
                $current = (float) ($row->current_meter ?? 0);
                $last = (float) ($row->last_time_meter ?? 0);
                $starting = (float) ($row->starting_meter ?? 0);
                $unit_price = (float) ($row->amount ?? 0);
                $amount = ($current - ($last != 0.0 ? $last : $starting)) * $unit_price;

                return '<span class="display_currency sold_amount" data-orig-value="' . $amount . '" data-currency_symbol="false">'
                    . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
            })
            ->addColumn('total_sale_amount', function ($row) use ($business_details) {
                $amount = ((float) ($row->current_meter ?? 0) - (float) ($row->starting_meter ?? 0))
                    * (float) ($row->amount ?? 0);

                return '<span class="display_currency total_sale_amount" data-orig-value="' . $amount . '" data-currency_symbol="false">'
                    . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
            })
            ->removeColumn('id');

        return $current_meters
            ->rawColumns(['sold_ltr', 'amount', 'current_meter', 'last_time_meter', 'total_sale_amount'])
            ->make(true);
    }

    public function getModal()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.current_meter');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = Auth::user()->business_id;

        $date = date('Y-m-d');    
        
        $pumps = PumpOperatorAssignment::join('pumps','pumps.id','pump_operator_assignments.pump_id')
                                    ->join('pump_operators','pump_operator_assignments.pump_operator_id','pump_operators.id')
                                    ->where('pump_operator_assignments.pump_operator_id',$pump_operator_id)
                                    ->where('pump_operator_assignments.status', '!=', 'close')
                                    ->where(function ($query) {
                                        $query->where('pump_operator_assignments.closed_in_settlement', 0)
                                            ->orWhereNull('pump_operator_assignments.closed_in_settlement');
                                    })
                                    ->where('pump_operator_assignments.business_id',$business_id)
                                    ->select('pumps.*','pump_operator_assignments.pump_operator_id','pump_operator_assignments.pump_id', 'pump_operators.name AS pumper_name', 'pump_operator_assignments.status')
                                    ->get();
                                    
        $user = Auth::user();
        
        $pump_operator_id = $user->pump_operator_id;
        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        
        return view('petropd::pd_operators.current_meter.get_modal')->with(compact(
            'pumps',
            'shift_number'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.current_meter');

        $pump_id = request()->pump_id;
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = Auth::user()->business_id;

        $pump = Pump::leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->where('pumps.id', $pump_id)
            ->select('default_sell_price', 'pumps.*', 'variation_location_details.qty_available')->first();

        $last_time_meter = CurrentMeter::where('pump_id', $pump_id)->whereDate('date', date('Y-m-d'))->first();

        if (empty(session()->get('pump_operator_main_system'))) {
            $layout = 'pumper';
        } else {
            $layout = 'app';
        }
        return view('petropd::pd_operators.current_meter.create')->with(compact(
            'pump',
            'layout',
            'last_time_meter'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = Auth::user()->business_id;
        $pump_id = request()->pump_id;
        try {
            $data = array(
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'date_and_time' => \Carbon::now(),
                'pump_id' => $pump_id,
                'pump_no' => $request->pump_no,
                'starting_meter' => $request->starting_meter,
                'current_meter' => $request->current_meter,
                'last_time_meter' => $request->last_time_meter,
                'sold_ltr' => $request->sold_ltr,
                'sale_price' => $request->sale_price,
                'amount' => $request->amount_hidden,
            );

            DB::beginTransaction();

            CurrentMeter::create($data);
            // Pump::where('id', $pump_id)->update(['last_meter_reading' => $request->closing_meter]);
            // PumpOperatorAssignment::where('pump_id', $pump_id)->update(['status' => 'close', 'close_date_and_time' => \Carbon::now()]);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('petropd::lang.success')
            ];

            return redirect()->to('/petropd/pd-operators?tab=closing_meter')->with('status', $output);
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
