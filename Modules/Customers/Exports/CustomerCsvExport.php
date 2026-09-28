<?php

namespace Modules\Customers\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerCsvExport
{
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);
        if (!str_ends_with(strtolower($safeFilename), '.csv')) {
            $safeFilename .= '.csv';
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $safeFilename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function value($row, string $key, $default = '')
    {
        if (is_array($row)) {
            return $row[$key] ?? $default;
        }

        if (is_object($row)) {
            return $row->{$key} ?? $default;
        }

        return $default;
    }

    protected function dateValue($value): string
    {
        return !empty($value) ? date('Y-m-d', strtotime($value)) : '';
    }

    protected function moneyValue($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
