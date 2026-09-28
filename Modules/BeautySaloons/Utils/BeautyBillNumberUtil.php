<?php
namespace Modules\BeautySaloons\Utils;

class BeautyBillNumberUtil
{
    public static function make(int $sequence): string
    {
        return 'BSB-' . date('Ymd') . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }
}
