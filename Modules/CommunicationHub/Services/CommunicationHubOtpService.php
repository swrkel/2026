<?php

namespace Modules\CommunicationHub\Services;

use Illuminate\Support\Facades\Hash;
use Modules\CommunicationHub\Entities\CommunicationHubOtp;

class CommunicationHubOtpService
{
    public function generate(array $data): CommunicationHubOtp
    {
        $otp = (string) random_int(100000, 999999);

        return CommunicationHubOtp::create([
            'business_id' => $data['business_id'] ?? session('business.id'),
            'module' => $data['module'] ?? 'general',
            'purpose' => $data['purpose'] ?? 'login',
            'identifier' => $data['identifier'],
            'otp_hash' => Hash::make($otp),
            'plain_otp_preview' => app()->environment(['local', 'testing']) ? $otp : null,
            'expires_at' => now()->addMinutes((int) config('communicationhub.otp_expiry_minutes', 5)),
            'attempts' => 0,
            'status' => 'pending',
        ]);
    }

    public function verify(string $identifier, string $otp, ?string $purpose = null): bool
    {
        $query = CommunicationHubOtp::where('identifier', $identifier)
            ->where('status', 'pending')
            ->where('expires_at', '>=', now())
            ->latest();

        if ($purpose) {
            $query->where('purpose', $purpose);
        }

        $record = $query->first();
        if (! $record) {
            return false;
        }

        $record->increment('attempts');
        if ($record->attempts > (int) config('communicationhub.otp_max_attempts', 3)) {
            $record->update(['status' => 'blocked']);
            return false;
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            return false;
        }

        $record->update(['status' => 'verified', 'verified_at' => now()]);
        return true;
    }
}
