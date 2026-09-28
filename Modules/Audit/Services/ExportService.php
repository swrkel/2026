<?php
namespace Modules\Audit\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function csv($rows, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) $line[] = data_get($row, $key);
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function excelHtml($rows, array $columns, string $filename)
    {
        $html = '<table border="1"><tr>';
        foreach ($columns as $label) $html .= '<th>'.e($label).'</th>';
        $html .= '</tr>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (array_keys($columns) as $key) $html .= '<td>'.e((string)data_get($row, $key)).'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
