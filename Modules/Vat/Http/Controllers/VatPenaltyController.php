<?php

namespace Modules\Vat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Vat\Entities\VatSetting;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Transaction;
;
use App\BusinessLocation;

class VatPenaltyController extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;

    protected $transactionUtil;
    
    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }
    
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        //   
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        // 
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $business_location = BusinessLocation::where('business_id', $business_id)
            ->first();
            
            
            DB::beginTransaction();
                $ob_data = [
                'business_id' => $business_id,
                'location_id' => $business_location->id,
                'type' => 'vat_penalty',
                'status' => 'final',
                'payment_status' => 'due',
                'contact_id' => $request->customer_id,
                'transaction_date' => \Carbon::parse($request->date)->format('Y-m-d'),
                'total_before_tax' => 0,
                'final_total' => $this->vatFormatter()->num_uf($request->amount),
                'transaction_note' => $request->note,
                'created_by' => request()->session()->get('user.id'),
                'is_vat' => 1,
                'tax_amount' =>$this->vatFormatter()->num_uf($request->amount) 
            ];
            
            
            $transaction = Transaction::create($ob_data);
            
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



        return $output;
    }

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
    public function edit()
    {
       
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
