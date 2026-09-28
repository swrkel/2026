<?php

namespace Modules\IdentityAccess\Services\Otp;

use Illuminate\Support\Facades\Hash;
use Modules\IdentityAccess\Entities\IdentityAccessLoginIdentity;
use Modules\IdentityAccess\Entities\IdentityAccessOtp;

class IdentityOtpService
{
    public function create(IdentityAccessLoginIdentity $identity): array
    {
        $otp = (string) random_int(100000, 999999);
        $record = IdentityAccessOtp::create([
            'business_id' => $identity->business_id,
            'login_identity_id' => $identity->id,
            'portal_type' => $identity->portal_type,
            'otp_hash' => Hash::make($otp),
            'delivery_email' => $identity->email,
            'delivery_mobile' => $identity->mobile,
            'expires_at' => now()->addMinutes(config('identityaccess.otp_expiry_minutes', 5)),
            'status' => 'pending',
        ]);
        return ['record' => $record, 'otp' => $otp];
    }

    public function verify(IdentityAccessOtp $record, string $otp): bool
    {
        if ($record->status !== 'pending' || now()->greaterThan($record->expires_at)) {
            return false;
        }
        $record->increment('attempts');
        if (!Hash::check($otp, $record->otp_hash)) {
            return false;
        }
        $record->update(['status' => 'verified', 'verified_at' => now()]);
        return true;
    }
}
