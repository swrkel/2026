<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\StockTransferNew\Entities\ScanLine;
use Modules\StockTransferNew\Entities\ScanSession;

class WarehouseScanService
{
    public function openSession(array $data): ScanSession
    {
        return ScanSession::firstOrCreate([
            'business_id' => $data['business_id'],
            'transfer_id' => $data['transfer_id'] ?? null,
            'scan_type' => $data['scan_type'],
            'status' => 'open',
        ], [
            'session_no' => $this->nextSessionNo($data['business_id'], $data['scan_type']),
            'location_id' => $data['location_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'device_code' => $data['device_code'] ?? request()->header('User-Agent'),
            'operator_id' => Auth::id(),
            'started_at' => now(),
            'created_by' => Auth::id(),
        ]);
    }

    public function recordScan(ScanSession $session, array $payload): ScanLine
    {
        return DB::transaction(function () use ($session, $payload) {
            $qty = (float)($payload['qty'] ?? 1);
            $line = ScanLine::firstOrNew([
                'business_id' => $session->business_id,
                'scan_session_id' => $session->id,
                'transfer_line_id' => $payload['transfer_line_id'] ?? null,
                'barcode' => $payload['barcode'] ?? null,
                'batch_no' => $payload['batch_no'] ?? null,
            ]);

            $line->transfer_id = $session->transfer_id;
            $line->product_id = $payload['product_id'] ?? $line->product_id;
            $line->sku = $payload['sku'] ?? $line->sku;
            $line->expected_qty = $payload['expected_qty'] ?? $line->expected_qty ?? 0;
            $line->scanned_qty = (float)($line->scanned_qty ?? 0) + $qty;
            $line->variance_qty = (float)$line->scanned_qty - (float)$line->expected_qty;
            $line->expiry_date = $payload['expiry_date'] ?? $line->expiry_date;
            $line->scan_count = (int)($line->scan_count ?? 0) + 1;
            $line->last_scanned_at = now();
            $line->notes = $payload['notes'] ?? $line->notes;
            $line->save();

            return $line;
        });
    }

    public function summary(ScanSession $session): array
    {
        $lines = $session->lines()->get();
        return [
            'line_count' => $lines->count(),
            'expected_qty' => $lines->sum('expected_qty'),
            'scanned_qty' => $lines->sum('scanned_qty'),
            'variance_qty' => $lines->sum('variance_qty'),
            'has_variance' => abs((float)$lines->sum('variance_qty')) > 0.0001,
        ];
    }

    public function submit(ScanSession $session, ?string $remarks = null): ScanSession
    {
        $session->status = 'submitted';
        $session->submitted_at = now();
        $session->remarks = $remarks;
        $session->updated_by = Auth::id();
        $session->save();
        return $session;
    }

    protected function nextSessionNo(int $businessId, string $type): string
    {
        return strtoupper($type).'-'.date('Ymd').'-'.str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
