<?php

namespace App\Http\Controllers\Auth;

use App\Business;
use App\Http\Controllers\Controller;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $util;

    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil, Util $util)
    {
        $this->middleware('guest');
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->util = $util;
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (empty($user)) {
            return back()
                ->withErrors(['email' => trans('passwords.user')])
                ->withInput($request->only('email'));
        }

        if (empty($user->contact_number)) {
            return back()->with('status', [
                'success' => 0,
                'msg' => 'Mobile number is not configured for this user.',
            ]);
        }

        $business = Business::find($user->business_id);
        if (empty($business)) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }

        $sms_settings = empty($business->sms_settings)
            ? $this->businessUtil->defaultSmsSettings()
            : $business->sms_settings;

        if (empty($sms_settings['default_gateway'])) {
            return back()->with('status', [
                'success' => 0,
                'msg' => 'SMS gateway is not configured.',
            ]);
        }

        $plainPassword = Str::random(8);
        $smsBody = 'Your new password is ' . $plainPassword;
        $mobileNumber = $user->contact_number;

        $noOfSms = $this->util->__getNumberOfSms($smsBody);
        $unitCost = $this->util->__businessSMSUnitCost($business->id);
        $balance = $this->transactionUtil->__getSMSBalance(date('Y-m-d'), $business->id, 'business');
        $totalCost = $noOfSms * $unitCost * count(array_filter(explode(',', $mobileNumber)));

        if ($totalCost > $balance) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => 'SMS Balance is not sufficient. Please Recharge and try again',
                ])
                ->with('sms_recharge_url', url('/superadmin/smsrefill-package'));
        }

        $sent = $this->util->sendSms([
            'business_id' => $business->id,
            'sms_settings' => $sms_settings,
            'mobile_number' => $mobileNumber,
            'sms_body' => $smsBody,
        ], 'Forgot Password');

        if (empty($sent)) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => 'Unable to send the SMS right now. Please try again.',
                ])
                ->withInput($request->only('email'));
        }

        $user->password = Hash::make($plainPassword);
        $user->save();

        return back()->with('status', [
            'success' => 1,
            'msg' => 'A new password has been sent to your mobile number.',
        ]);
    }
}
