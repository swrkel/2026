<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthMember;

class MyHealthQrService
{
    public function ensureToken(MyHealthMember $member): string
    {
        if (! empty($member->qr_token)) {
            return $member->qr_token;
        }

        do {
            $token = Str::random(48);
        } while (MyHealthMember::where('qr_token', $token)->exists());

        $member->update(['qr_token' => $token]);

        return $token;
    }

    public function qrUrl(MyHealthMember $member): string
    {
        return url('/myhealth-register/login?code=' . urlencode($member->myhealth_code));
    }
}
