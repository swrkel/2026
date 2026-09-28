<?php



namespace Modules\PetroDirect\Http\Controllers;



use Modules\PetroDirect\Support\PetroDirectDebug;
use App\Business;

use App\Contact;

use App\Account;

use App\AccountGroup;

use App\BusinessLocation;



use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\Validator;

use Modules\PetroDirect\Entities\PetroShift;

use Modules\PetroDirect\Entities\PumpOperator;

use App\Utils\Util;

use App\Utils\ProductUtil;

use App\Utils\ModuleUtil;

use App\Utils\TransactionUtil;

use App\Utils\BusinessUtil;

use Illuminate\Support\Facades\Auth;

use Modules\PetroDirect\Entities\DailyCard;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\DB;

use Modules\PetroDirect\Entities\PetroDailyShift;

use Modules\PetroDirect\Entities\PumpOperatorAssignment;



class DailyShiftController extends Controller

{



    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;



    private $barcode_types;



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

     * @return Response

     */

    public function index()

    {

        $business_id = request()->session()->get('user.business_id');



        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_direct_module')) {

            abort(403, 'Unauthorized Access');

        }



        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                $query = DailyCard::leftjoin('pump_operators', 'daily_cards.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('business_locations','business_locations.id','pump_operators.location_id')

                    ->leftjoin('contacts', 'daily_cards.customer_id', 'contacts.id')

                    ->leftjoin('settlements','settlements.id','daily_cards.settlement_no')

                    ->leftjoin('accounts', 'daily_cards.card_type', 'accounts.id')

                    ->leftJoin('pump_operator_assignments', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')

                    ->where('daily_cards.business_id', $business_id)

                    ->select([

                        'daily_cards.*',

                        'accounts.name as type_name',

                        'pump_operators.name as pump_operator_name',

                        'contacts.name as customer_name',

                        'business_locations.name as location_name',

                        'settlements.settlement_no as settlement_nos',

                        'settlements.status as settlement_status',

                        'pump_operator_assignments.shift_number'

                    ])

                    ->groupBy('daily_cards.id');

                

                if (!empty(request()->pump_operator_id)) {

                    $query->where('daily_cards.pump_operator_id', request()->pump_operator_id);

                }

                

                if (!empty(request()->status)) {

                    if(request()->status == 'completed'){

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status',0);

                    }

                    

                    if(request()->status == 'pending'){

                        $query->where(function($q) {

                            $q->whereNull('settlements.settlement_no')

                              ->orWhere('settlements.status', 1);

                        });



                    }

                }

                

                if (!empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                

                if (!empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                

                if (!empty(request()->customer_id)) {

                    $query->where('daily_cards.customer_id', request()->customer_id);

                }

                if (!empty(request()->card_type)) {

                    $query->where('daily_cards.card_type', request()->card_type);

                }

                

                if (!empty(request()->slip_no)) {

                    $query->where('daily_cards.slip_no', request()->slip_no);

                }

                if (!empty(request()->card_number)) {

                    $query->where('daily_cards.card_number', request()->card_number);

                }

                

                if (!empty(request()->start_date) && !empty(request()->end_date)) {

                    $query->whereDate('daily_cards.date', '>=', request()->start_date);

                    $query->whereDate('daily_cards.date', '<=', request()->end_date);

                }

                // $query->orderBy(DB::raw('CAST(daily_cards.collection_no AS UNSIGNED)'), 'desc');

                $query->orderBy('daily_cards.date', 'desc');

                $fuel_tanks = Datatables::of($query)

                    ->addColumn(

                        'action',

                        '

                        @if(empty($settlement_no))@can("daily_card.edit") &nbsp; <button data-href="{{action(\'\Modules\PetroDirect\Http\Controllers\DailyCardController@edit\', [$id])}}" data-container=".pump_modal" class="btn btn-success btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil" aria-hidden="true"></i> @lang("lang_v1.edit")</button> &nbsp; @endcan @endif

                        @if($used_status == 0) <a class="btn btn-danger btn-xs delete_daily_card" data-href="{{action(\'\Modules\PetroDirect\Http\Controllers\DailyCardController@destroy\', [$id])}}"><i class="fa fa-trash" aria-hidden="true"></i> @lang("petrodirect::lang.delete")</a>@endif'

                    )

                    ->addColumn('total_collection', function ($id) {

                        $total = DB::table('daily_cards')

                                ->where('pump_operator_id', $id->pump_operator_id)

                                ->where('id', '<=', $id->id)

                                ->whereNull('settlement_no')

                                ->sum('amount') ?? 0;

                            

                            return $this->productUtil->num_f($total);

                    })

                

                    /**

                     * @ChangedBy Afes

                     * @Date 25-05-2021 

                     * @Task 12700

                     */

                    ->editColumn('amount', '{{@num_format($amount)}}')

                    ->addColumn('status',function($row){

                        if(empty($row->settlement_nos) || $row->settlement_status == 1){

                            return 'Pending';

                        }else{

                            return 'Completed';

                        }

                    })

                    ->editColumn('shift_number',function($row){

                        if(empty($row->shift_number)){

                            $assigned_pumps = PumpOperatorAssignment::where('pump_operator_id', $row->pump_operator_id)

                            ->select('shift_number')

                            ->orderBy('id','DESC')

                            ->first();

                            return optional($assigned_pumps)->shift_number;

                        }else{

                            return $row->shift_number;

                        }

                    })

                    ->editColumn('date', '{{@format_date($date)}}');



                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            }

        }



    }



    /**

     * Show the form for creating a new resource.

     * @return Response

     */

    public function create()

    {

        $business_id = request()->session()->get('user.business_id');        

        $locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($locations->toArray()));

        

        $open_shifts = PetroShift::where('business_id',$business_id)->where('status','0')->pluck('pump_operator_id')->toArray();

        $pump_operators = PumpOperator::where('business_id', $business_id)
        ->where('active', 1)
        ->whereNotIn('id',$open_shifts)->pluck('name', 'id');



        $collection_form_no = (int) (DailyCard::where('business_id', $business_id)->count()) + 1;

        

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (!empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }





        return view('petrodirect::daily_collection.partials.create_daily_cards')->with(compact('card_types','customers','locations', 'pump_operators', 'collection_form_no','default_location'));

    }



    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function store(Request $request)

