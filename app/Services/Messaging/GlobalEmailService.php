<?php

namespace App\Services\Messaging;

use App\Services\Documents\GlobalDocumentHeader;
use App\Services\GlobalReportsPagesFooter;

/**
 * Canonical email-body decorator used by Laravel Mail and Communication Hub.
 */
class GlobalEmailService
{
    /** @var GlobalDocumentHeader */
    private $header;

    /** @var GlobalReportsPagesFooter */
    private $footer;

    public function __construct(GlobalDocumentHeader $header, GlobalReportsPagesFooter $footer)
    {
        $this->header = $header;
        $this->footer = $footer;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function decorate($body, array $context = [], $isHtml = null)
    {
        $body = (string) $body;
        $isHtml = $isHtml === null ? $this->looksLikeHtml($body) : (bool) $isHtml;

        return $isHtml
            ? $this->decorateHtml($body, $context)
            : $this->decorateText($body, $context);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function decorateHtml($html, array $context = [])
    {
        $html = (string) $html;
        if ($this->htmlAlreadyDecorated($html)) {
            return $html;
        }

        $header = $this->header->emailHtml(array_merge($context, ['page_no' => 1]));
        $footer = $this->footerHtml();

        if (preg_match('/<body\b[^>]*>/i', $html)) {
            $html = preg_replace_callback(
                '/<body\b[^>]*>/i',
                static function ($matches) use ($header) {
                    return $matches[0] . $header;
                },
                $html,
                1
            );
            if ($footer !== '') {
                $html = preg_replace_callback(
                    '/<\/body\s*>/i',
                    static function () use ($footer) {
                        return $footer . '</body>';
                    },
                    $html,
                    1
                );
            }

            return (string) $html;
        }

        return $header . $html . $footer;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function decorateText($text, array $context = [])
    {
        $text = trim((string) $text);
        if ($this->textAlreadyDecorated($text)) {
            return $text;
        }

        $parts = [$this->header->text(array_merge($context, ['page_no' => 1]), 1)];
        if ($text !== '') {
            $parts[] = $text;
        }

        $footer = trim((string) $this->footer->text());
        if ($footer !== '') {
            $parts[] = $footer;
        }

        return implode("\n\n", $parts);
    }

    private function footerHtml()
    {
        $footer = trim((string) $this->footer->text());
        if ($footer === '') {
            return '';
        }

        return '<div data-erp-global-document-footer="1" style="margin-top:18px;padding-top:9px;border-top:1px solid #b9c0c8;text-align:center;font-family:Arial,sans-serif;font-size:11px;color:#555;">'
            . nl2br(e($footer), false)
            . '</div>';
    }

    private function looksLikeHtml($body)
    {
        return preg_match('/<\/?[a-z][^>]*>/i', (string) $body) === 1;
    }

    private function htmlAlreadyDecorated($html)
    {
        return strpos((string) $html, 'data-erp-global-document-header="1"') !== false;
    }

    private function textAlreadyDecorated($text)
    {
        return preg_match('/^\s*Business Name\s*:/i', (string) $text) === 1
            && stripos((string) $text, 'Business Location:') !== false
            && stripos((string) $text, 'Page Title / Name:') !== false
            && stripos((string) $text, 'Date Range Selected:') !== false
            && stripos((string) $text, 'Page No:') !== false;
    }
}
