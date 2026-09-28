<?php

namespace App\Services\Documents;

/**
 * Renders the mandatory five-row document header in HTML and plain text.
 */
class GlobalDocumentHeader
{
    /** @var GlobalDocumentMetadata */
    private $metadata;

    public function __construct(GlobalDocumentMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function html(array $context = [], $pageNo = '{PAGENO}', $id = 'erp-global-document-header')
    {
        $data = $this->metadata->resolve(array_merge($context, ['page_no' => $pageNo]));

        $rows = [
            'Business Name' => $data['business_name'],
            'Business Location' => $data['business_location'],
            'Page Title / Name' => $data['page_title'],
            'Date Range Selected' => $data['date_range'],
            'Page No' => $data['page_no'],
        ];

        $html = '<div id="' . e($id) . '" class="erp-global-document-header" data-erp-global-document-header="1">';
        $html .= '<table role="presentation" cellspacing="0" cellpadding="0">';
        foreach ($rows as $label => $value) {
            $key = strtolower(str_replace([' / ', ' '], ['_', '_'], $label));
            $valueHtml = ($label === 'Page No' && $value === '__BROWSER_PAGE_NO__')
                ? '<span class="erp-global-browser-page-number" aria-label="Printed page number"><span class="erp-global-browser-page-number-fallback">1</span></span>'
                : e((string) $value);
            $html .= '<tr><th>' . e($label) . '</th><td data-erp-document-value="' . e($key) . '">' . $valueHtml . '</td></tr>';
        }
        $html .= '</table></div>';

        return $html;
    }

    /**
     * Compact email-compatible HTML without external CSS dependencies.
     *
     * @param  array<string,mixed>  $context
     */
    public function emailHtml(array $context = [])
    {
        $data = $this->metadata->resolve(array_merge($context, ['page_no' => 1]));
        $rows = [
            'Business Name' => $data['business_name'],
            'Business Location' => $data['business_location'],
            'Page Title / Name' => $data['page_title'],
            'Date Range Selected' => $data['date_range'],
            'Page No' => 1,
        ];

        $html = '<div data-erp-global-document-header="1" style="margin:0 0 16px;border:1px solid #b9c0c8;font-family:Arial,sans-serif;font-size:12px;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="5" style="border-collapse:collapse;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr><td width="32%" style="border-bottom:1px solid #d8dde3;font-weight:bold;background:#f3f5f7;">' . e($label) . '</td>';
            $html .= '<td style="border-bottom:1px solid #d8dde3;">' . e((string) $value) . '</td></tr>';
        }
        $html .= '</table></div>';

        return $html;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function text(array $context = [], $pageNo = 1)
    {
        $data = $this->metadata->resolve(array_merge($context, ['page_no' => $pageNo]));

        return implode("\n", [
            'Business Name: ' . $data['business_name'],
            'Business Location: ' . $data['business_location'],
            'Page Title / Name: ' . $data['page_title'],
            'Date Range Selected: ' . $data['date_range'],
            'Page No: ' . $data['page_no'],
        ]);
    }
}
