<?php

namespace Modules\SimpleAudit\Services;

use Modules\SimpleAudit\Support\NumberFormat;

class ReportExportService
{
    public function filename(array $report, $extension)
    {
        $business = preg_replace('/[^A-Za-z0-9_-]+/', '_', $report['meta']['business_name'] ?? 'Business');
        return 'Simple_Audit_Purchase_Audit_' . $business . '_' . ($report['meta']['from'] ?? '') . '_to_' . ($report['meta']['to'] ?? '') . '.' . $extension;
    }

    public function csv(array $report)
    {
        $fh = fopen('php://temp', 'r+');
        $this->writeMetaCsv($fh, $report);
        foreach ($this->sectionDefinitions() as $section => $def) {
            fputcsv($fh, []);
            fputcsv($fh, [$def['title']]);
            fputcsv($fh, array_values($def['columns']));
            foreach (($report['sections'][$section]['rows'] ?? []) as $row) {
                $line = [];
                foreach ($def['columns'] as $key => $label) {
                    $line[] = $this->exportValue($report, $section, $key, $row[$key] ?? null, $row);
                }
                fputcsv($fh, $line);
            }
            if (!empty($report['sections'][$section]['totals'])) {
                $totals = $report['sections'][$section]['totals'];
                $line = [];
                foreach ($def['columns'] as $key => $label) {
                    $line[] = $key === array_key_first($def['columns']) ? strtoupper(__('simpleaudit::simpleaudit.total')) : $this->exportValue($report, $section, $key, $totals[$key] ?? null, []);
                }
                fputcsv($fh, $line);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return "\xEF\xBB\xBF" . $csv;
    }

    public function xls(array $report)
    {
        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:Arial,sans-serif;font-size:11pt}h1{font-size:16pt}h2{font-size:13pt;background:#f1f5f9;padding:6px}'
            . 'table{border-collapse:collapse;margin-bottom:18px}th,td{border:1px solid #9ca3af;padding:5px 7px}th{background:#e5e7eb}.num{text-align:right}tfoot td{font-weight:bold;background:#f8fafc}'
            . '</style></head><body>';
        $html .= '<h1>' . e(__('simpleaudit::simpleaudit.module_name')) . ' — ' . e(__('simpleaudit::simpleaudit.purchase_audit')) . '</h1>';
        $html .= '<p><strong>' . e(__('simpleaudit::simpleaudit.business')) . ':</strong> ' . e($report['meta']['business_name'])
            . ' &nbsp; <strong>' . e(__('simpleaudit::simpleaudit.location')) . ':</strong> ' . e($report['meta']['location_name'])
            . ' &nbsp; <strong>' . e(__('simpleaudit::simpleaudit.store')) . ':</strong> ' . e($report['meta']['store_name'])
            . ' &nbsp; <strong>' . e(__('simpleaudit::simpleaudit.date_period')) . ':</strong> ' . e($report['meta']['from'] . ' ' . __('simpleaudit::simpleaudit.to') . ' ' . $report['meta']['to']) . '</p>';

        foreach ($this->sectionDefinitions() as $section => $def) {
            $html .= '<h2>' . e($def['title']) . '</h2><table><thead><tr>';
            foreach ($def['columns'] as $label) $html .= '<th>' . e($label) . '</th>';
            $html .= '</tr></thead><tbody>';
            foreach (($report['sections'][$section]['rows'] ?? []) as $row) {
                $html .= '<tr>';
                foreach ($def['columns'] as $key => $label) {
                    $numeric = $this->isNumericColumn($key);
                    $html .= '<td' . ($numeric ? ' class="num"' : '') . '>' . e($this->exportValue($report, $section, $key, $row[$key] ?? null, $row)) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            if (!empty($report['sections'][$section]['totals'])) {
                $totals = $report['sections'][$section]['totals'];
                $html .= '<tfoot><tr>';
                foreach ($def['columns'] as $key => $label) {
                    $value = $key === array_key_first($def['columns']) ? strtoupper(__('simpleaudit::simpleaudit.total')) : $this->exportValue($report, $section, $key, $totals[$key] ?? null, []);
                    $html .= '<td' . ($this->isNumericColumn($key) ? ' class="num"' : '') . '>' . e($value) . '</td>';
                }
                $html .= '</tr></tfoot>';
            }
            $html .= '</table>';
        }
        return $html . '</body></html>';
    }

    public function pdf(array $report)
    {
        $lines = [];
        $lines[] = strtoupper(__('simpleaudit::simpleaudit.module_name') . ' - ' . __('simpleaudit::simpleaudit.purchase_audit'));
        $lines[] = __('simpleaudit::simpleaudit.business') . ': ' . ($report['meta']['business_name'] ?? '');
        $lines[] = __('simpleaudit::simpleaudit.location') . ': ' . ($report['meta']['location_name'] ?? '') . ' | ' . __('simpleaudit::simpleaudit.store') . ': ' . ($report['meta']['store_name'] ?? '');
        $lines[] = __('simpleaudit::simpleaudit.date_period') . ': ' . ($report['meta']['from'] ?? '') . ' ' . __('simpleaudit::simpleaudit.to') . ' ' . ($report['meta']['to'] ?? '');
        $lines[] = str_repeat('-', 112);

        foreach ($this->sectionDefinitions() as $section => $def) {
            $lines[] = '';
            $lines[] = strtoupper($def['title']);
            $lines[] = str_repeat('-', 112);
            $headers = array_values($def['columns']);
            $keys = array_keys($def['columns']);
            $lines[] = $this->fixedLine($headers, $keys);
            $lines[] = str_repeat('-', 112);
            foreach (($report['sections'][$section]['rows'] ?? []) as $row) {
                $values = [];
                foreach ($keys as $key) $values[] = $this->exportValue($report, $section, $key, $row[$key] ?? null, $row);
                $lines[] = $this->fixedLine($values, $keys);
            }
            if (!empty($report['sections'][$section]['totals'])) {
                $totals = $report['sections'][$section]['totals'];
                $values = [];
                foreach ($keys as $i => $key) $values[] = $i === 0 ? strtoupper(__('simpleaudit::simpleaudit.total')) : $this->exportValue($report, $section, $key, $totals[$key] ?? null, []);
                $lines[] = str_repeat('-', 112);
                $lines[] = $this->fixedLine($values, $keys);
            }
        }

        return $this->makePdf($lines);
    }

    public function sectionDefinitions()
    {
        return [
            'purchases' => [
                'title' => __('simpleaudit::simpleaudit.purchase'),
                'columns' => [
                    'product' => __('simpleaudit::simpleaudit.product'), 'qty' => __('simpleaudit::simpleaudit.qty'),
                    'unit_cost' => __('simpleaudit::simpleaudit.unit_cost'), 'total' => __('simpleaudit::simpleaudit.total'),
                    'discount' => __('simpleaudit::simpleaudit.discount'), 'tax' => __('simpleaudit::simpleaudit.tax')
                ],
            ],
            'stock_movements' => [
                'title' => __('simpleaudit::simpleaudit.stock_movements'),
                'columns' => [
                    'product' => __('simpleaudit::simpleaudit.product'), 'before' => __('simpleaudit::simpleaudit.before'),
                    'purchases' => __('simpleaudit::simpleaudit.purchases'), 'purchase_return' => __('simpleaudit::simpleaudit.purchase_return'),
                    'stock_adjustment' => __('simpleaudit::simpleaudit.stock_adjustment'), 'after' => __('simpleaudit::simpleaudit.after'),
                    'difference' => __('simpleaudit::simpleaudit.difference')
                ],
            ],
            'supplier_payments' => [
                'title' => __('simpleaudit::simpleaudit.supplier_payments'),
                'columns' => ['supplier' => __('simpleaudit::simpleaudit.supplier'), 'before' => __('simpleaudit::simpleaudit.before'), 'after' => __('simpleaudit::simpleaudit.after'), 'difference' => __('simpleaudit::simpleaudit.difference')],
            ],
            'accounts' => [
                'title' => __('simpleaudit::simpleaudit.accounts'),
                'columns' => ['account' => __('simpleaudit::simpleaudit.account'), 'before' => __('simpleaudit::simpleaudit.before'), 'after' => __('simpleaudit::simpleaudit.after'), 'difference' => __('simpleaudit::simpleaudit.difference')],
            ],
            'supplier_ledgers' => [
                'title' => __('simpleaudit::simpleaudit.supplier_ledgers'),
                'columns' => ['supplier' => __('simpleaudit::simpleaudit.supplier'), 'before' => __('simpleaudit::simpleaudit.before'), 'after' => __('simpleaudit::simpleaudit.after'), 'difference' => __('simpleaudit::simpleaudit.difference')],
            ],
        ];
    }

    protected function writeMetaCsv($fh, array $report)
    {
        fputcsv($fh, [__('simpleaudit::simpleaudit.module_name') . ' - ' . __('simpleaudit::simpleaudit.purchase_audit')]);
        fputcsv($fh, [__('simpleaudit::simpleaudit.business'), $report['meta']['business_name'] ?? '']);
        fputcsv($fh, [__('simpleaudit::simpleaudit.location'), $report['meta']['location_name'] ?? __('simpleaudit::simpleaudit.all_locations')]);
        fputcsv($fh, [__('simpleaudit::simpleaudit.store'), $report['meta']['store_name'] ?? __('simpleaudit::simpleaudit.all_stores')]);
        fputcsv($fh, [__('simpleaudit::simpleaudit.date_period'), ($report['meta']['from'] ?? '') . ' ' . __('simpleaudit::simpleaudit.to') . ' ' . ($report['meta']['to'] ?? '')]);
    }

    protected function exportValue(array $report, $section, $key, $value, array $row)
    {
        if ($value === null) return '';
        if (!$this->isNumericColumn($key)) return (string) $value;
        $currencyPrecision = (int) ($report['precision']['currency'] ?? 2);
        $qtyPrecision = isset($row['qty_precision']) ? (int) $row['qty_precision'] : (int) ($report['precision']['quantity'] ?? 2);
        $qtyColumns = ['qty','before','purchases','purchase_return','stock_adjustment','after','difference'];
        if ($section === 'stock_movements' || ($section === 'purchases' && $key === 'qty')) {
            return NumberFormat::quantity($value, $qtyPrecision);
        }
        if (in_array($key, ['unit_cost','total','discount','tax','before','after','difference'], true)) {
            return NumberFormat::amount($value, $currencyPrecision);
        }
        return (string) $value;
    }

    protected function isNumericColumn($key)
    {
        return in_array($key, ['qty','unit_cost','total','discount','tax','before','purchases','purchase_return','stock_adjustment','after','difference'], true);
    }

    protected function fixedLine(array $values, array $keys)
    {
        $count = count($values);
        $widths = $count >= 7 ? [26,14,14,14,14,14,14] : ($count === 6 ? [30,14,16,16,16,16] : [40,22,22,22]);
        $out = '';
        foreach ($values as $i => $value) {
            $w = $widths[$i] ?? 16;
            $text = preg_replace('/\s+/', ' ', (string) $value);
            if (strlen($text) > $w - 1) $text = substr($text, 0, $w - 2) . '…';
            $numeric = isset($keys[$i]) && $this->isNumericColumn($keys[$i]);
            $out .= $numeric ? str_pad($text, $w, ' ', STR_PAD_LEFT) : str_pad($text, $w, ' ', STR_PAD_RIGHT);
        }
        return rtrim($out);
    }

    // Minimal standalone PDF writer: landscape A4, Helvetica, multi-page text.
    // This keeps the module independent from dompdf/snappy/core report packages.
    protected function makePdf(array $lines)
    {
        $pageWidth = 842;
        $pageHeight = 595;
        $left = 28;
        $top = 565;
        $lineHeight = 9;
        $fontSize = 7;
        $linesPerPage = 57;
        $chunks = array_chunk($lines, $linesPerPage);

        $objects = [];
        $catalogId = 1;
        $pagesId = 2;
        $fontId = 3;
        $objects[$fontId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $pageIds = [];
        $nextId = 4;

        foreach ($chunks as $chunk) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $stream = "BT\n/F1 {$fontSize} Tf\n";
            $y = $top;
            foreach ($chunk as $line) {
                $escaped = $this->pdfEscape($line);
                $stream .= "1 0 0 1 {$left} {$y} Tm ({$escaped}) Tj\n";
                $y -= $lineHeight;
            }
            $stream .= "ET\n";
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $kids = implode(' ', array_map(fn($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = "<< /Type /Pages /Kids [{$kids}] /Count " . count($pageIds) . " >>";
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        $maxId = max(array_keys($objects));
        for ($id = 1; $id <= $maxId; $id++) {
            $offsets[$id] = strlen($pdf);
            $body = $objects[$id] ?? '<< >>';
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    protected function pdfEscape($text)
    {
        $text = (string) $text;
        $text = str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $text);
        // Helvetica Type1 is WinAnsi-ish; replace unsupported unicode punctuation.
        $text = str_replace(['—','–','…'], ['-','-','...'], $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) $text = $converted;
        }
        return $text;
    }
}
