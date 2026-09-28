<?php
namespace Modules\DistributionNew\Services\Scanner;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Entities\DisnewScannerSession;
use Modules\DistributionNew\Entities\DisnewScannerScan;

class DisnewScannerService
{
    public function startSession(array $data): DisnewScannerSession
    {
        $data['status'] = $data['status'] ?? 'open';
        $data['started_at'] = $data['started_at'] ?? now();
        return DisnewScannerSession::create($data);
    }

    public function recordScan(array $data): DisnewScannerScan
    {
        return DB::transaction(function () use ($data) {
            $data['scan_status'] = $data['scan_status'] ?? 'accepted';
            $data['scanned_at'] = $data['scanned_at'] ?? now();
            return DisnewScannerScan::create($data);
        });
    }

    public function closeSession(DisnewScannerSession $session, array $extra = []): DisnewScannerSession
    {
        $session->update(array_merge(['status'=>'closed','closed_at'=>now()], $extra));
        return $session;
    }
}
