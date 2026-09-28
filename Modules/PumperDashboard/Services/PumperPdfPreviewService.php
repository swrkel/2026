<?php

namespace Modules\PumperDashboard\Services;

use Illuminate\Support\Facades\Log;

class PumperPdfPreviewService
{
    /**
     * Render a module-owned Blade view as an inline PDF.
     *
     * Direct Dompdf is preferred deliberately. Some installations decorate the
     * Laravel wrapper with a generic report header, which is not suitable for
     * receipts and statements. The wrapper remains a safe second choice.
     */
    public function stream(
        string $view,
        array $data,
        string $fileName,
        string $paper = 'a4',
        string $orientation = 'portrait',
        array $logContext = []
    ) {
        $safeFileName = $this->sanitizeFileName($fileName);
        $html = view($view, $data)->render();
        $errors = [];

        if (class_exists(\Dompdf\Dompdf::class) && class_exists(\Dompdf\Options::class)) {
            try {
                $options = new \Dompdf\Options();
                $options->set('defaultFont', 'DejaVu Sans');
                $options->set('isRemoteEnabled', false);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isPhpEnabled', false);
                $options->set('dpi', 96);

                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->setBasePath(public_path());
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper($paper, $orientation);
                $dompdf->render();

                return response($dompdf->output(), 200, $this->inlineHeaders($safeFileName));
            } catch (\Throwable $exception) {
                $errors[] = 'direct: ' . $exception->getMessage();
                Log::warning('Pumper Dashboard direct PDF render failed; trying wrapper.', array_merge($logContext, [
                    'view' => $view,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]));
            }
        }

        if (app()->bound('dompdf.wrapper')) {
            try {
                $pdf = app('dompdf.wrapper');
                $pdf->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => false,
                    'dpi' => 96,
                ]);
                $pdf->loadHTML($html);
                $pdf->setPaper($paper, $orientation);

                return $pdf->stream($safeFileName, ['Attachment' => false]);
            } catch (\Throwable $exception) {
                $errors[] = 'wrapper: ' . $exception->getMessage();
                Log::warning('Pumper Dashboard PDF wrapper render failed; trying another PDF engine.', array_merge($logContext, [
                    'view' => $view,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]));
            }
        }

        if ($view === 'pumperdashboard::actions.closed_pumps_statement_pdf') {
            /*
             * IS2306: fallback engines do not all understand the richer browser/
             * Dompdf stylesheet in the normal view. Use a deliberately simple,
             * table-only PDF view for them. This prevents the blank multi-page
             * output seen in the print preview while still producing a real PDF
             * (so browser date/title/URL/page-number bands are not added).
             */
            $engineHtml = view('pumperdashboard::actions.closed_pumps_statement_engine_pdf', $data)->render();

            if (class_exists(\Mpdf\Mpdf::class)) {
                try {
                    $landscape = strtolower($orientation) === 'landscape';
                    $format = strtolower($paper) === 'a4' ? ($landscape ? 'A4-L' : 'A4') : strtoupper($paper);
                    $mpdf = new \Mpdf\Mpdf([
                        'format' => $format,
                        'margin_left' => 6,
                        'margin_right' => 6,
                        'margin_top' => 6,
                        'margin_bottom' => 6,
                        'margin_header' => 0,
                        'margin_footer' => 0,
                    ]);
                    $mpdf->SetTitle('');
                    $mpdf->WriteHTML($engineHtml);
                    $bytes = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

                    return response($bytes, 200, $this->inlineHeaders($safeFileName));
                } catch (\Throwable $exception) {
                    $errors[] = 'mpdf: ' . $exception->getMessage();
                    Log::warning('Pumper Dashboard mPDF render failed; trying another PDF engine.', array_merge($logContext, [
                        'view' => $view,
                        'message' => $exception->getMessage(),
                    ]));
                }
            }

            // wkhtmltopdf/Snappy handles HTML tables reliably and should be used
            // before TCPDF when both happen to be installed.
            if (app()->bound('snappy.pdf.wrapper')) {
                try {
                    $snappy = app('snappy.pdf.wrapper');
                    $snappy->loadHTML($engineHtml);
                    $snappy->setOption('page-size', strtoupper($paper));
                    $snappy->setOption('orientation', ucfirst(strtolower($orientation)));
                    $snappy->setOption('margin-top', 6);
                    $snappy->setOption('margin-right', 6);
                    $snappy->setOption('margin-bottom', 6);
                    $snappy->setOption('margin-left', 6);
                    $snappy->setOption('disable-smart-shrinking', false);

                    $bytes = $snappy->output();

                    return response($bytes, 200, $this->inlineHeaders($safeFileName));
                } catch (\Throwable $exception) {
                    $errors[] = 'snappy: ' . $exception->getMessage();
                    Log::warning('Pumper Dashboard Snappy PDF render failed; trying TCPDF.', array_merge($logContext, [
                        'view' => $view,
                        'message' => $exception->getMessage(),
                    ]));
                }
            }

            if (class_exists(\TCPDF::class)) {
                try {
                    $tcpdf = new \TCPDF(
                        strtolower($orientation) === 'landscape' ? 'L' : 'P',
                        'mm',
                        strtoupper($paper),
                        true,
                        'UTF-8',
                        false
                    );
                    $tcpdf->setPrintHeader(false);
                    $tcpdf->setPrintFooter(false);
                    $tcpdf->SetMargins(6, 6, 6, true);
                    $tcpdf->SetAutoPageBreak(true, 6);
                    $tcpdf->SetTitle('');
                    $tcpdf->AddPage();
                    $tcpdf->writeHTML($engineHtml, true, false, true, false, '');
                    $bytes = $tcpdf->Output($safeFileName, 'S');

                    return response($bytes, 200, $this->inlineHeaders($safeFileName));
                } catch (\Throwable $exception) {
                    $errors[] = 'tcpdf: ' . $exception->getMessage();
                    Log::warning('Pumper Dashboard TCPDF render failed; using clean HTML preview.', array_merge($logContext, [
                        'view' => $view,
                        'message' => $exception->getMessage(),
                    ]));
                }
            }
        }

        Log::warning('Pumper Dashboard PDF engine unavailable; using clean HTML preview.', array_merge($logContext, [
            'view' => $view,
            'errors' => $errors,
        ]));

        return response()->view($view, array_merge($data, [
            'browser_print_fallback' => true,
        ]), 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9_.-]+/', '-', trim($fileName));
        $fileName = trim((string) $fileName, '-.');

        if ($fileName === '') {
            $fileName = 'print-preview';
        }

        if (! str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        return $fileName;
    }

    private function inlineHeaders(string $fileName): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
    }
}
