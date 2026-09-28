<?php

namespace App\Http\Controllers;

use App\Services\Documents\GlobalPdfService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One authenticated browser-to-PDF endpoint for every module and DataTables
 * export. It removes the need for pdfMake, jsPDF, html2pdf and module-specific
 * PDF endpoints while preserving tenant/session context.
 */
class GlobalDocumentOutputController extends Controller
{
    public function pdf(Request $request, GlobalPdfService $pdfService)
    {
        $validated = $request->validate([
            'html' => ['required', 'string', 'max:12000000'],
            'filename' => ['nullable', 'string', 'max:180'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'business_id' => ['nullable', 'integer'],
            'business_name' => ['nullable', 'string', 'max:500'],
            'location_id' => ['nullable'],
            'business_location' => ['nullable', 'string', 'max:1000'],
            'date_range' => ['nullable', 'string', 'max:500'],
            'page_size' => ['nullable', Rule::in(['A3', 'A4', 'A5', 'LETTER', 'LEGAL'])],
            'orientation' => ['nullable', Rule::in(['P', 'L'])],
        ]);

        $filename = $this->safeFilename($validated['filename'] ?? 'document.pdf');
        $pageSize = strtoupper((string) ($validated['page_size'] ?? 'A4'));
        $orientation = strtoupper((string) ($validated['orientation'] ?? 'P'));
        $html = $this->safeReportHtml((string) $validated['html']);

        return $pdfService->download(
            $this->documentCss() . $html,
            $filename,
            [
                'format' => $pageSize,
                'orientation' => $orientation,
                'margin_left' => 8,
                'margin_right' => 8,
            ],
            [
                'business_id' => $validated['business_id'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'location_id' => $validated['location_id'] ?? null,
                'business_location' => $validated['business_location'] ?? null,
                'page_title' => $validated['page_title'] ?? 'Document',
                'date_range' => $validated['date_range'] ?? 'All Dates',
            ]
        );
    }

    private function safeFilename($filename)
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename((string) $filename));
        $filename = trim((string) $filename, '._-');

        if ($filename === '') {
            $filename = 'document.pdf';
        }

        if (strtolower(substr($filename, -4)) !== '.pdf') {
            $filename .= '.pdf';
        }

        return $filename;
    }

    private function safeReportHtml($html)
    {
        $html = preg_replace('/<(script|iframe|object|embed|form)\b[^>]*>.*?<\/\1>/is', '', (string) $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/is', '', (string) $html);
        $html = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', '', (string) $html);

        return (string) $html;
    }

    private function documentCss()
    {
        return '<style>'
            . 'body{font-family:dejavusans,Arial,sans-serif;font-size:9pt;color:#111;}'
            . 'h1,h2{font-size:13pt;text-align:center;margin:0 0 5mm;}'
            . 'h3,h4{font-size:11pt;margin:5mm 0 2mm;}'
            . 'table{width:100%;border-collapse:collapse;margin-bottom:5mm;}'
            . 'th,td{border:0.2mm solid #777;padding:1.4mm 1.8mm;vertical-align:top;}'
            . 'th{background:#f0f2f4;font-weight:bold;}'
            . '.text-right{text-align:right;}.text-center{text-align:center;}'
            . '.no-print,.notexport,.dataTables_empty{display:none!important;}'
            . '</style>';
    }
}
