<?php

namespace Modules\MyHealth\Http\Controllers;

use App\Ad;
use App\AdPage;
use App\Business;
use App\Contact;
use Illuminate\Http\Request;
use Modules\MyHealth\Entities\PatientDetail;
use Modules\MyHealth\Entities\PatientSugarReading;
use Modules\MyHealth\Entities\SugarReadingBreakfast;
use Modules\MyHealth\Entities\SugarReadingLunchs;
use Modules\MyHealth\Entities\SugarReadingDinner;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Utils\BusinessUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class SugerReadingController extends Controller
{
    protected $businessUtil;
    protected $moduleUtil;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
{
    if (request()->ajax()) {
        // $users = User::where('id', Auth::user()->id)
        //     ->where('member', 0)
        //     ->first();

        // if ($users) {
            //$member = Member::where('username', $users->username)->first();
           
            $sugar_readings = PatientSugarReading::leftJoin('sugar_reading_breakfasts', 'patient_sugar_readings.id', '=', 'sugar_reading_breakfasts.sugar_reading_id')
                ->leftJoin('sugar_reading_lunchs', 'patient_sugar_readings.id', '=', 'sugar_reading_lunchs.sugar_reading_id')
                ->leftJoin('sugar_reading_dinners', 'patient_sugar_readings.id', '=', 'sugar_reading_dinners.sugar_reading_id')
                ->where('member_id',Auth::user()->id)
                ->select([
                    'patient_sugar_readings.id as id',
                    'patient_sugar_readings.sugar_reading_breakfast as sugar_reading_breakfast',
                     'patient_sugar_readings.sugar_reading_lunch as sugar_reading_lunch',
                      'patient_sugar_readings.sugar_reading_dinner as sugar_reading_dinner',
                    'patient_sugar_readings.date as sugar_reading_date',
                    'sugar_reading_breakfasts.time as breakfast_time',
                    'sugar_reading_breakfasts.reading as breakfast_reading',
                    'sugar_reading_breakfasts.note as breakfast_note',
                    'sugar_reading_lunchs.time as lunch_time',
                    'sugar_reading_lunchs.reading as lunch_reading',
                    'sugar_reading_lunchs.note as lunch_note',
                    'sugar_reading_dinners.time as dinner_time',
                    'sugar_reading_dinners.reading as dinner_reading',
                    'sugar_reading_dinners.note as dinner_note',
                ])
                ; // Execute the query
           
            return DataTables::of($sugar_readings)
         ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
          
                // Check values of breakfast, lunch, and dinner
                if ($row->sugar_reading_breakfast == 0 || $row->sugar_reading_lunch == 0 || $row->sugar_reading_dinner == 0)
                
                {
                    // Show the "Add" button if all values are 0
                    $html .= '<a href="#" data-href="' . action('\Modules\MyHealth\Http\Controllers\SugerReadingController@add', [$row->id]) . '" class="btn btn-info btn-xs btn-modal" data-container=".suger_reading_model" style="background-color: #61CBF3; color: white; border: none;">
                                <i class="fa fa-plus"></i> ' . __("messages.add") . '
                              </a>'; 
                } else {
                    // Show the "Edit" button if any value is not 0
                    $html .= '<a href="#" data-href="' . action('\Modules\MyHealth\Http\Controllers\SugerReadingController@edit', [$row->id]) . '" class="btn btn-warning btn-xs btn-modal" data-container=".suger_reading_model" style="background-color: green; color: white; border: none;">
                    <i class="fa fa-edit"></i> ' . __("messages.edit") . '   </a>';
                        }
            
                $html .= '</div>';
                
                return $html;
            })
            ->removeColumn('id')
                ->make(true); // Return DataTables response
        // }
    }

    return view('myhealth::patient.sugar_reading');
}
        public function sugar_reading()
    {



        return view('myhealth::patient.sugar_reading');
    }
   public function fetchData(Request $request)
{
    $type = $request->input('type');
    $reading_id = $request->input('sugar_reading_id');

    // Initialize the data array
    $data = [
        'reading_value' => null,
        'note' => null,
        'time' => null,
    ];

    // Select the appropriate model based on the type
    $model = null;

    if ($type == 'breakfast') {
        $model = SugarReadingBreakfast::class;
    } elseif ($type == 'lunch') {
        $model = SugarReadingLunchs::class; // Assuming you have this model
    } elseif ($type == 'dinner') {
        $model = SugarReadingDinner::class; // Assuming you have this model
    }
 
    // If a model was found, fetch the data
    if ($model) {
        $result = $model::where('sugar_reading_id', $reading_id)->first();

        if ($result) {
            $data['reading_value'] = $result->reading;
            $data['note'] = $result->note;
            $data['time'] = $result->time;
        }
    }

    // Return the data as JSON
    return response()->json($data);
}
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('myhealth::patient.add_date');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $data = $request->except('_token');
        $year = request('year1') . request('year2') . request('year3') . request('year4');
        $month = request('month1') . request('month2');
        $day = request('date1') . request('date2');
        
        $dateString = "$year-$month-$day";
        $user = User::where('id', auth()->user()->id)->first();
        // Use Carbon to create a date object
        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateString);
        try {
            $tests_data = array(
                'date' => $date,
                'member_id' =>$user->id,
            );
            $result=PatientSugarReading::create($tests_data);
           
            $output = [
                'success' => 1,
                'msg' => __('myhealth::patient.test_add_success')
            ];

            return redirect()->back()->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       

 $sugarReading = PatientSugarReading::findOrFail($id); // Replace with your actual model

    return view('myhealth::patient.edit_sugar_reading', compact('sugarReading'));
 }
