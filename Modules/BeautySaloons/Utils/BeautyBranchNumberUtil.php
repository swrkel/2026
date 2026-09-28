<?php

namespace Modules\BeautySaloons\Utils;

class BeautyBranchNumberUtil
{
    public static function nextBranchCode(int $nextId): string
    {
        return 'BSB-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    public static function nextResourceCode(int $nextId): string
    {
        return 'BSR-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