    {

        

        $data = $request->validate([

            'business_id' => 'required|integer',

            'pump_operator_pending' => 'nullable|string',

            'pump_operator_assigned' => 'nullable|string',

            'date' => 'required|date',

            'time' => 'required',

            'user' => 'required|integer',

            'open_shift' => 'nullable|integer' // Add open_shift to validation

        ]);

        $data['status'] =1;

        //dd($data);





        // Generate shift number if creating new record

        if(is_null($request->open_shift) || empty($request->open_shift)){

            $data['open_shift']= $this->createIfNotExist($request);

        }

        $checkShift = PetroDailyShift::where('id', $data['open_shift'])

            ->where('business_id', $data['business_id'])

            ->first();

        if($checkShift->status == 0){

            return response()->json([

                'message' => 'shift already close'

            ], 400);

        }



        // Update existing record

        $shift = PetroDailyShift::where('id', $data['open_shift'])

            ->where('business_id', $data['business_id'])

            ->firstOrFail();



        $shift->update($data);

        $action = 'updated';

      

    

        return response()->json([

            'success' => true,

            'action' => $action,

            'shift_id' => $shift->id,

            'shift_no' => $shift->shift_no,

            'pump_operators_assigned' => explode(',', $shift->pump_operator_assigned),

            'pump_operators_pending' => explode(',', $shift->pump_operator_pending)

        ]);

    }



    public function createIfNotExist(Request $request){

        $business_id = $request->input('business_id');

        $newShift = PetroDailyShift::create([

            'business_id' => $business_id,

            'shift_no' => $this->generateShiftNumber(),

            'date' => now()->format('Y-m-d'),

            'time' => now()->format('H:i:s'),

            'user' => auth()->id(),

            'status' => 1, // Assuming 1 means "open"

            'pump_operators_assigned' => explode(',', $request->pump_operator_assigned),

            'pump_operators_pending' => explode(',', $request->pump_operator_pending)

        ]);

        return $newShift->id;

    }



    public function shiftExists($id)

