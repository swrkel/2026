<?php



namespace Modules\PetroGeneral\Http\Controllers;



use App\Business;

use App\BusinessLocation;

use Illuminate\Http\Request;

use Illuminate\Routing\Controller;

use App\Utils\ModuleUtil;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Facades\DataTables;

use App\Utils\ProductUtil;

use App\Utils\TransactionUtil;

;

use Illuminate\Support\Facades\Log;

use Modules\PetroGeneral\Entities\CustomerBillVatPrefix;

class CustomerBillVatPrefixController extends Controller

{
    private const SHOW_MECHANICAL_METER_SETTING = 'mech_mtr';

    private function petroSettingsBusinessIds(): array
    {
        return array_values(array_unique(array_filter([
            request()->session()->get('user.business_id'),
            request()->session()->get('business.id'),
        ])));
    }


    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;



    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)

    {

        $this->productUtil = $productUtil;

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

    }





    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */
     
    public function getTankProduct(){
        
    }

    public function index()

    {

        $business_id = request()->session()->get('user.business_id');
        $business_ids = $this->petroSettingsBusinessIds();


        
        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            
            abort(403, 'Unauthorized Access');
            
        }



        if (request()->ajax()) {

                $query = CustomerBillVatPrefix::leftjoin('users', 'customer_bill_vat_prefixes.created_by', 'users.id')

                    ->whereIn('customer_bill_vat_prefixes.business_id', $business_ids)
                    ->where('customer_bill_vat_prefixes.prefix', self::SHOW_MECHANICAL_METER_SETTING)

                    ->select([

                        'customer_bill_vat_prefixes.*',

                        'users.username as user_created'

                    ])
                    ->orderBy('customer_bill_vat_prefixes.created_at', 'desc')
                    ->orderBy('customer_bill_vat_prefixes.id', 'desc');

                

                $fuel_tanks = Datatables::of($query)
                    ->addColumn('current_date_time', function ($row) {
                        return $this->transactionUtil->format_date($row->created_at, true);
                    })
                    ->addColumn('checkbox_status', function ($row) {
                        return (int) $row->starting_no === 1 ? 'Enabled' : 'Disabled';
                    })
                    ->addColumn('show_mechanical_meter_too', function ($row) {
                        return (int) $row->starting_no === 1 ? 'Yes' : 'No';
                    })

                    ->removeColumn('id');



                return $fuel_tanks

                    ->make(true);

            }

        $latest_setting = CustomerBillVatPrefix::whereIn('business_id', $business_ids)
            ->where('prefix', self::SHOW_MECHANICAL_METER_SETTING)
            ->latest('id')
            ->first();

        $show_mechanical_meter_too = empty($latest_setting)
            ? true
            : (int) $latest_setting->starting_no === 1;

        
        return view('petrogeneral::customer_bill_vat_prefixes.index')
            ->with(compact('show_mechanical_meter_too'));

    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()

    {

        $business_id = request()->session()->get('business.id');

        return view('petrogeneral::customer_bill_vat_prefixes.create')->with(compact('business_id'));

    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */

    public function store(Request $request)

    {



        try {

            $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');

            
            DB::beginTransaction();
            
            /*
             * IS1981: update the existing setting row instead of inserting a new one.
             *
             * This used to call create() on every save, so each time anyone pressed
             * Save on the Petro Settings page another 'mech_mtr' row was added. The
             * readers all use ->latest('id')->first(), so the setting still behaved
             * correctly, but the table grew without limit and the true value was
             * whichever row happened to have the highest id - the same duplicate-row
             * pattern that had to be cleaned up by hand for Stock Center under
             * IS1971 #2.
             *
             * Keyed on business_id + prefix, so each business keeps exactly one row.
             */
            CustomerBillVatPrefix::updateOrCreate(
                [
                    'business_id' => $business_id,
                    'prefix' => self::SHOW_MECHANICAL_METER_SETTING,
                ],
                [
                    'starting_no' => $request->boolean('show_mechanical_meter_too') ? 1 : 0,
                    'created_by' => auth()->user()->id,
                ]
            );
            
            
            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('messages.success')

            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }



        return redirect()->back()->with('status', $output);

    }



    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        //

    }



    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

     public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $data = CustomerBillVatPrefix::findOrFail($id);

        return view('petrogeneral::customer_bill_vat_prefixes.edit')->with(compact('data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        
        
        try {
            $data = $request->only('starting_no','prefix');
            $data['created_by'] = auth()->user()->id;
            $data['business_id'] = $business_id;
            
            CustomerBillVatPrefix::where('id', $id)
                            ->update($data);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }


    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function destroy($id)

    {
        $business_id = request()->session()->get('user.business_id');
        

        if (request()->ajax()) {
            try {
                
                CustomerBillVatPrefix::where('id', $id)->delete();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

}
