<?php

namespace Modules\MyHealthMembers\Http\Controllers\Business;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAccessPasscode;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthAccessController extends Controller
{
    public function requestPasscode(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_view_profile'), 403);

        $passcode = (string) random_int(100000, 999999);

        MyHealthAccessPasscode::create([
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'requested_by' => auth()->id(),
            'passcode' => $passcode,
            'purpose' => $request->input('purpose', 'member_access'),
            'expires_at' => now()->addMinutes(config('myhealthmembers.passcode_expiry_minutes', 10)),
        ]);

        // Next step: connect existing SMS/email utility here.
        // Send $passcode to $member->mobile and $member->email.

        return back()->with('status', __('myhealthmembers::lang.passcode_sent_to_member'));
    }

    public function verifyPasscode(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService)
    {
        $request->validate(['passcode' => ['required', 'string']]);

        $record = MyHealthAccessPasscode::where('member_id', $member->id)
            ->where('business_id', $permissionService->businessId())
            ->where('passcode', $request->passcode)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (empty($record)) {
            return back()->withErrors(['passcode' => __('myhealthmembers::lang.invalid_or_expired_passcode')]);
        }

        $record->update(['used_at' => now()]);
        session()->put('myhealth_access_member_' . $member->id, true);

        return redirect()->route('myhealth.members.show', $member->id);
    }
}
