<?php

namespace Modules\PumperDashboard\Http\Controllers;

use Modules\PumperDashboard\Services\PumperDashboardSchema;

use App\Product;
use App\Utils\BusinessUtil;
use App\Utils\Util;
;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\PumperDashboard\Entities\Pump;

use Modules\PumperDashboard\Entities\PetroShift;

use Modules\PumperDashboard\Entities\PumpOperatorAssignment;
use Modules\PumperDashboard\Entities\PumpOperator;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSale;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSaleDetail;
use Modules\PumperDashboard\Entities\PumperDayEntry;
use Modules\HR\Entities\WorkShift;
use Yajra\DataTables\Facades\DataTables;

class PumpOperatorAssignmentController extends Controller
{


    /**
     * Resolve the business currently selected in the tenant session so records
     * written by Pumper Dashboard are immediately visible in PetroPD.
     */
    private function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?: $this->resolveBusinessId()
            ?: optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $business_id;
    }

    private function startingMeterExpression(string $pumpAlias = 'pumps', string $assignmentAlias = 'pump_operator_assignments'): string
    {
        $candidates = [];

        if (PumperDashboardSchema::hasTable('pumper_day_entries')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'business_id')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'pump_id')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'closing_meter')) {
            $orderColumn = PumperDashboardSchema::hasColumn('pumper_day_entries', 'date') ? 'pde_last.date DESC,' : '';
            $candidates[] = "(SELECT NULLIF(pde_last.closing_meter, 0)
                FROM pumper_day_entries AS pde_last
                WHERE pde_last.business_id = {$assignmentAlias}.business_id
                    AND pde_last.pump_id = {$assignmentAlias}.pump_id
                    AND pde_last.closing_meter IS NOT NULL
                    AND pde_last.closing_meter > 0
                ORDER BY {$orderColumn} pde_last.id DESC
                LIMIT 1)";
        }

        if (PumperDashboardSchema::hasTable('pump_operator_assignments')
            && PumperDashboardSchema::hasColumn('pump_operator_assignments', 'closing_meter')) {
            $statusFilter = PumperDashboardSchema::hasColumn('pump_operator_assignments', 'status')
                ? "AND poa_last.status IN ('close', 'closed')"
                : '';
            $candidates[] = "(SELECT NULLIF(poa_last.closing_meter, 0)
                FROM pump_operator_assignments AS poa_last
                WHERE poa_last.business_id = {$assignmentAlias}.business_id
                    AND poa_last.pump_id = {$assignmentAlias}.pump_id
                    AND poa_last.id <> {$assignmentAlias}.id
                    {$statusFilter}
                    AND poa_last.closing_meter IS NOT NULL
                    AND poa_last.closing_meter > 0
                ORDER BY poa_last.id DESC
                LIMIT 1)";
        }

        foreach (['pod_last_meter', 'last_meter_reading', 'current_meter'] as $column) {
            if (PumperDashboardSchema::hasColumn('pumps', $column)) {
                $candidates[] = "NULLIF({$pumpAlias}.{$column}, 0)";
            }
        }

        if (PumperDashboardSchema::hasColumn('pump_operator_assignments', 'starting_meter')) {
            $candidates[] = "NULLIF({$assignmentAlias}.starting_meter, 0)";
        }

        $candidates[] = '0';

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }


    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;
    protected $businessUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->businessUtil = $businessUtil;
    }


    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('pumperdashboard::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $business_id = $this->resolveBusinessId();



        // $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('dashboard_settings')->toArray();
        $pump_operators = PumpOperator::where('business_id', $business_id)->get();
        // dd($pump_operators);
        $show_pump_if_shift_is_open = "no";
        foreach($pump_operators as $pump_operator){
            $array = json_decode($pump_operator, true);

            if (is_array($array) && isset($array['pump_should_not_show_if_shift_is_open'])) {
                if($array['pump_should_not_show_if_shift_is_open'] == 'yes'){
                    $show_pump_if_shift_is_open = "yes";
                }
            }
        }

        $open_shift_assignments = PumpOperatorAssignment::where('business_id', $business_id)
        ->where('status', 'open')
        ->where(function ($q) {
            $q->where('closed_in_settlement', 0)
              ->orWhereNull('closed_in_settlement');
        })
        ->with(['pumpOperator', 'pump', 'shift'])
        ->get();

       $assigned = PumpOperatorAssignment::where('business_id', $business_id)
            ->with('Shift')
            ->get()
            ->filter(function ($assign) {
                return $assign->shift
                    && $assign->shift->status == 0
                    && $assign->shift->closed_time === null
                    && empty($assign->closed_in_settlement);
            })
            ->pluck('pump_id')
            ->toArray();



        // if($show_pump_if_shift_is_open == 'yes'){
        //     $assigned = [];
        //     $assignedData = PumpOperatorAssignment::where('business_id',$business_id)->where('status','close')->get(['pump_id', 'shift_id'])->toArray();
        //     foreach($assignedData as $assign){
        //         $shift = PetroShift::where('id', $assign['shift_id'])->first();
        //         if($shift->status == 2){}// when shift is closed, remove its pumps from assigned pumps array
        //         else{array_push($assigned, $assign['pump_id']);}
        //     }
        // }else{
        //     $assigned = PumpOperatorAssignment::where('business_id', $business_id)->where('status','open')->pluck('pump_id')->toArray();
        // }


        // $pump_operators = PumpOperator::where('business_id', $business_id)->get(['name', 'id', 'assigned_pump_id', 'settlement_no']);
        $operators_with_open_shifts = $open_shift_assignments
            ->pluck('pump_operator_id')
            ->unique()
            ->toArray();

        $pump_operators = PumpOperator::where('business_id', $business_id)
            ->whereNotIn('id', $operators_with_open_shifts)
            ->get(['id', 'name', 'settlement_no']);

        $pump_assignments = PumpOperatorAssignment::where('business_id',$business_id)->get(['pump_id', 'id', 'pump_operator_id']);

        $pumps = Pump::whereNotIn('pumps.id', $assigned)
            ->where('pumps.business_id', $business_id)->pluck('pump_name','id');

            //   dd($show_pump_if_shift_is_open,$assigned,$pumps);



        /*
         * MA-002: numeric max, scoped to the business.
         *
         * shift_number is varchar(50), and MySQL MAX() on a text column
         * compares as TEXT - so '9' beats '10'. At shift 10 this returned 9,
         * added 1, and created a SECOND shift 10. Two operators would then
         * share a shift number, silently.
         *
         * It also had NO business filter, so one business's numbering was
         * driven by the highest number anywhere in the database.
         *
         * CAST(... AS UNSIGNED) compares numerically, and the business scope
         * keeps each tenant's numbering its own.
         */
        $shift_number = PumpOperatorAssignment::where('business_id', $business_id)
            ->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')
            ->value('n');

        $work_shifts = WorkShift::where('business_id', $business_id)->pluck('shift_name', 'id');

        return view('pumperdashboard::partials.bulk_pumper_assignment')->with(compact(
            'pumps',
            'pump_operators',
            'shift_number',
            'pump_assignments',
            'open_shift_assignments',
            'work_shifts'
        ));
    }
    public function storeBulk(Request $request)
    {
        $business_id = $this->resolveBusinessId();

        try {
            // Check if any previous unconfirmed shift exists
            $has_open_shift = PumpOperatorAssignment::where('is_manually_closed', 0)
                ->exists();

            // if ($has_open_shift) {
            //     $output = [
            //         'success' => false,
            //         'msg' => __('You cannot assign another shift until the previous shift is closed')
            //     ];
            //     return redirect()->back()->with('status', $output);
            // }

            $shift_date = date('Y-m-d', strtotime($request->date));

            // Create or update Petro Shift
            $petro_shift = PetroShift::Create(

                [
                    'business_id' => $business_id,
                    'pump_operator_id' => $request->pump_operator,
                    'status' => 0,
                    'shift_date' => $shift_date,
                    'work_shift_id' => $request->work_shift ?? null,
                ]
            );

            // Get max shift number or start from 0 if none exists
            /*
             * MA-002: numeric max, scoped to the business.
             *
             * shift_number is varchar(50), and MySQL MAX() on a text column
             * compares as TEXT - so '9' beats '10'. At shift 10 this returned 9,
             * added 1, and created a SECOND shift 10. Two operators would then
             * share a shift number, silently.
             *
             * It also had NO business filter, so one business's numbering was
             * driven by the highest number anywhere in the database.
             *
             * CAST(... AS UNSIGNED) compares numerically, and the business scope
             * keeps each tenant's numbering its own.
             */
            $shift_number = PumpOperatorAssignment::where('business_id', $business_id)
                ->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')
                ->value('n') ?? 0;

            foreach ($request->pump as $pump_id) {
                $pump = Pump::findOrFail($pump_id);

                // pod_last_meter is the latest operational reading. A historical
                // pump-master value may be higher after a reset/replacement and
                // must never override the operational chain merely because it is larger.
                $starting_meter = !empty($pump->pod_last_meter)
                    ? $pump->pod_last_meter
                    : $pump->last_meter_reading;

                $input = [
                    'business_id'       => $business_id,
                    'pump_id'           => $pump_id,
                    'pump_operator_id'  => $request->pump_operator,
                    'starting_meter'    => $starting_meter,
                    'date_and_time'     => $request->date,
                    'status'            => 'open',
                    'assigned_by'       => auth()->id(),
                    'shift_id'          => $petro_shift->id,
                    'shift_number'      => $shift_number + 1
                ];

                PumpOperatorAssignment::create($input);
            }

            // new assignments affect unconfirmed meter count; clear cache for this operator
            Cache::forget("dashboard_unconfirmed_meters_{$request->pump_operator}");

            $output = [
                'success' => true,
                'msg' => __('pumperdashboard::lang.pump_operator_assigned_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }


    // public function storeBulk(Request $request)
    // {
    //     $business_id = $this->resolveBusinessId();
    //     try {

    //         $petro_shift = PetroShift::updateOrCreate(array(
    //             'shift_date' => date('Y-m-d',strtotime($request->date)),
    //             'status' => 0,
    //             'pump_operator_id' => $request->pump_operator
    //          ),
    //          array(
    //             'business_id' => $business_id,
    //             'pump_operator_id' => $request->pump_operator,
    //             'status' => 0,
    //             'shift_date' => date('Y-m-d',strtotime($request->date))
    //         ));

    //         $shift_number = PumpOperatorAssignment::max('shift_number');
    //         foreach($request->pump as $one){
    //             $pump = Pump::findOrFail($one);

    //             $starting_meter = !empty($pump->pod_last_meter) ? ($pump->pod_last_meter >= $pump->last_meter_reading ?  ($pump->pod_last_meter) : ($pump->last_meter_reading)) :  ($pump->last_meter_reading);
    //             $input = array(
    //                 'business_id' => $business_id,
    //                 'pump_id' => $one,
    //                 'pump_operator_id' => $request->pump_operator,
    //                 'starting_meter' => $starting_meter,
    //                 'date_and_time' => $request->date,
    //                 'status' => 'open',
    //                 'assigned_by' => auth()->user()->id,
    //                 'shift_id' => $petro_shift->id,
    //                 'shift_number' => $shift_number + 1
    //             );

    //             PumpOperatorAssignment::create($input);
    //         }



    //         $output = [
    //             'success' => true,
    //             'msg' => __('pumperdashboard::lang.pump_operator_assigned_success')
    //         ];
    //     } catch (\Exception $e) {
    //         Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg' => __('messages.something_went_wrong')
    //         ];
    //     }

    //     return redirect()->back()->with('status', $output);
    // }

    public function getPumperAssignment($pump_id, $pump_operator_id)
    {
        $business_id = $this->resolveBusinessId();

        if (empty(Auth::user()->pump_operator_id)) {

            // if is admin
            $pump_op = PumpOperator::where('business_id', $this->resolveBusinessId())->where('is_default', '1')->first();

            if (!empty($pump_op)) {
                $pump_operator_id =  $pump_op->id;
            }
        }

        $pump = Pump::leftjoin('products', 'pumps.product_id', 'products.id')
            ->where('pumps.id', $pump_id)
            ->where('pumps.business_id', $business_id)
            ->select('pumps.*', 'products.name')->first();

        if(empty(session()->get('pump_operator_main_system'))){
            $layout = 'pumper';
        }else{
            $layout = 'app';
        }

        $work_shifts = WorkShift::where('business_id', $business_id)->pluck('shift_name', 'id');

        return view('pumperdashboard::partials.pumper_assignment')->with(compact(
            'pump',
            'pump_operator_id',
            'layout',
            'work_shifts'
        ));
    }

    public function confirmAssignment($id)
    {
        $business_id = $this->resolveBusinessId();
        $pump_operator_id = Auth::user()->pump_operator_id;

        $pump = PumpOperatorAssignment::leftjoin('pumps','pumps.id','pump_operator_assignments.pump_id')
                                    ->leftjoin('products','products.id','pumps.product_id')
                                    ->where('pump_operator_assignments.id',$id)
                                    ->where('pump_operator_assignments.business_id', $business_id)
                                    ->when(!empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                                        return $query->where('pump_operator_assignments.pump_operator_id', $pump_operator_id);
                                    })
                                    ->select(
                                        'pump_operator_assignments.*',
                                        'pumps.pump_name',
                                        'products.name',
                                        DB::raw($this->startingMeterExpression('pumps', 'pump_operator_assignments') . ' as starting_meter')
                                    )
                                    ->firstOrFail();

        return view('pumperdashboard::partials.pumper_assignment_pumper')->with(compact(
            'pump'
        ));
    }

    public function postConfirmAssignment(Request $request,$id)
    {

        try {

            $starting_meter = (float) str_replace(',', '', (string) $request->input('starting_meter', 0));
            $closing_meter = (float) str_replace(',', '', (string) $request->input('closing_meter', 0));

            if ($request->has('closing_meter') && round($starting_meter, 3) !== round($closing_meter, 3)) {
                $output = [
                    'success' => false,
                    'msg' => __('pumperdashboard::lang.reconfirm_meter') . ' does not match ' . __('pumperdashboard::lang.starting_meter')
                ];
                return redirect()->back()->with('status', $output);
            }

            $input = array('is_confirmed' => 1,'confirmed_at' => date('Y-m-d H:i:s'));
            if (PumperDashboardSchema::hasColumn('pump_operator_assignments', 'starting_meter')) {
                $input['starting_meter'] = $starting_meter;
            }

            PumpOperatorAssignment::where('id',$id)
                ->where('business_id', $this->resolveBusinessId())
                ->update($input);

            // clear cache for dashboard unconfirmed meters so buttons enable immediately
            $assignment = PumpOperatorAssignment::find($id);
            if (!empty($assignment)) {
                Cache::forget("dashboard_unconfirmed_meters_{$assignment->pump_operator_id}");
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        try {
            $input = $request->except('_token');
            $input['date_and_time'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = !empty($input['status']) ? 'open' : 'close';
            $input['business_id'] = $business_id;

            if (!empty($input['closing_meter'])) {
                if ($input['closing_meter'] < $input['starting_meter']) {
                    $output = [
                        'success' => 0,
                        'msg' => __('pumperdashboard::lang.closing_meter_cannot_be_smaller')
                    ];
                    return redirect()->back()->with('status', $output);
                }
            }
            if (!empty($input['status'])) {
                if (empty($input['closing_meter'])) {
                    $output = [
                        'success' => 0,
                        'msg' => __('pumperdashboard::lang.closing_meter_cannot_be_empty')
                    ];
                    return redirect()->back()->with('status', $output);
                }
            }

             $petro_shift = PetroShift::updateOrCreate(array(
                                'shift_date' => date('Y-m-d'),
                                'status' => 0,
                                'pump_operator_id' => $request->pump_operator_id
                             ),
                             array(
                                'business_id' => $business_id,
                                'pump_operator_id' => $request->pump_operator_id,
                                'status' => 0,
                                'shift_date' => date('Y-m-d'),
                                'work_shift_id' => $request->work_shift ?? null,
                            ));

            $input['shift_id'] = $petro_shift->id;


            $assignment = PumpOperatorAssignment::create($input);

            // clear cache for this pump operator so dashboard reflects new unconfirmed assignment
            if (!empty($assignment)) {
                Cache::forget("dashboard_unconfirmed_meters_{$assignment->pump_operator_id}");
            }

            $output = [
                'success' => true,
                'msg' => __('pumperdashboard::lang.pump_operator_assigned_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('pumperdashboard::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $pump_assignment = PumpOperatorAssignment::with('Shift')->findOrFail($id);

        $this->ensureAssignmentIsEditable($pump_assignment);

        $business_id = $this->resolveBusinessId();

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');

        if (empty(session()->get('pump_operator_main_system'))) {
            $layout = 'pumper';
        } else {
            $layout = 'app';
        }

        return view('pumperdashboard::partials.pumper_assignment_edit')->with(compact(
            'pump_assignment',
            'layout',
            'pump_operators',
            'pumps'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $pump_assignment = PumpOperatorAssignment::with('Shift')->findOrFail($id);

            $this->ensureAssignmentIsEditable($pump_assignment);

            $business_id = $pump_assignment->business_id;

            $validator = Validator::make($request->all(), [
                'pump_operator_id' => [
                    'required',
                    Rule::exists('pump_operators', 'id')->where('business_id', $business_id),
                ],
                'pump_id' => [
                    'required',
                    Rule::exists('pumps', 'id')->where('business_id', $business_id),
                ],
            ]);

            if ($validator->fails()) {
                $output = [
                    'success' => 0,
                    'msg' => $validator->errors()->first()
                ];
                return redirect()->back()->with('status', $output);
            }

            $input = $request->except('_token', '_method');
            $input['status'] = !empty($input['status']) ? 'open' : 'close';
            $current_starting_meter = $pump_assignment->starting_meter;

            if (!empty($input['pump_id']) && (int) $input['pump_id'] !== (int) $pump_assignment->pump_id) {
                $pump = Pump::findOrFail($input['pump_id']);
                $starting_meter = !empty($pump->pod_last_meter)
                    ? $pump->pod_last_meter
                    : $pump->last_meter_reading;
                $input['starting_meter'] = $starting_meter;
                $current_starting_meter = $starting_meter;
            } else {
                unset($input['starting_meter']);
            }

            if (!empty($input['closing_meter'])) {
                if ($input['closing_meter'] < $current_starting_meter) {
                    $output = [
                        'success' => 0,
                        'msg' => __('pumperdashboard::lang.closing_meter_cannot_be_smaller')
                    ];
                    return redirect()->back()->with('status', $output);
                }
            }
            if (!empty($input['status'])) {
                if (empty($input['closing_meter'])) {
                    $output = [
                        'success' => 0,
                        'msg' => __('pumperdashboard::lang.closing_meter_cannot_be_empty')
                    ];
                    return redirect()->back()->with('status', $output);
                }
            }

            PumpOperatorAssignment::find($id)->update($input);

            // clear cache as assignment data has changed
            $pump_assignment = PumpOperatorAssignment::find($id);
            if (!empty($pump_assignment)) {
                Cache::forget("dashboard_unconfirmed_meters_{$pump_assignment->pump_operator_id}");
            }

            $output = [
                'success' => true,
                'msg' => __('pumperdashboard::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    protected function ensureAssignmentIsEditable(PumpOperatorAssignment $assignment)
    {
        if (empty($assignment)) {
            abort(404, __('pumperdashboard::lang.edit_not_allowed_assignment_missing'));
        }

        if (!empty($assignment->is_confirmed) || !empty($assignment->confirmed_at)) {
            abort(403, __('pumperdashboard::lang.edit_not_allowed_after_receive'));
        }

        if (!empty($assignment->status) && $assignment->status === 'close') {
            abort(403, __('pumperdashboard::lang.edit_not_allowed_shift_closed'));
        }

        $shift = $assignment->relationLoaded('Shift') ? $assignment->Shift : $assignment->Shift()->first();

        if (!empty($shift) && (int) $shift->status === 2) {
            abort(403, __('pumperdashboard::lang.edit_not_allowed_shift_closed'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $assignment = PumpOperatorAssignment::findOrFail($id);

            // clear unconfirmed meters cache for this operator (assignment count will change)
            if (!empty($assignment->pump_operator_id)) {
                Cache::forget("dashboard_unconfirmed_meters_{$assignment->pump_operator_id}");
            }

            // capture identifiers used for filtering on the dashboard
            $shift_id = $assignment->shift_id;
            $shift_number = $assignment->shift_number;

            // delete the assignment itself
            $assignment->delete();

            // remove any pumper day entries linked to this assignment or
            // to other assignments having the same shift identifiers. This
            // ensures that entries are not left behind when the user filters
            // by either shift number or shift id.
            $assignment_ids = [$id];
            if (!empty($shift_id)) {
                $assignment_ids = array_merge(
                    $assignment_ids,
                    PumpOperatorAssignment::where('shift_id', $shift_id)
                        ->pluck('id')
                        ->toArray()
                );
            }
            if (!empty($shift_number)) {
                $assignment_ids = array_merge(
                    $assignment_ids,
                    PumpOperatorAssignment::where('shift_number', $shift_number)
                        ->pluck('id')
                        ->toArray()
                );
            }
            $assignment_ids = array_unique($assignment_ids);

            if (!empty($assignment_ids)) {
                PumperDayEntry::whereIn('pumper_assignment_id', $assignment_ids)->delete();
            }

            // Also delete any meter sales associated with the shift id so that
            // they do not reappear in the day entries listing after the
            // assignment has been removed.  The details table has a foreign
            // key on sale_id, so remove them first (to avoid foreign key
            // constraint errors if any).  We assume cascading behaviour is not
            // already defined in the database.
            if (!empty($shift_id)) {
                $sales = PumpOperatorMeterSale::where('shift_id', $shift_id)->pluck('id');
                if ($sales->isNotEmpty()) {
                    PumpOperatorMeterSaleDetail::whereIn('sale_id', $sales)->delete();
                    PumpOperatorMeterSale::whereIn('id', $sales)->delete();
                }
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

}


