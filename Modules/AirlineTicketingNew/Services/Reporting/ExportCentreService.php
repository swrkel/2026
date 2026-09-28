<?php
namespace Modules\AirlineTicketingNew\Services\Reporting;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCentreService
{
    public function csv(iterable $rows, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn ($field) => data_get($row, $field),
                    array_values($columns)
                ));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function json(iterable $rows, string $filename)
    {
        return response()->streamDownload(function () use ($rows): void {
            echo json_encode(collect($rows)->values(), JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
