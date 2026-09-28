<?php

namespace App\Services\Documents;

/**
 * Canonical PDF API for all future modules. Existing direct mPDF calls are
 * also routed through GlobalMpdf for backward compatibility.
 */
class GlobalPdfService
{
    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $context
     */
    public function make(array $config = [], array $context = [])
    {
        return new GlobalMpdf($config, $context);
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $context
     */
    public function binary($html, array $config = [], array $context = [])
    {
        $pdf = $this->make($config, $context);
        $pdf->WriteHTML((string) $html);

        return $pdf->Output('', 'S');
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $context
     */
    public function save($html, $path, array $config = [], array $context = [])
    {
        $pdf = $this->make($config, $context);
        $pdf->WriteHTML((string) $html);
        $pdf->Output((string) $path, 'F');

        return $path;
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $context
     */
    public function download($html, $filename, array $config = [], array $context = [])
    {
        return response($this->binary($html, $config, $context), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . basename((string) $filename) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $context
     */
    public function stream($html, $filename, array $config = [], array $context = [])
    {
        return response($this->binary($html, $config, $context), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename((string) $filename) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }
}
