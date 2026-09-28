<?php
namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\DuplicateKey;
use Modules\StockTransferNew\Entities\TransferLock;

class TransferSecurityService
{
    protected array $lockedStatuses = ['approved','dispatched','partially_received','received','closed','cancelled','rejected'];

    public function assertEditable($transfer): void
    {
        if (!$transfer) {
            throw new \RuntimeException('Transfer record not found.');
        }
        if (in_array((string) $transfer->status, $this->lockedStatuses, true)) {
            throw new \RuntimeException('This transfer is locked by workflow status and cannot be edited.');
        }
        if (TransferLock::active()->where('transfer_id', $transfer->id)->exists()) {
            throw new \RuntimeException('This transfer is currently locked for processing.');
        }
    }

    public function lock($transfer, string $lockType, string $reason, array $meta = []): TransferLock
    {
        return TransferLock::firstOrCreate([
            'transfer_id' => $transfer->id,
            'lock_type' => $lockType,
            'released_at' => null,
        ], [
            'business_id' => session('business.id') ?? session('user.business_id') ?? null,
            'reason' => $reason,
            'locked_by' => Auth::id(),
            'locked_at' => Carbon::now(),
            'meta' => $meta,
        ]);
    }

    public function release(TransferLock $lock, string $reason = null): void
    {
        $lock->update([
            'released_by' => Auth::id(),
            'released_at' => Carbon::now(),
            'release_reason' => $reason,
        ]);
    }

    public function registerDuplicateKey(string $action, string $key, array $payload = [], int $minutes = 60): DuplicateKey
    {
        $businessId = session('business.id') ?? session('user.business_id') ?? null;
        $payloadHash = hash('sha256', json_encode($payload));
        $existing = DuplicateKey::where('business_id', $businessId)
            ->where('action', $action)
            ->where('duplicate_key', $key)
            ->where('payload_hash', $payloadHash)
            ->where('status', 'active')
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at','>',Carbon::now()); })
            ->first();
        if ($existing) {
            throw new \RuntimeException('Duplicate request blocked. Please refresh and check the transfer before retrying.');
        }
        return DuplicateKey::create([
            'business_id' => $businessId,
            'action' => $action,
            'duplicate_key' => $key,
            'payload_hash' => $payloadHash,
            'status' => 'active',
            'expires_at' => Carbon::now()->addMinutes($minutes),
            'user_id' => Auth::id(),
            'meta' => ['payload_sample' => array_slice($payload, 0, 10, true)],
        ]);
    }

    public function guardedTransaction(callable $callback)
    {
        return DB::transaction($callback, 3);
    }
}
