<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadsNewReportExportService
{
    public function csv(Collection $rows, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($key) => data_get($row, $key), array_keys($columns)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
