<?php

namespace App\Services\Documents;

use App\Services\GlobalReportsPagesFooter;

/**
 * The application's single mPDF implementation.
 *
 * Every current and future `new Mpdf(...)` call is routed to this class. It
 * applies the mandatory five-row header on every PDF page and the Super Admin
 * report/page footer without requiring view-by-view changes.
 */
class GlobalMpdf extends \Mpdf\Mpdf
{
    /** @var array<string,mixed> */
    private $globalDocumentContext = [];

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $documentContext
     */
    public function __construct(array $config = [], array $documentContext = [])
    {
        $this->globalDocumentContext = $documentContext;

        if (empty($config['tempDir'])) {
            $config['tempDir'] = public_path('uploads/temp');
        }
        $config['mode'] = $config['mode'] ?? 'utf-8';
        $config['autoScriptToLang'] = $config['autoScriptToLang'] ?? true;
        $config['autoLangToFont'] = $config['autoLangToFont'] ?? true;
        $config['autoVietnamese'] = $config['autoVietnamese'] ?? true;
        $config['autoArabic'] = $config['autoArabic'] ?? true;

        // Reserve enough room for the mandatory five-row repeated header.
        $config['margin_top'] = max((float) ($config['margin_top'] ?? 10), 31);
        $config['margin_header'] = min((float) ($config['margin_header'] ?? 4), 6);
        $config['margin_bottom'] = max((float) ($config['margin_bottom'] ?? 10), 15);
        $config['margin_footer'] = min((float) ($config['margin_footer'] ?? 4), 7);

        parent::__construct($config);

        $this->applyGlobalDocumentChrome();
    }

    /**
     * Update context after construction and re-render the repeated header.
     *
     * @param  array<string,mixed>  $context
     * @return $this
     */
    public function setGlobalDocumentContext(array $context)
    {
        $this->globalDocumentContext = array_merge($this->globalDocumentContext, $context);
        $this->applyGlobalDocumentChrome();

        return $this;
    }

    /** @return void */
    private function applyGlobalDocumentChrome()
    {
        try {
            $header = app(GlobalDocumentHeader::class)->html(
                $this->globalDocumentContext,
                '{PAGENO}',
                'erp-global-pdf-document-header'
            );

            /*
             * IS2131: the stylesheet is registered SEPARATELY, not prepended to
             * the header HTML.
             *
             * SetHTMLHeader() does not run mPDF's full HTML/CSS parser - it
             * renders the fragment it is given. A <style> block passed to it is
             * therefore not applied as CSS; its contents are drawn as literal
             * TEXT. That is why every F25 PDF opened with a wall of
             * ".erp-global-document-header{width:100%;font-family:dejavusans..."
             * printed above the header table.
             *
             * WriteHTML() with the HTMLHEADER mode registers a stylesheet for the
             * document, so the rules are applied to the header instead of being
             * printed. The header fragment itself is then passed on its own.
             *
             * The CSS is unchanged - only how it reaches mPDF.
             *
             * Inline styles on the table are kept as a fallback: if the
             * stylesheet fails to register on some mPDF build, the header still
             * renders legibly rather than losing all formatting.
             */
            $headerCss = '.erp-global-document-header{width:100%;font-family:dejavusans,Arial,sans-serif;font-size:7.5pt;color:#111;}'
                . '.erp-global-document-header table{width:100%;border-collapse:collapse;border:0.2mm solid #8b929a;}'
                . '.erp-global-document-header th{width:31%;padding:1.1mm 1.8mm;text-align:left;font-weight:bold;background:#f0f2f4;border-bottom:0.15mm solid #c8cdd2;}'
                . '.erp-global-document-header td{padding:1.1mm 1.8mm;text-align:left;border-bottom:0.15mm solid #c8cdd2;}';

            /*
             * Mode 1 = CSS/stylesheet block. Registered before the header is set
             * so the rules exist when the header is laid out.
             */
            $this->WriteHTML($headerCss, 1);

            $this->SetHTMLHeader($header);
        } catch (\Throwable $exception) {
            // A document must still be generated if metadata is unavailable.
        }

        try {
            $footerText = app(GlobalReportsPagesFooter::class)->text();
            if ($footerText !== '') {
                $footer = '<div style="border-top:0.2mm solid #777;padding-top:1.5mm;text-align:center;font-family:dejavusans,Arial,sans-serif;font-size:8pt;color:#111;">'
                    . nl2br(e($footerText), false)
                    . '</div>';
                $this->SetHTMLFooter($footer);
            }
        } catch (\Throwable $exception) {
            // Footer failure must never block PDF generation.
        }
    }
}
