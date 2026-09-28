<?php

namespace Modules\PetroPDNew\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdnewReportService
{
    public function __construct(private PdnewReportRegistry $registry) {}

    public function paginate(string $key, int $businessId, array $filters, int $perPage = 50): array
    {
        $report = $this->registry->get($key);

        return [
            'report' => $report,
            'columns' => $report->columns(),
            'rows' => $report->query($businessId, $filters)
                ->paginate(max(10, min(200, $perPage)))
                ->withQueryString(),
        ];
    }

    public function export(
        string $format,
        string $key,
        int $businessId,
        array $filters
    ): Response|StreamedResponse {
        return match (strtolower($format)) {
            'csv' => $this->csv($key, $businessId, $filters),
            'excel', 'xls' => $this->excel($key, $businessId, $filters),
            'pdf' => $this->pdf($key, $businessId, $filters),
            default => throw new RuntimeException('Unsupported Petro PD-New report export format.'),
        };
    }

    public function printable(string $key, int $businessId, array $filters): array
    {
        $report = $this->registry->get($key);

        return [
            'report' => $report,
            'columns' => $report->columns(),
            'rows' => $this->rows($key, $businessId, $filters),
            'filters' => $filters,
            'generatedAt' => now(),
        ];
    }

    public function csv(string $key, int $businessId, array $filters): StreamedResponse
    {
        $report = $this->registry->get($key);
        $columns = $report->columns();
        $filename = $this->filename($key, 'csv');

        return response()->streamDownload(function () use ($report, $columns, $businessId, $filters): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_values($columns));

            $report->query($businessId, $filters)->chunk(500, function ($rows) use ($stream, $columns): void {
                foreach ($rows as $row) {
                    $array = (array) $row;
                    fputcsv($stream, array_map(
                        fn (string $column) => $array[$column] ?? '',
                        array_keys($columns)
                    ));
                }
            });

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function excel(string $key, int $businessId, array $filters): StreamedResponse
    {
        $report = $this->registry->get($key);
        $columns = $report->columns();
        $filename = $this->filename($key, 'xls');

        return response()->streamDownload(function () use ($report, $columns, $businessId, $filters): void {
            echo "<html><head><meta charset=\"UTF-8\"></head><body><table border=\"1\"><thead><tr>";
            foreach ($columns as $label) {
                echo '<th>' . e($label) . '</th>';
            }
            echo '</tr></thead><tbody>';

            $report->query($businessId, $filters)->chunk(500, function ($rows) use ($columns): void {
                foreach ($rows as $row) {
                    $array = (array) $row;
                    echo '<tr>';
                    foreach (array_keys($columns) as $column) {
                        echo '<td>' . e((string) ($array[$column] ?? '')) . '</td>';
                    }
                    echo '</tr>';
                }
            });

            echo '</tbody></table></body></html>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function pdf(string $key, int $businessId, array $filters): Response
    {
        if (! class_exists(Pdf::class)) {
            throw new RuntimeException('The PDF renderer is not installed in this application.');
        }

        $data = $this->printable($key, $businessId, $filters);
        $document = Pdf::loadView('petropdnew::reports.print', $data)
            ->setPaper('a4', 'landscape');

        return $document->download($this->filename($key, 'pdf'));
    }

    private function rows(string $key, int $businessId, array $filters): Collection
    {
        return $this->registry->get($key)
            ->query($businessId, $filters)
            ->limit(10000)
            ->get();
    }

    private function filename(string $key, string $extension): string
    {
        return 'petro-pd-new-'
            . str_replace('_', '-', $key)
            . '-'
            . now()->format('Ymd-His')
            . '.'
            . $extension;
    }
}
