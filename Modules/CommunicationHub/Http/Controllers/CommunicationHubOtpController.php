<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\CommunicationHub\Entities\CommunicationHubOtp;
use Modules\CommunicationHub\Services\Messaging\EnterpriseMessagingEngine;

class CommunicationHubOtpController extends Controller
{
    public function index() { $otps = CommunicationHubOtp::latest()->paginate(25); return view('communicationhub::otp.index', compact('otps')); }

    public function generate(Request $request, EnterpriseMessagingEngine $engine)
    {
        $data = $request->validate(['recipient'=>'required|string|max:191','channel'=>'required|string|max:50','purpose'=>'nullable|string|max:100']);
        $otp = (string) random_int(100000, 999999);
        CommunicationHubOtp::create(['recipient'=>$data['recipient'],'channel'=>$data['channel'],'purpose'=>$data['purpose'] ?? 'general','otp_hash'=>Hash::make($otp),'expires_at'=>now()->addMinutes(config('communicationhub.otp.expiry_minutes',5)),'status'=>'pending','attempts'=>0]);
        $engine->send(['channel'=>$data['channel'],'recipient'=>$data['recipient'],'body'=>'Your OTP is '.$otp,'source_module'=>'CommunicationHub','source_reference'=>'otp'], false);
        return back()->with('status', 'OTP generated and queued.');
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['recipient'=>'required|string|max:191','otp'=>'required|string|max:10']);
        $record = CommunicationHubOtp::where('recipient',$data['recipient'])->where('status','pending')->latest()->first();
        if (! $record || $record->expires_at < now() || ! Hash::check($data['otp'], $record->otp_hash)) {
            if ($record) { $record->increment('attempts'); }
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }
        $record->update(['status'=>'verified','verified_at'=>now()]);
        return back()->with('status', 'OTP verified successfully.');
    }
}
