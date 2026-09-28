<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Str;

class TransferQrService
{
    public function token(?string $existing = null): string
    {
        return $existing ?: 'STNQR-'.Str::upper(Str::random(24));
    }

    public function payload(object $transfer): array
    {
        return [
            'module' => 'StockTransferNew',
            'transfer_id' => $transfer->id ?? null,
            'transfer_no' => $transfer->transfer_no ?? null,
            'qr_token' => $transfer->qr_token ?? null,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
