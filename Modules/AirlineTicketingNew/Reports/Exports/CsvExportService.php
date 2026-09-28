<?php

namespace Modules\AirlineTicketingNew\Reports\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function download(iterable $rows, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($field) => data_get($row, $field), array_values($columns)));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
