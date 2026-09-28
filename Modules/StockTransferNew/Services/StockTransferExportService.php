<?php

namespace Modules\StockTransferNew\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class StockTransferExportService
{
    public function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
