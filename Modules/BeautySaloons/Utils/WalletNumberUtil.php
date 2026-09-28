<?php

namespace Modules\BeautySaloons\Utils;

class WalletNumberUtil
{
    public static function walletNo(int $id): string
    {
        return 'BSW-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public static function transactionNo(int $id): string
    {
        return 'BSWT-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
