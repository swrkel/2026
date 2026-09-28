<?php

namespace Modules\Petro\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Petro\Entities\IssueCustomerBillSetting;
use App\Utils\ProductUtil;
use Illuminate\Support\Facades\Auth;
class IssueCustomerBillSettingController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $setting = IssueCustomerBillSetting::firstOrNew([
                'business_id' => $business_id
            ]);

            $setting->print_option = $request->input('print_option', 1);
            $setting->show_pump = $request->input('show_pump');
            $setting->show_pump_operator = $request->input('show_pump_operator');
            $setting->update_customer_ledger = $request->input('post_to_customer_ledger', 0);
            $setting->prefill_credit_sale_details = $request->input('prefill_credit_sale_details', 1);
            $setting->added_by = Auth::user()->id;

            $setting->save();

            return response()->json([
                'success' => true,
                'msg' => __('petro::lang.print_settings_saved_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}