    {

        return PetroDailyShift::where('id', $id)->exists();

    }

// In your DailyShiftController.php

public function OpenShift(Request $request)
{
    $business_id = $request->input('business_id');
    $module = $request->input('module', 'daily_shift');

    // Determine prefix and type based on module
    switch ($module) {
        case 'daily_collection_sw':
            $prefix = 'DCSW';
            $type = 'daily_collection_sw';
            break;
        case 'daily_collection':
            $prefix = 'DC';
            $type = 'daily_collection';
            break;
        default:
            $prefix = 'DSN';
            $type = 'daily_shift';
            break;
    }

    // Get all active operators
    $allOperatorswithNames = PumpOperator::where('business_id', $business_id)
        ->where('status', 1)
        ->where('active', 1)
        ->pluck('name', 'id')
        ->toArray();
    $allOperators = array_keys($allOperatorswithNames);

    // Check if there is an active shift for this type
    $openPendingShift = PetroDailyShift::where('business_id', $business_id)
        ->where('type', $type)
        ->where('status', 1) // only open shifts
        ->latest()
        ->first();

    $assignedOperators = $request->input('dv_pump_operator_right', []);
    $pendingOperators = array_diff($allOperators, $assignedOperators);

    if ($openPendingShift) {
        // Return existing active shift
        return response()->json([
            "success" => true,
            'shift_id' => $openPendingShift->id,
            'shift_no' => $openPendingShift->shift_no,
            'pump_operators_assigned' => $assignedOperators,
            'pump_operators_pending' => $pendingOperators,
            'operators_with_name' => $allOperatorswithNames
        ], 200);
    }

    // ONLY generate a new shift number when the user clicks "Open Shift"
    if ($request->input('create', false)) {
        $shift_no = $this->generateShiftNumber($prefix, $type, $business_id);

        $newShift = PetroDailyShift::create([
            'business_id' => $business_id,
            'shift_no' => $shift_no,
            'type' => $type,
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'user' => auth()->id(),
            'status' => 1,
            'pump_operator_pending' => implode(',', $pendingOperators),
            'pump_operator_assigned' => implode(',', $assignedOperators)
        ]);

        return response()->json([
            'success' => true,
            'shift_id' => $newShift->id,
            'shift_no' => $newShift->shift_no,
            'pump_operators_assigned' => $assignedOperators,
            'pump_operators_pending' => $pendingOperators,
            'operators_with_name' => $allOperatorswithNames
        ]);
    }

    // If no active shift and create not requested, return a placeholder
    return response()->json([
        'success' => true,
        'shift_id' => null,
        'shift_no' => 'No active shift',
        'pump_operators_assigned' => [],
        'pump_operators_pending' => $allOperators,
        'operators_with_name' => $allOperatorswithNames
    ], 200);
}


public function saveShift(Request $request)

{

    $data = $request->validate([

        'business_id' => 'required|integer',

        'open_shift' => 'required|integer',

        'pump_operator_pending' => 'nullable|string',

        'pump_operator_assigned' => 'nullable|string',

        'date' => 'required|date',

        'time' => 'required',

        'user' => 'required|integer'

    ]);



    $data['status'] = 0; // Save status



    try {

        DB::beginTransaction();



        // Step 1: Close the previous shift (status = 2)

        PetroDailyShift::where('business_id', $data['business_id'])

            ->where('status', 0) // previously saved shift(s)

            ->where('id', '!=', $data['open_shift']) // exclude the current one

            ->update(['status' => 2]);



        // Step 2: Update the current shift (status = 0)

        $shift = PetroDailyShift::where('id', $data['open_shift'])

            ->where('business_id', $data['business_id'])

            ->where('status', 1) // only update if it was 'open'

            ->firstOrFail();



        $shift->update($data);



        // Fetch operator names

        $pending_ids = array_filter(explode(',', $shift->pump_operator_pending));

        $assigned_ids = array_filter(explode(',', $shift->pump_operator_assigned));



        $pending_operators = PumpOperator::whereIn('id', $pending_ids)

            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();



        $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)

            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();



        DB::commit();



        return response()->json([

            'success' => true,

            'shift_id' => $shift->id,

            'shift_no' => $shift->shift_no,

            'status' => $data['status'],

            'old_pending' => $pending_operators,

            'old_assigned' => $assigned_operators

        ]);



    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

        DB::rollBack();

        return response()->json([

            'success' => false,

            'message' => 'Open shift not found or already closed'

        ], 404);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([

            'success' => false,

            'message' => 'Something went wrong',

            'error' => $e->getMessage()

        ], 500);

    }

}





public function editShift(PetroDailyShift $petroDailyShift, Request $request)

{



    $data = $request->validate([

        'business_id' => 'required|integer',

        // Changed from nullable to required

        'pump_operator_pending' => 'nullable|string|required_without:pump_operator_assigned',

        'pump_operator_assigned' => 'nullable|string|required_without:pump_operator_pending',

        'date' => 'required|date',

        'time' => 'required',

        'user' => 'required|integer'

    ]);



    // if(is_null($request->open_shift) || empty($request->open_shift)){

    //     $data['open_shift']=$this->createIfNotExist($request);

    // }



    try {





        $petroDailyShift->update([

            'pump_operator_assigned' => $request->pump_operator_assigned,

            'pump_operator_pending' => $request->pump_operator_pending,

        ]);



        // Convert comma-separated string to array of IDs

        $pending_ids = array_filter(explode(',', $request->pump_operator_pending));

        $assigned_ids = array_filter(explode(',', $request->pump_operators_assigned));



        // Fetch operator names using the IDs

        $pending_operators = PumpOperator::whereIn('id', $pending_ids)

            ->pluck('name', 'id')

            ->toArray();



        $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)

            ->pluck('name', 'id')

            ->toArray();



            return response()->json([

            'success' => true,

            'shift_id' => $petroDailyShift->id,

            'shift_no' =>   $petroDailyShift->shift_no,

            'status' =>  $petroDailyShift->status,

            'old_pending' => $pending_operators,

            'old_assigned' => $assigned_operators

        ]);



        // return response()->json([

        //     'success' => true,

        //     'shift_id' => $newShift->id,

        //     'shift_no' => $newShift->shift_no,

        //     'status' => $newShift->status,

        //     'old_pending' => $pending_operators,

        //     'old_assigned' => $assigned_operators

        // ]);



    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

        return response()->json([

            'success' => false,

            'message' => 'Open shift not found or already closed'

        ], 404);

    }

}

