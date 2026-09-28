<?php

namespace Modules\MyAuto\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\MyAuto\Entities\MyAutoSubscription;
use App\System;
use Razorpay\Api\Api;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\Package;

class MyAutoSubscriptionController extends Controller
{
    public function startCheckout(Request $request)
    {
        $request->validate([
            'package_id' => 'required|integer',
            'subscription_cycle' => 'required|in:Daily,Monthly,Biannually,Annually',
            'amount_to_auto_load' => 'nullable|numeric|min:0',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $auto_number = $request->session()->get('business.name');

        $package = Package::active()
            ->visible()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->findOrFail($request->package_id);

        $request->session()->put('my_auto_checkout', [
            'business_id' => $business_id,
            'package_id' => (int) $package->id,
            'auto_number' => $auto_number,
            'subscription_cycle' => $request->subscription_cycle,
            'amount_to_auto_load' => $request->filled('amount_to_auto_load')
                ? $request->amount_to_auto_load
                : null,
        ]);

        return redirect()->action(
            '\Modules\Superadmin\Http\Controllers\SubscriptionController@pay',
            ['package_id' => $package->id, 'my_auto' => 1]
        );
    }

    public function renew()
    {
        $period = (int) System::getProperty('AUTO_SUBSCRIPTION_PERIOD');
        $amount = (float) System::getProperty('AUTO_SUBSCRIPTION_AMOUNT');

        $bank = [
            'bank_name'   => System::getProperty('PAY_ONLINE_BANK_NAME'),
            'branch'      => System::getProperty('PAY_ONLINE_BRANCH_NAME'),
            'account_no'  => System::getProperty('PAY_ONLINE_ACCOUNT_NO'),
            'account_name' => System::getProperty('PAY_ONLINE_ACCOUNT_NAME'),
            'swift'       => System::getProperty('PAY_ONLINE_SWIFT_CODE'),
        ];

        return view('myauto::subscription.renew', compact(
            'period',
            'amount',
            'bank'
        ));
    }

    public function payOnline(Request $request)
    {
        $request->validate([
            'period' => 'required|integer|min:1'
        ]);

        $period = (int) System::getProperty('AUTO_SUBSCRIPTION_PERIOD');
        $amount = (float) System::getProperty('AUTO_SUBSCRIPTION_AMOUNT');

        if ($request->period != $period) {
            abort(403);
        }

        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        $order = $api->order->create([
            'receipt'         => 'AUTO_' . uniqid(),
            'amount'          => $amount * 100,
            'currency'        => 'INR',
            'payment_capture' => 1
        ]);

        $subscription = MyAutoSubscription::create([
            'user_id'        => Auth::id(),
            'period'         => $period,
            'amount'         => $amount,
            'payment_mode'   => 'online',
            'payment_status' => 'pending',
            'transaction_id' => $order['id']
        ]);

        return view('myauto::subscription.razorpay_checkout', [
            'order' => $order,
            'amount' => $amount
        ]);
    }

    public function paymentCallback(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        try {

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ]);

            $subscription = MyAutoSubscription::where(
                'transaction_id',
                $request->razorpay_order_id
            )->firstOrFail();

            DB::transaction(function () use ($subscription) {

                $subscription->update([
                    'payment_status' => 'success',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($subscription->period)
                ]);

                $user = $subscription->user;
                $user->subscription_end = $subscription->ends_at;
                $user->save();
            });

            return redirect()
                ->route('myauto.subscription.renew')
                ->with('status', [
                    'success' => 1,
                    'msg' => System::getProperty('subscription_message_online_success_msg')
                ]);
        } catch (\Exception $e) {

            return redirect()
                ->route('myauto.subscription.renew')
                ->with('status', [
                    'success' => 0,
                    'msg' => 'Payment verification failed.'
                ]);
        }
    }

    public function payOffline(Request $request)
    {
        $period = (int) System::getProperty('AUTO_SUBSCRIPTION_PERIOD');
        $amount = (float) System::getProperty('AUTO_SUBSCRIPTION_AMOUNT');

        MyAutoSubscription::create([
            'user_id'        => Auth::id(),
            'period'         => $period,
            'amount'         => $amount,
            'payment_mode'   => 'offline',
            'payment_status' => 'pending'
        ]);

        return back()->with('status', [
            'success' => 1,
            'msg' => System::getProperty('subscription_message_offline_success_msg')
        ]);
    }
}
