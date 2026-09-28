<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;

class StockTransferNumberService
{
    public function next(int $businessId): string
    {
        $lastId = (int) DB::table('stnew_stock_transfers')
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->value('id');

        return 'STN-' . date('Ymd') . '-' .
            str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
