<?php
namespace Modules\BeautySaloons\Utils;

class BeautyInventoryNumberUtil
{
    public static function nextInvoiceNo(int $id): string
    {
        return 'BSPOS-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }
}
