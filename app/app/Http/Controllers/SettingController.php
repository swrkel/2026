<?php

namespace App\Http\Controllers;

use App\System;
use App\Currency;
use App\DefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Chequer\ChequerBankAccount;
use App\Chequer\DefaultFonts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
     public function index()
    { 
        // if (!auth()->user()->can('superadmin')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $settings = DefaultSettings::where('business_id',$business_id)->first();
        $settings_list = DefaultSettings::where('business_id', $business_id)->orderBy('id', 'desc')->get();
        $currency=Currency::orderBy('country','ASC')->get();
        $currencies=[];
        foreach($currency as $datarow){
            $currencies[$datarow['country'].' '.$datarow['currency']] =$datarow['country'].' '.$datarow['currency'];
        }
       
        $stamp=DB::table('chequer_stamps')->where('business_id',$business_id)->get(); 
        $stamps=[];
        foreach($stamp as $datarow){
            $stamps[$datarow->stamp_name] = $datarow->stamp_name;
        }
        $template=DB::table('cheque_templates')->where('business_id',$business_id)->get();
        $templates=[];
        foreach($template as $datarow){
            $templates[$datarow->id] = $datarow->template_name;
        }
        $accounts=[];
         
        //if($settings){
            $account = ChequerBankAccount::with('account')->where('business_id',$business_id)->get();
            foreach($account as $datarow){
                if (!empty($datarow->account)) {
                $accounts[$datarow->account->id] = $datarow->account->name;
            }
            }
       // }

        $fonts = DefaultFonts::where('business_id',$business_id)->pluck('font')->toArray();
        $fonts = array_merge($fonts,DefaultFonts::DEFAULT_FONTS);
        if (!empty($settings)) {
            return view('chequer.default_settings', compact('settings','settings_list','currencies','stamps','templates','accounts','fonts'));
        } else {
             return view('chequer.default_settings', compact('settings_list','currencies','stamps','templates','accounts','fonts'));
        }
    }
    public function updateSettings(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
            $settings = !empty($request->id) ? DefaultSettings::where('business_id', $business_id)->where('id', $request->id)->first() : null;
            if (empty($settings) && !empty($request->def_bank_account) && !empty($request->def_cheque_templete)) {
                $settings = DefaultSettings::where('business_id', $business_id)
                    ->where('def_bank_account', $request->def_bank_account)
                    ->where('def_cheque_templete', $request->def_cheque_templete)
                    ->first();
            }
            $msg = !empty($settings) ? 'Updated' : 'Added';
            if (empty($settings)) {
                $settings = new DefaultSettings();
            }
            
            $settings->business_id=$business_id;
            $settings->def_currency=$request->def_currency; 
            $settings->def_stamp=$request->def_stamp;
            $settings->def_cheque_templete=$request->def_cheque_templete;
            $settings->def_bank_account=$request->def_bank_account;
            $settings->def_autostart_chbk_no=$request->def_autostart_chbk_no;
            $settings->def_font=$request->def_font;
            $settings->def_font_size=$request->def_font_size;
            $settings->save();
           $output = [
                    'success' => 1,
                    'msg' => 'Default Settings '.$msg.' Successfully'
                ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            echo "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
       return back()->with('status', $output);
    }


    /**
     *  @doc:6948 Chequer Module
     *  @div:Sakhawat Kamran
     * 
     * Get Bank Account Againt Template
     **/

    public function linkTemplateBankaccount(Request $request)
    {
        $template_id = $request->id;
        $business_id = request()->session()->get('user.business_id');
        $account = ChequerBankAccount::with('account')->where('business_id',$business_id)->where('cheque_templete_id',$template_id)->get();
        $accounts=[];
        foreach($account as $datarow){
            if (!empty($datarow->account)) {
                $accounts[$datarow->account->id] = $datarow->account->name;
            }
        }

        return [
            'success' => true,
            'data'=> $accounts,
            
        ];
    }
}