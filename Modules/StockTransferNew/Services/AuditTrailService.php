<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\StockTransferNew\Entities\AuditLog;

class AuditTrailService
{
    public function record(string $event, string $entityType, $entityId = null, array $old = [], array $new = [], array $meta = []): AuditLog
    {
        return AuditLog::create([
            'business_id' => session('business.id') ?? session('user.business_id') ?? null,
            'location_id' => $meta['location_id'] ?? null,
            'store_id' => $meta['store_id'] ?? null,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'meta' => $meta,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);
    }

    public function transferEvent($transfer, string $event, array $old = [], array $new = [], array $meta = []): AuditLog
    {
        $meta = array_merge([
            'location_id' => $transfer->from_location_id ?? null,
            'store_id' => $transfer->from_store_id ?? null,
            'transfer_no' => $transfer->transfer_no ?? null,
            'status' => $transfer->status ?? null,
        ], $meta);

        return $this->record($event, 'stock_transfer', $transfer->id ?? null, $old, $new, $meta);
    }
}
