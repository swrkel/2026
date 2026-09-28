<?php



namespace Modules\Vat\Http\Controllers;



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

use Modules\Vat\Entities\VatPrefix;

class VatPrefixController extends Controller

{

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


        if (request()->ajax()) {

                $query = VatPrefix::leftjoin('users', 'vat_prefixes.created_by', 'users.id')

                    ->where('vat_prefixes.business_id', $business_id)

                    ->select([

                        'vat_prefixes.*',

                        'users.username as user_created'

                    ]);

                

                $usageService = app(\Modules\Vat\Services\VatPrefixUsageService::class);

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'action',
                        function ($row) use ($usageService, $business_id) {
                            /*
                             * S664: Edit and Delete were always rendered here -
                             * the reported "not showing" was the menu being
                             * CLIPPED by the scrollable table wrapper. The shared
                             * builder adds the hooks the script uses to lift the
                             * open menu out to <body>.
                             *
                             * It also applies the ticket's second rule: disabled
                             * while the prefix has related transactions, live
                             * again once they are deleted. The count is taken
                             * fresh on every draw, so nothing needs resetting.
                             */
                            $count = $usageService->invoiceCount((int) $row->id, (int) $business_id);

                            return $usageService->actionMenuHtml(
                                action([\Modules\Vat\Http\Controllers\VatPrefixController::class, 'edit'], [$row->id]),
                                action([\Modules\Vat\Http\Controllers\VatPrefixController::class, 'destroy'], [$row->id]),
                                $count
                            );
                        }
                    )

                    ->removeColumn('id');



                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            }
            
            
            
            $business_id = request()->session()->get('user.business_id');

        return view('vat::vat_invoice.prefixes')->with(compact('business_id'));


    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()

    {

        $business_id = request()->session()->get('user.business_id');

        return view('vat::vat_prefixes.create')->with(compact('business_id'));

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

            $business_id = request()->session()->get('user.business_id');

            $validated = $request->validate([
                'prefix' => 'nullable|string|max:191',
                'starting_no' => ['required', 'regex:/^\d+$/'],
            ]);
            
            
            DB::beginTransaction();
            
            $data = [
                'prefix' => $validated['prefix'] ?? null,
                'starting_no' => $validated['starting_no'],
            ];
            $data['created_by'] = auth()->user()->id;
            $data['business_id'] = $business_id;
            
            
            VatPrefix::create($data);
            
            
            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('messages.success')

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }



        if ($request->ajax()) {
            return response()->json($output);
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

        $data = VatPrefix::findOrFail($id);

        return view('vat::vat_prefixes.edit')->with(compact('data'));
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
            $validated = $request->validate([
                'prefix' => 'nullable|string|max:191',
                'starting_no' => ['required', 'regex:/^\d+$/'],
            ]);

            $data = [
                'prefix' => $validated['prefix'] ?? null,
                'starting_no' => $validated['starting_no'],
            ];
            $data['created_by'] = auth()->user()->id;
            $data['business_id'] = $business_id;
            
            VatPrefix::where('id', $id)
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

        if ($request->ajax()) {
            return response()->json($output);
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
                
                VatPrefix::where('id', $id)->delete();

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

