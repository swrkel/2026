<?php

namespace Modules\MPCS\Http\Controllers;

use App\Account;
use App\BusinessLocation;
use App\Category;
use Illuminate\Support\Facades\DB;
use App\Employee;
use App\MergedSubCategory;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Modules\MPCS\Entities\Form9cSubCategory;
use Modules\MPCS\Entities\FormOpeningValue;
use Modules\MPCS\Entities\Mpcs16aFormSettings;
use Modules\MPCS\Entities\Mpcs9aFormSettings;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;


class FormsSettingController extends Controller
{
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->check()) {
            return Redirect::route('login');
        } 
        $business_id = request()->session()->get('business.id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        $settings = MpcsFormSetting::where('business_id', $business_id)->first();

        $mpcs_form_settings_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'mpcs_form_settings');
        $list_opening_values_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'list_opening_values');

        $card_group_rec = \Illuminate\Support\Facades\DB::table('account_groups')
            ->where('business_id', $business_id)
            ->where('name', 'Card')
            ->first();
        $card_asset_type = $card_group_rec ? $card_group_rec->id : 7;
        $card_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_asset_type)
            ->where('is_main_account', 0)
            ->orderBy('name')
            ->get();

        $f9a_settings = Mpcs9aFormSettings::where('business_id', $business_id)
            ->orderBy('date', 'desc')
            ->first();

        $card_manual = [];
        if (!empty($f9a_settings)) {
            $card_manual = json_decode($f9a_settings->pre_day_card_manual ?? '[]', true) ?: [];
        }

        $f22_from_no = "";
           // New logic to generate F16a_from_no
        // if (!empty($settings)) {
        //     // Get today's date and the starting day from settings (assumes the field 'date' is the starting day)
        //     $current_date = Carbon::today();
        //     $starting_day = Carbon::parse($settings->F22_form_tdate);
        //     $days_passed = $starting_day->diffInDays($current_date);
        //     // If no days have passed yet, use the starting number
        //     if ($days_passed === 0) {
        //         $f22_from_no = $settings->F22_form_sn;
        //     } else {
        //         // Otherwise, increment the form number based on the number of days passed
        //         // Add the number of days passed to the starting number
        //         $f22_from_no = $settings->F22_form_sn + $days_passed;
        //     }
        // } else {
        //     $f22_from_no = '';
        // }
        return view('mpcs::forms_setting.index')->with(compact('business_locations', 'settings', 'mpcs_form_settings_permission', 'list_opening_values_permission', 'f22_from_no', 'card_accounts', 'card_manual'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('mpcs::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
     
     public function store(Request $request)
{
    try {
        // Get business ID from session
        $business_id = request()->session()->get('business.id');
    
        // Fields to be extracted from request
        $input = $request->only([
            'F9C_sn', 'F9C_tdate',
            'F159ABC_form_sn', 'F159ABC_form_tdate',
            'F16A_form_sn', 'F16A_form_tdate',
            'F21C_form_sn', 'F21C_form_tdate',
            'F14_form_sn', 'F14_form_tdate',
            'F17_form_sn', 'F17_form_tdate',
            'F20_form_sn', 'F20_form_tdate',
            'F21_form_sn', 'F21_form_tdate',
            'F22_form_sn', 'F22_form_tdate',
            'current_stock_aa_onstocktaking',
        ]);
    
        $input['business_id'] = $business_id;
    
        // Define date fields to be formatted
        $dateFields = [
            'F9C_tdate',
            'F159ABC_form_tdate',
            'F16A_form_tdate',
            'F21C_form_tdate',
            'F14_form_tdate',
            'F17_form_tdate',
            'F20_form_tdate',
            'F21_form_tdate',
            'F22_form_tdate',
        ];
    
        // Format each date field to Y-m-d, or null if invalid
        foreach ($dateFields as $field) {
            $date = $input[$field] ?? null;
    
            $input[$field] = !empty($date)
                ? (
                    \Carbon\Carbon::hasFormat($date, 'm/d/Y')
                        ? \Carbon\Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d')
                        : null
                  )
                : null;
        }
    
        // Get existing record if it exists
        $existingSetting = MpcsFormSetting::where('business_id', $business_id)->first();
    
        // F22 serial number logic
        $newDate = $input['F22_form_tdate'];
        $formSerial = $input['F22_form_sn'];
    
        $input['F22_form_sn'] = $formSerial;
    
        // Save or update settings
        MpcsFormSetting::updateOrCreate(
            ['business_id' => $business_id],
            $input
        );
    
        // Response output
        $output = [
            'success' => true,
            'msg' => __('mpcs::lang.settings_update_success'),
        ];
    
    } catch (\Exception $e) {
        Log::emergency('File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Message: ' . $e->getMessage());
    
        $output = [
            'success' => false,
            'msg' => __('messages.something_went_wrong'),
        ];
    }


    return Redirect::back()->with('status', $output);
}

    public function store_old(Request $request)
    {
        // dd($request->all());
        try {
            $business_id = request()->session()->get('business.id');
            $input = $request->except('_token');
            $input['business_id'] =  $business_id;


            $input['F9C_tdate'] = !empty($request->F_9_C_tdate) ? Carbon::parse($request->F_9_C_tdate)->format('Y-m-d') : null;
            $input['F159ABC_form_tdate'] = !empty($request->F159ABC_form_tdate) ? Carbon::parse($request->F159ABC_form_tdate)->format('Y-m-d') : null;
            $input['F16A_form_tdate'] = !empty($request->F16A_form_tdate) ? Carbon::parse($request->F16A_form_tdate)->format('Y-m-d') : null;
            $input['F21C_form_tdate'] = !empty($request->F21C_form_tdate) ? Carbon::parse($request->F21C_form_tdate)->format('Y-m-d') : null;
            $input['F14_form_tdate'] = !empty($request->F14_form_tdate) ? Carbon::parse($request->F14_form_tdate)->format('Y-m-d') : null;
            $input['F17_form_tdate'] = !empty($request->F17_form_tdate) ? Carbon::parse($request->F17_form_tdate)->format('Y-m-d') : null;
            $input['F20_form_tdate'] = !empty($request->F20_form_tdate) ? Carbon::parse($request->F20_form_tdate)->format('Y-m-d') : null;
            $input['F21_form_tdate'] = !empty($request->F21_form_tdate) ? Carbon::parse($request->F21_form_tdate)->format('Y-m-d') : null;
            $input['F22_form_tdate'] = !empty($request->F22_form_tdate) ? Carbon::parse($request->F22_form_tdate)->format('Y-m-d') : null;


            MpcsFormSetting::updateOrCreate(['business_id' => $business_id], $input);

            $output = [
                'success' => true,
                'msg' => __('mpcs::lang.settings_update_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('mpcs::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('mpcs::edit');
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


    public function getForm9CSetting()
    {
        $business_id = request()->session()->get('business.id');
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $months = MpcsFormSetting::getMonthArray();
        // dd($settings,'setings');
        return view('mpcs::forms_setting.partials.form_9c_modal')->with(compact('sub_categories', 'settings', 'business_id', 'months'));
    }
    public function postForm9CSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $data = array(
                'F9C_sn' => $request->F9C_setting_sn,
                'F9C_tdate' => !empty($request->F9C_setting_tdate) ? Carbon::parse($request->F9C_setting_tdate)->format('Y-m-d') : null,
                'F9C_first_day_after_stock_taking' => !empty($request->F9C_first_day_after_stock_taking) ? 1 : 0,
                'F9C_first_day_of_next_month' => !empty($request->F9C_first_day_of_next_month) ? 1 : 0,
                'F9C_first_day_of_next_month_selected' => !empty($request->F9C_first_day_of_next_month_selected) ? $request->F9C_first_day_of_next_month_selected : null
            );
            $setting = MpcsFormSetting::where('business_id', $business_id)->update($data);
            $save_sub_cat_data = [];
            if (!empty($request->sub_cat_9c)) {
                foreach ($request->sub_cat_9c as $key => $item) {
                    $sub_cat_data = [
                        'business_id' => $business_id,
                        'sub_category_id' => $key,
                        'qty' => !empty($item['qty']) ? $item['qty'] : 0.00,
                        'amount' => !empty($item['amount']) ?  $item['amount'] : 0.00
                    ];
                    Form9cSubCategory::updateOrCreate(
                        ['business_id' => $business_id, 'sub_category_id' => $key],
                        $sub_cat_data
                    );
                    $save_sub_cat_data[] =  $sub_cat_data;
                }



                $data['cat_data'] =  $save_sub_cat_data;

                $form_id = MpcsFormSetting::where('business_id', $business_id)->first();
                FormOpeningValue::create([
                    'business_id' => $business_id,
                    'form_name' => '9C',
                    'form_id' => !empty($form_id) ? $form_id->id : 0,
                    'data' => $data,
                    'edited_by' => Auth::user()->id,
                    'date' => date('Y-m-d')
                ]);
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

        return Redirect::back()->with('status', $output);
    }
    

    public function getForm14CSetting()
    {
        $business_id = request()->session()->get('business.id');

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms_setting.partials.form_f14_modal')->with(compact('setting'));
    }

    public function updateF14FormSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            
            $input = $request->only(['F14_form_sn', 'F14_form_tdate', 'bill_no_form_14']);
            $input['business_id'] = $business_id;
            
            // Format the date if provided
            if (!empty($input['F14_form_tdate'])) {
                $input['F14_form_tdate'] = Carbon::parse($input['F14_form_tdate'])->format('Y-m-d');
            }
            
            // Update or create the F14 form settings
            MpcsFormSetting::updateOrCreate(
                ['business_id' => $business_id],
                $input
            );
            
            $output = [
                'success' => true,
                'msg' => __('mpcs::lang.f14_settings_updated_successfully')
            ];
            
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Message: ' . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        
        return Redirect::back()->with('status', $output);
    }
    
    public function getForm17CSetting()
    {
        $business_id = request()->session()->get('business.id');

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms_setting.partials.form_f17_modal')->with(compact('setting'));
    }
    

    public function getForm20CSetting()
    {
        $business_id = request()->session()->get('business.id');

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms_setting.partials.form_f20_modal')->with(compact('setting'));
    }

    public function getForm16ASetting()
    {
        $business_id = request()->session()->get('business.id');
        $months_numbers = MpcsFormSetting::getMonthArray();

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms_setting.partials.form_16a_modal')->with(compact('months_numbers', 'setting'));
    }
    
    public function postForm16ASetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $data = array(
                'F16A_form_tdate' => !empty($request->F16A_form_tdate) ? Carbon::parse($request->F16A_form_tdate)->format('Y-m-d') : null,
                'F16A_form_sn' => $request->F16A_form_sn,
                'F16A_total_pp' => $request->F16A_total_pp,
                'F16A_total_sp' => $request->F16A_total_sp,
                'F16A_first_day_after_stock_taking' => !empty($request->F16A_first_day_after_stock_taking) ? $request->F16A_first_day_after_stock_taking : 0,
                'F16A_first_day_of_next_month' => !empty($request->F16A_first_day_of_next_month) ? $request->F16A_first_day_of_next_month : 0,
                'F16A_first_day_of_next_month_selected' => !empty($request->F16A_first_day_of_next_month_selected) ? $request->F16A_first_day_of_next_month_selected : null,
            );
            MpcsFormSetting::where('business_id', $business_id)->update($data);
            $form_id = MpcsFormSetting::where('business_id', $business_id)->first();
            FormOpeningValue::create([
                'business_id' => $business_id,
                'form_name' => 'F16A',
                'form_id' => !empty($form_id) ? $form_id->id : 0,
                'data' => $data,
                'edited_by' => Auth::user()->id,
                'date' => date('Y-m-d')
            ]);

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

        return Redirect::back()->with('status', $output);
    }

    public function getFormF22Setting()
    {
        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id',  $business_id)->first();

        return view('mpcs::forms_setting.partials.form_f22_modal')->with(compact('settings'));
    }
    public function postFormF22Setting(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        $input['F22_no_of_product_per_page'] = !empty($request->F22_no_of_product_per_page) ? $request->F22_no_of_product_per_page : null;

        MpcsFormSetting::updateOrCreate(['business_id' => $business_id], $input);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.setting_save_success')
        ];

        return Redirect::back()->with('status', $output);
    }
    
    public function getFormF21Setting()
    {
        $business_id = request()->session()->get('business.id');
        $setting = MpcsFormSetting::where('business_id', $business_id)
            ->first();

        return view('mpcs::forms_setting.partials.form_f21_modal')->with(compact('setting'));
    }
    
    public function postFormF21Setting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            
            $data = [
                'F21_no_of_product_per_page' => $request->get('F21_no_of_product_per_page', 25),
                'F21_form_sn' => $request->get('F21_form_sn'),
            ];
            
            // Parse the opening date if provided
            if (!empty($request->F21_form_tdate)) {
                $dateValue = $request->F21_form_tdate;
                // Handle m/d/Y format from datepicker
                if (Carbon::hasFormat($dateValue, 'm/d/Y')) {
                    $data['F21_form_tdate'] = Carbon::createFromFormat('m/d/Y', $dateValue)->format('Y-m-d');
                } else {
                    $data['F21_form_tdate'] = Carbon::parse($dateValue)->format('Y-m-d');
                }
            }
            
            MpcsFormSetting::updateOrCreate(
                ['business_id' => $business_id], 
                $data
            );
            
            // Log the change for audit purposes
            $form_id = MpcsFormSetting::where('business_id', $business_id)->first();
            FormOpeningValue::create([
                'business_id' => $business_id,
                'form_name' => 'F21',
                'form_id' => !empty($form_id) ? $form_id->id : 0,
                'data' => $data,
                'edited_by' => Auth::user()->id,
                'date' => date('Y-m-d')
            ]);

            $output = [
                'success' => 1,
                'msg' => __('mpcs::lang.setting_save_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::back()->with('status', $output);
    }
    
    public function getForm159ABCSetting()
    {
        $business_id = request()->session()->get('business.id');
        $months = MpcsFormSetting::getMonthArray();

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms_setting.partials.form_15_9_abc_modal')->with(compact('months', 'setting'));
    }
    public function saveForm159ABCSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $data = array(
                'F159ABC_form_tdate' => !empty($request->F159ABC_form_tdate) ? Carbon::parse($request->F159ABC_form_tdate)->format('Y-m-d') : null,
                'F159ABC_form_sn' => $request->F159ABC_form_sn,
                'F159ABC_first_day_after_stock_taking' => !empty($request->F159ABC_first_day_after_stock_taking) ? $request->F159ABC_first_day_after_stock_taking : 0,
                'F159ABC_first_day_of_next_month' => !empty($request->F159ABC_first_day_of_next_month) ? $request->F159ABC_first_day_of_next_month : 0,
                'F159ABC_first_day_of_next_month_selected' => !empty($request->F159ABC_first_day_of_next_month_selected) ? $request->F159ABC_first_day_of_next_month_selected : null,
            );
            MpcsFormSetting::where('business_id', $business_id)->update($data);

            $form_id = MpcsFormSetting::where('business_id', $business_id)->first();
            FormOpeningValue::create([
                'business_id' => $business_id,
                'form_name' => 'F159ABC',
                'form_id' => !empty($form_id) ? $form_id->id : 0,
                'data' => $data,
                'edited_by' => Auth::user()->id,
                'date' => date('Y-m-d')
            ]);

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

        return Redirect::back()->with('status', $output);
    }

    public function getForm21CSetting()
    {
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $months = MpcsFormSetting::getMonthArray();

        return view('mpcs::forms_setting.partials.form_21c_modal')->with(compact('merged_sub_categories', 'settings', 'months'));
    }
    public function postForm21CSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $data = array(
                'F21C_form_sn' => $request->F21C_form_sn,
                'F21C_form_tdate' => !empty($request->F21C_form_tdate) ? Carbon::parse($request->F21C_form_tdate)->format('Y-m-d') : null,
                'F21C_first_day_after_stock_taking' => !empty($request->F21C_first_day_after_stock_taking) ? 1 : 0,
                'F21C_first_day_of_next_month' => !empty($request->F21C_first_day_of_next_month) ? 1 : 0,
                'F21C_first_day_of_next_month_selected' => $request->F21C_first_day_of_next_month
            );

            $setting = MpcsFormSetting::where('business_id', $business_id)->update($data);

            $form_id = MpcsFormSetting::where('business_id', $business_id)->first();
            FormOpeningValue::create([
                'business_id' => $business_id,
                'form_name' => 'F21C',
                'form_id' => !empty($form_id) ? $form_id->id : 0,
                'data' => $data,
                'edited_by' => Auth::user()->id,
                'date' => date('Y-m-d')
            ]);

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

        return Redirect::back()->with('status', $output);
    }

     public function get16AFormSetting() {
        
        $businessId = session()->get('user.business_id');
        $currentDate = now()->toDateString(); 
        $existingSetting = Mpcs16aFormSettings::where('business_id', $businessId)
            ->where('date', $currentDate)
            ->first();
    
        $startingNumber = $existingSetting
            ? $existingSetting->starting_number
            : Mpcs16aFormSettings::where('business_id', $businessId)
                ->orderByDesc('created_at')
                ->value('starting_number') + 1;

        return view('mpcs::forms.partials.create_16a_form_settings')->with(compact('startingNumber'));

}
 
    public function store16aFormSetting(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $data = array(
            'business_id' => $business_id,
            'date' => $request->input('datepicker'),
            'time' => $request->input('time'),
            'starting_number' => $request->input('starting_number'),
            // 'ref_pre_form_number' => $request->input('ref_pre_form_number'),
            'no_of_rows_per_page' => $request->input('no_of_rows_per_page'),
            'total_purchase_price_with_vat' => $request->input('total_purchase_price_with_vat'),
            'total_sale_price_with_vat' => $request->input('total_sale_price_with_vat'),
            'created_by' => auth()->user()->id,
            'created_at' => date('Y-m-d H:i'),
            'updated_at' => date('Y-m-d H:i'),
        );
        // dd($data,'data');

        Mpcs16aFormSettings::insertGetId($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_16a_settings_add_success')
        ];

        return $output;
    }
    
    public function store16aFormSettingnew(Request $request)
    { 
        $businessId = session()->get('user.business_id');
        $currentDate = now()->toDateString(); 
        //"2025-04-19";
        $requestedStartNumber = $request->input('starting_number');
    // dump($requestedStartNumber,'request',$currentDate);
        $existingSetting = Mpcs16aFormSettings::where('business_id', $businessId)
            ->where('date', $currentDate)
            ->first();
    
        $startingNumber = $existingSetting
            ? $existingSetting->starting_number
            : Mpcs16aFormSettings::where('business_id', $businessId)
                ->orderByDesc('created_at')
                ->value('starting_number') + 1 ?? $requestedStartNumber;
    // dd($startingNumber,'starting number');
        Mpcs16aFormSettings::create([
            'business_id' => $businessId,
            'date' => $currentDate,
            'time' => $request->input('time'),
            'starting_number' => $startingNumber,
            'no_of_rows_per_page' => $request->input('no_of_rows_per_page'),
            'total_purchase_price_with_vat' => $request->input('total_purchase_price_with_vat'),
            'total_sale_price_with_vat' => $request->input('total_sale_price_with_vat'),
            'created_by' => auth()->id(), // Use auth()->id() directly
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        return [
            'success' => 1,
            'msg' => __('mpcs::lang.form_16a_settings_add_success')
        ];
    }

    public function mpcs16aFormSettings()
    {
        if (request()->ajax()) {
            // IS2039: scope to the current business; this list had no filter,
            // so every business's settings row was returned.
            $business_id = request()->session()->get('business.id')
                ?? request()->session()->get('user.business_id');

            $header = Mpcs16aFormSettings::where('business_id', $business_id)->select('*');
         
            return DataTables::of($header)
                ->removeColumn('id')
                ->removeColumn('business_id')
                ->removeColumn('ref_pre_form_number')
                ->removeColumn('created_at')
                ->removeColumn('updated_at')
                ->editColumn('date', function($row) {
                    $formattedTime = Carbon::parse($row->time)->format('H:i');
                    return $row->date.' '.$formattedTime;
                })
                ->editColumn('total_purchase_price_with_vat', function($row) {
                    return number_format($row->total_purchase_price_with_vat, 2, '.', ',');
                })
                ->editColumn('total_sale_price_with_vat', function($row) {
                    return number_format($row->total_sale_price_with_vat, 2, '.', ',');
                })
                ->editColumn('action', function ($row) {
                    /*
                     * IS2039 (fourth report of IS2016 / IS2025 / IS2038): Edit is
                     * disabled once the settings have been saved.
                     *
                     * THIS is the endpoint the F16A settings table actually uses.
                     * list_f16.blade.php points #form_16a_settings_table at
                     * /mpcs/16aformsettings, which routes here. The three earlier
                     * attempts were applied to Form16ASettingsController and then
                     * Form9ASettingsController - neither of which this screen
                     * calls, so none of that code ever ran.
                     *
                     * The superadmin gate is removed too: the ticket asks for the
                     * button to be disabled after saving, without qualification,
                     * and the person testing is a superadmin - which is why it
                     * still appeared enabled every time.
                     */
                    return '<button type="button" class="btn btn-primary btn-xs" disabled
                            title="' . e(__("messages.edit")) . ' is disabled once the settings have been saved."><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</button>';
                })

                ->rawColumns(['action'])
                ->make(true);
        }
        $business_id = request()->session()->get('business.id');
        $settings = Mpcs16aFormSettings::where('business_id', $business_id)->first();
        $business_locations = BusinessLocation::forDropdown($business_id);

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();

        return view('mpcs::forms.form_9a')->with(compact(
            'business_locations',
            'settings'
        ));
    }

    public function edit16aFormSetting($id) {
        $business_id = request()->session()->get('user.business_id');

        if (Gate::forUser(auth()->user())->check('superadmin'))
            $settings = Mpcs16aFormSettings::first();
        else 
            $settings = Mpcs16aFormSettings::where('business_id', $business_id)->where('id', $id)->first();        $settings = Mpcs16aFormSettings::first();

        return view('mpcs::forms.partials.edit_16a_form_settings')->with(compact(
                    'settings'
        ));
    }

    public function mpcs16Update(Request $request, $id)
    {
        $data = array(
            // 'business_id' => $business_id,
            'date' => date('Y-m-d'),
            'time' => $request->input('time'),
            'starting_number' => $request->input('starting_number'),
            'ref_pre_form_number' => $request->input('ref_pre_form_number'),
            'total_purchase_price_with_vat' => $request->input('total_purchase_price_with_vat'),
            'total_sale_price_with_vat' => $request->input('total_sale_price_with_vat'),
            'created_by' => auth()->user()->id,
            'created_at' => date('Y-m-d H:i'),
            'updated_at' => date('Y-m-d H:i'),
       );

        Mpcs16aFormSettings::where('id', $id)->update($data);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_16a_settings_update_success')
        ];

        return $output;
    }

}