public function shiftclose(Request $request)

{

    $request->validate([

        'shift_id' => 'required|string',

    ]);



    $business_id = auth()->user()->business_id;



   $shift = PetroDailyShift::whereRaw('LOWER(shift_no) = ?', [strtolower($request->shift_id)])

    ->where('business_id', $business_id)    

    ->first();

        PetroDirectDebug::info("shift { $shift}");

        PetroDirectDebug::info("shift { $shift}");

    if (!$shift) {

        return response()->json(['message' => 'Shift not found or not saved.'], 404);

    }



    $shift->update([

        'status' => 0,

        'closed_at' => now(),

        'closed_by' => auth()->user()->id

    ]);



    return response()->json(['message' => 'Shift closed successfully.']);

}

public function shiftcloseStatus(Request $request)

{

    $request->validate([

        'shift_id' => 'required|string',

    ]);



    $business_id = auth()->user()->business_id;



   $shift = PetroDailyShift::whereRaw('LOWER(shift_no) = ?', [strtolower($request->shift_id)])

    ->where('business_id', $business_id)    

    ->first();

        PetroDirectDebug::info("shift { $shift}");

        PetroDirectDebug::info("shift { $shift}");

    if (!$shift) {

        return response()->json(['message' => 'Shift not found or not saved.'], 404);

    }



    $shift->update([

        'status' => 2,

        'closed_at' => now(),

        'closed_by' => auth()->user()->id

    ]);



    return response()->json(['message' => 'Shift closed successfully.']);

}

public function getOperators(Request $request)

{

    $business_id = $request->input('business_id', auth()->user()->business_id);

    

    $operators = PumpOperator::where('business_id', $business_id)

        ->where('status', 1)

        ->where('active', 1)

        ->pluck('name', 'id');

    

    return response()->json([

        'pump_operators' => $operators

    ]);

}



// private function generateShiftNumber()

// {

//     $lastShift = PetroDailyShift::orderBy('id', 'DESC')->first();

//     return $this->incrementCode($lastShift->shift_no??'DSN-001');

// }

    /**
     *  Generate shift number for each module type separately
     */
    private function generateShiftNumber($prefix, $type, $business_id)
{
    // Only consider shifts of the SAME type
    $lastShift = PetroDailyShift::where('business_id', $business_id)
        ->where('type', $type)
        ->orderByDesc('id')
        ->first();

    $nextNumber = 1;

    if ($lastShift && !empty($lastShift->shift_no)) {
        $parts = explode('-', $lastShift->shift_no);

        // Only increment if prefix matches
        if (isset($parts[0]) && $parts[0] === $prefix && isset($parts[1])) {
            $nextNumber = intval($parts[1]) + 1;
        }
    }

    return sprintf("%s-%03d", $prefix, $nextNumber);
}




public function fetchOpenShift()

{

    $business_id= \request()->session()->get('user.business_id');

     return PetroDailyShift::where('business_id', $business_id)

        ->where('status', 0)

        ->pluck('shift_no', 'id');

}

 private function incrementCode(string $code): string {

    $parts = explode('-', $code);



    if (count($parts) !== 2) {

        error_log('Invalid code format');

        return $code;

    }



    $prefix = $parts[0];

    $number = $parts[1];

    $numberLength = strlen($number);



    $incremented = str_pad((intval($number) + 1), $numberLength, '0', STR_PAD_LEFT);



    return $prefix . '-' . $incremented;

}

// private function generateShiftNumber()

//     {

//         $lastNumber = PetroDailyShift::select('shift_no')

//             ->orderBy('shift_no', 'desc')

//             ->value('shift_no');



//         if (!$lastNumber) {

//             return 'DSN-001';

//         }



//         $numericPart = (int) substr($lastNumber, 4); // Extract number after 'DSN-'

//         $nextNumber = $numericPart + 1;



//         return 'DSN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

//     }



    /**

     * Show the specified resource.

     * @return Response

     */

    public function show()

    {

    }



    /**

     * Show the form for editing the specified resource.

     * @return Response

     */

      



    /**

     * Remove the specified resource from storage.

     * @return Response

     */

     



    /**

     * Remove the specified resource from storage.

     * @return Response

     */

      

}

