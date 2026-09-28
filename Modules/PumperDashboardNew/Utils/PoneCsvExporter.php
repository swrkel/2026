<?php

namespace Modules\PumperDashboardNew\Utils;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PoneCsvExporter
{
    public function download(Collection $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            // UTF-8 BOM keeps spreadsheet applications from misreading non-Latin names.
            fwrite($output, "\xEF\xBB\xBF");
            $normalised = $rows->map(fn (mixed $row): array => $this->row($row));
            if ($normalised->isNotEmpty()) {
                fputcsv($output, array_keys($normalised->first()));
            }
            foreach ($normalised as $row) {
                fputcsv($output, array_map([$this, 'cell'], $row));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function row(mixed $row): array
    {
        if ($row instanceof Arrayable) {
            return $row->toArray();
        }
        if (is_object($row)) {
            return get_object_vars($row);
        }
        return (array) $row;
    }

    private function cell(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
