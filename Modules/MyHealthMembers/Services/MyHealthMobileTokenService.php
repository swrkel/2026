<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMobileAccessToken;

class MyHealthMobileTokenService
{
    public function issue(MyHealthMember $member, Request $request): array
    {
        $plainToken = Str::random(80);
        $days = (int) config('myhealthmembers.mobile_token_expiry_days', 30);

        $record = MyHealthMobileAccessToken::create([
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $plainToken),
            'device_name' => $request->input('device_name'),
            'device_id' => $request->input('device_id'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'expires_at' => $days > 0 ? Carbon::now()->addDays($days) : null,
        ]);

        return ['token' => $plainToken, 'record' => $record];
    }

    public function findValid(string $plainToken): ?MyHealthMobileAccessToken
    {
        $token = MyHealthMobileAccessToken::where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        $token->forceFill(['last_used_at' => Carbon::now()])->save();

        return $token;
    }

    public function revoke(string $plainToken): void
    {
        MyHealthMobileAccessToken::where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }
}
