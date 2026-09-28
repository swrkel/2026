<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\MyHealthMembers\Entities\MyHealthMedicalHistory;
use Modules\MyHealthMembers\Services\MyHealthQrService;

class MyHealthProfileApiController extends MyHealthApiBaseController
{
    public function profile()
    {
        return $this->success(['member' => $this->memberPayload($this->member())]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:191',
            'emergency_contact_mobile' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:20',
        ]);

        $member = $this->member();
        $member->fill($data)->save();

        return $this->success(['member' => $this->memberPayload($member->fresh())], 'Profile updated successfully.');
    }

    public function medicalHistory()
    {
        $history = MyHealthMedicalHistory::where('member_id', $this->member()->id)->latest('id')->first();
        return $this->success(['history' => $history]);
    }

    public function qr(MyHealthQrService $qrService)
    {
        $member = $this->member();
        return $this->success([
            'qr_token' => $qrService->ensureToken($member),
            'qr_url' => $qrService->qrUrl($member),
        ]);
    }
}
