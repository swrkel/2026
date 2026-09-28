<?php

namespace Modules\BankingTellerOperations\Services;

class TellerNumberService
{
    public function next(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
