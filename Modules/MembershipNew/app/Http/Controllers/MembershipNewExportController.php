<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipNewExportController extends Controller
{
    public function csv(string $report): StreamedResponse
    {
        $filename = 'membership_new_' . $report . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', $report]);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Note', 'Hook ready. Full report-specific export mapping can be extended without changing other modules.']);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
