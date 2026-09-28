<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;

class MyHealthMemberCodeService
{
    public function nextCode(): string
    {
        $lastCode = MyHealthMember::whereNotNull('myhealth_code')
            ->where('myhealth_code', 'REGEXP', '^[0-9]+$')
            ->orderByRaw('LENGTH(myhealth_code) DESC')
            ->orderBy('myhealth_code', 'DESC')
            ->value('myhealth_code');

        $nextNumber = $lastCode ? ((int) $lastCode + 1) : 1;
        $digits = max(6, strlen((string) $nextNumber));

        do {
            $code = str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);
            $nextNumber++;
            $digits = max(6, strlen((string) $nextNumber));
        } while (MyHealthMember::where('myhealth_code', $code)->exists());

        return $code;
    }

    public function nextLoginCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (MyHealthMemberLogin::where('login_code', $code)->exists());

        return $code;
    }
}
