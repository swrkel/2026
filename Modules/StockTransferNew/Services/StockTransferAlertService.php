<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferAlertService
{
    public function record(StockTransfer $transfer, string $event, string $message, ?int $userId = null): void
    {
        if (!Schema::hasTable('stnew_stock_transfer_alerts')) {
            return;
        }

        DB::table('stnew_stock_transfer_alerts')->insert([
            'business_id' => $transfer->business_id,
            'transfer_id' => $transfer->id,
            'event' => $event,
            'message' => $message,
            'target_user_id' => $userId,
            'is_read' => 0,
            'created_by' => StockTransferTenant::userId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