public function add($id)
    {
       

 $sugarReading = PatientSugarReading::findOrFail($id); // Replace with your actual model

    return view('myhealth::patient.add_sugar_reading', compact('sugarReading'));
 }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $breakfast=0;
        $lunch=0;
        $dinner=0;
        $data = $request->except('_token');
        $hour = $request->input('hour');
        $minute = $request->input('minute');
        $second = 0; // Fixed value for seconds
    
        // Format the time as HH:MM:SS
        $time = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
        try {
            if ($request->sugar_reading=='breakfast')
            {
               $breakfast=1; 
                  $update_data = array(
                'sugar_reading_breakfast' => $breakfast
            );
            $patient_details = PatientSugarReading::where('id', $id)->update($update_data);
            
            $sugarReadingId = $id; // The sugar_reading_id you want to check
            $breakfastData = [
            'time' => $time,
            'reading' => $request->reading_value,
            'note' => $request->note,
            ];
            
            $result = SugarReadingBreakfast::updateOrCreate(
            ['sugar_reading_id' => $sugarReadingId], // Attributes to find the record
            $breakfastData // Data to update or create
            );
             
              $output = [
                'success' => 1,
                'msg' => __("myhealth::patient.details_update_success")
            ];
            }
            elseif($request->sugar_reading=='lunch')
            {
                 $lunch=1;
                 $update_data = array(
                'sugar_reading_lunch' => $lunch
            );
               $patient_details = PatientSugarReading::where('id', $id)->update($update_data);  
              
               $lunch_data = array(
                'sugar_reading_id' => $id,
                'time' => $time,
                'reading' => $request->reading_value,
                'note' => $request->note
            );
           $sugarReadingId = $id; 
            $result=SugarReadingLunchs::updateOrCreate(
            ['sugar_reading_id' => $sugarReadingId], 
            $lunch_data // Data to update or create
            );
             
                $output = [
                'success' => 1,
                'msg' => __("myhealth::patient.details_update_success")
            ];
            }
             elseif($request->sugar_reading=='dinner')
            {
                $dinner=1;
             $update_data = array(
                'sugar_reading_dinner' => $dinner
            );
             $patient_details = PatientSugarReading::where('id', $id)->update($update_data);
             $dinner_data = array(
                'sugar_reading_id' => $id,
                'time' => $time,
                'reading' => $request->reading_value,
                'note' => $request->note
            );
             $sugarReadingId = $id;
            $result=SugarReadingDinner::updateOrCreate(
            ['sugar_reading_id' => $sugarReadingId], 
            $dinner_data 
            );
              $output = [
                'success' => 1,
                'msg' => __("myhealth::patient.details_update_success")
            ];
                
            }
           
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }


        return back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


      /**
     * Retrieves list of customers, if filter is passed then filter it accordingly.
     *
     * @param  string  $q
     * @return JSON
     */
    public function getPatient()
    {
        if (request()->ajax()) {
            $term = request()->input('q', '');
            $patient = Business::where('is_patient', 1)->leftjoin('users', 'business.id', 'users.business_id')
                    ->leftjoin('patient_details', 'users.id', 'patient_details.user_id');
      

            if (!empty($term)) {
                $patient->where(function ($query) use ($term) {
                    $query->Where('users.username', 'like', '%' . $term .'%')
                            ->orWhere('patient_details.mobile', 'like', '%' . $term .'%')
                            ->orWhere('patient_details.name', 'like', '%' . $term .'%');
                });
            }

            $patient->select(
                'users.id',
                'username',
                'patient_details.mobile',
                'patient_details.name'
            );

          
            $patient = $patient->get();
            return json_encode($patient);
        }
    }

}
