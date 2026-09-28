<?php

namespace App\Services;

use App\Services\Documents\GlobalDocumentHeader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Global browser page/print chrome:
 * - mandatory five-row print header on every printed page;
 * - Super Admin "Footer for the Reports & Pages" on screens and prints;
 * - automatic DataTables Print integration for present and future modules.
 */
class GlobalReportsPagesFooter
{
    public const CACHE_KEY = 'erp.global_reports_pages_footer.v1';

    private const CACHE_SECONDS = 300;

    /** @var bool */
    private $resolved = false;

    /** @var string */
    private $footerText = '';

    /** @var GlobalDocumentHeader */
    private $header;

    public function __construct(GlobalDocumentHeader $header)
    {
        $this->header = $header;
    }

    /** @return string */
    public function text()
    {
        if ($this->resolved) {
            return $this->footerText;
        }

        $this->resolved = true;

        try {
            $connection = $this->centralConnectionName();
            $value = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () use ($connection) {
                return DB::connection($connection)
                    ->table('system')
                    ->where('key', 'admin_reports_footer')
                    ->value('value');
            });
            $this->footerText = trim((string) $value);
        } catch (\Throwable $exception) {
            $this->footerText = '';
        }

        return $this->footerText;
    }

    /**
     * @param  bool  $includeFooter
     * @return string
     */
    public function renderedMarkup($includeFooter = true)
    {
        $headerHtml = $this->header->html([], '__BROWSER_PAGE_NO__', 'erp-global-document-print-header');
        $footerText = $includeFooter ? $this->text() : '';
        $footerHtml = $footerText === ''
            ? ''
            : '<footer id="erp-global-reports-pages-footer" role="contentinfo">'
                . nl2br(e($footerText), false)
                . '</footer>';
        $bottomMargin = $footerHtml === '' ? '8mm' : '18mm';

        $markup = <<<'HTML'
<style id="erp-global-document-chrome-style">
    #erp-global-document-print-header {
        display: none;
    }

    #erp-global-reports-pages-footer {
        box-sizing: border-box;
        clear: both;
        width: 100%;
        margin: 24px 0 0;
        padding: 9px 16px;
        border-top: 1px solid #d8dde3;
        background: #fff;
        color: #5f6670;
        font-size: 11px;
        font-weight: 400;
        line-height: 1.45;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    @media print {
        @page {
            margin-top: 35mm;
            margin-bottom: __BOTTOM_MARGIN__;
            counter-increment: page;
        }

        #erp-global-document-print-header {
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            left: 0 !important;
            z-index: 2147483647 !important;
            box-sizing: border-box !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 2mm 7mm 1.5mm !important;
            background: #fff !important;
            color: #000 !important;
            font-family: Arial, sans-serif !important;
            font-size: 8pt !important;
            line-height: 1.15 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #erp-global-document-print-header table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: .25mm solid #777 !important;
        }

        #erp-global-document-print-header th,
        #erp-global-document-print-header td {
            padding: .75mm 1.5mm !important;
            border-bottom: .2mm solid #bbb !important;
            text-align: left !important;
            vertical-align: top !important;
        }

        #erp-global-document-print-header th {
            width: 31% !important;
            font-weight: 700 !important;
            background: #f1f2f3 !important;
        }

        .erp-global-browser-page-number {
            position: relative;
            display: inline-block;
            min-width: 2.5em;
        }

        .erp-global-browser-page-number::after {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background: #fff;
            content: counter(page);
        }

        #erp-global-reports-pages-footer {
            position: fixed !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            z-index: 2147483647 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 3mm 8mm 2mm !important;
            border-top: .25mm solid #777 !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 9pt !important;
            line-height: 1.25 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
__GLOBAL_HEADER__
__GLOBAL_FOOTER__
<script id="erp-global-document-chrome-script">
(function (window, document) {
    'use strict';

    var header = document.getElementById('erp-global-document-print-header');
    var globalPdfEndpoint = __GLOBAL_PDF_ENDPOINT__;
    var globalCsrfToken = __GLOBAL_CSRF_TOKEN__;

    function text(value) {
        return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    }

    function setValue(key, value) {
        if (!header || !text(value)) return;
        var cell = header.querySelector('[data-erp-document-value="' + key + '"]');
        if (cell) cell.textContent = text(value);
    }

    function selectedText(selectors) {
        for (var i = 0; i < selectors.length; i++) {
            var element = document.querySelector(selectors[i]);
            if (!element) continue;
            if (element.tagName === 'SELECT') {
                var selected = Array.prototype.slice.call(element.selectedOptions || []);
                var values = selected.map(function (option) { return text(option.textContent); }).filter(Boolean);
                if (values.length) return values.join(', ');
            }
            if (text(element.value)) return text(element.value);
        }
        return '';
    }

    function pageTitle() {
        var selectors = ['[data-document-title]', '.content-header h1', '.page-header h1', 'main h1', 'h1'];
        for (var i = 0; i < selectors.length; i++) {
            var element = document.querySelector(selectors[i]);
            if (element && text(element.textContent)) return text(element.textContent);
        }
        return text(document.title).replace(/\s*[-|]\s*[^-|]+$/, '');
    }

    function dateRange() {
        var range = selectedText([
            '[name="date_range"]', '[name="selected_date_range"]',
            '[name="report_date_range"]', '[name="transaction_date_range"]',
            '#date_range', '#selected_date_range', '#report_date_range',
            '.date-range-picker:not([type="hidden"])', '.daterange:not([type="hidden"])'
        ]);
        if (range) return range;

        var starts = ['[name="start_date"]', '[name="date_from"]', '[name="from_date"]', '#start_date', '#date_from', '#from_date'];
        var ends = ['[name="end_date"]', '[name="date_to"]', '[name="to_date"]', '#end_date', '#date_to', '#to_date'];
        var start = selectedText(starts);
        var end = selectedText(ends);
        if (start || end) return start && end && start !== end ? start + ' - ' + end : (start || end);

        return '';
    }

    function documentValue(key) {
        if (!header) return '';
        var cell = header.querySelector('[data-erp-document-value="' + key + '"]');
        return cell ? text(cell.textContent) : '';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? text(meta.getAttribute('content')) : globalCsrfToken;
    }

    function safeFileName(value) {
        var name = text(value || 'document').replace(/[^A-Za-z0-9._-]+/g, '_').replace(/^[_\.\-]+|[_\.\-]+$/g, '');
        if (!name) name = 'document';
        return /\.pdf$/i.test(name) ? name : name + '.pdf';
    }

    function currentDocumentMetadata(options) {
        refreshMetadata();
        options = options || {};
        return {
            business_name: options.business_name || documentValue('business_name') || 'Not Available',
            business_location: options.business_location || documentValue('business_location') || 'All Locations',
            page_title: options.page_title || pageTitle() || 'Document',
            date_range: options.date_range || documentValue('date_range_selected') || dateRange() || 'All Dates'
        };
    }

    function cleanExportNode(node) {
        var clone = node.cloneNode(true);
        clone.removeAttribute('id');
        Array.prototype.slice.call(clone.querySelectorAll('script,style,form,.no-print,.notexport,.dataTables_empty,.dt-buttons,.dataTables_filter,.dataTables_length,.dataTables_paginate')).forEach(function (element) {
            element.remove();
        });
        Array.prototype.slice.call(clone.querySelectorAll('[id]')).forEach(function (element) {
            element.removeAttribute('id');
        });
        Array.prototype.slice.call(clone.querySelectorAll('input,select,textarea,button,a')).forEach(function (control) {
            var replacement = document.createTextNode(text(control.value || control.textContent));
            if (control.parentNode) control.parentNode.replaceChild(replacement, control);
        });
        Array.prototype.slice.call(clone.querySelectorAll('[style]')).forEach(function (element) {
            var style = element.getAttribute('style') || '';
            style = style.replace(/display\s*:\s*none\s*;?/ig, '');
            element.setAttribute('style', style);
        });
        return clone;
    }

    function downloadPdfBlob(blob, filename) {
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = safeFileName(filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { window.URL.revokeObjectURL(url); }, 1500);
    }

    function postGlobalPdf(html, options) {
        options = options || {};
        var metadata = currentDocumentMetadata(options);
        var body = new URLSearchParams();
        body.set('_token', csrfToken());
        body.set('html', String(html || ''));
        body.set('filename', safeFileName(options.filename || metadata.page_title));
        body.set('page_title', metadata.page_title);
        body.set('business_name', metadata.business_name);
        body.set('business_location', metadata.business_location);
        body.set('date_range', metadata.date_range);
        body.set('location_id', options.location_id || '');
        body.set('page_size', String(options.page_size || 'A4').toUpperCase());
        body.set('orientation', String(options.orientation || 'P').toUpperCase() === 'L' ? 'L' : 'P');

        return window.fetch(globalPdfEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/pdf'
            },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (message) {
                    throw new Error(message || 'PDF export failed.');
                });
            }
            return response.blob();
        }).then(function (blob) {
            downloadPdfBlob(blob, options.filename || metadata.page_title);
            return blob;
        });
    }

    window.erpGlobalPdf = {
        exportHtml: function (html, options) {
            return postGlobalPdf(html, options || {});
        },
        exportTable: function (table, options) {
            options = options || {};
            if (typeof table === 'string') table = document.querySelector(table);
            if (!table) return Promise.reject(new Error('Report table was not found.'));

            var wrapper = document.createElement('div');
            var heading = document.createElement('h2');
            heading.textContent = options.page_title || pageTitle() || 'Document';
            wrapper.appendChild(heading);
            wrapper.appendChild(cleanExportNode(table));

            var columnCount = table.querySelectorAll('thead th').length;
            if (!options.orientation && columnCount > 6) options.orientation = 'L';
            if (!options.page_size && columnCount > 11) options.page_size = 'A3';
            return postGlobalPdf(wrapper.innerHTML, options);
        },
        exportTables: function (sections, options) {
            options = options || {};
            var wrapper = document.createElement('div');
            var heading = document.createElement('h2');
            heading.textContent = options.page_title || pageTitle() || 'Document';
            wrapper.appendChild(heading);
            var widest = 0;

            (sections || []).forEach(function (section) {
                var table = typeof section.table === 'string' ? document.querySelector(section.table) : section.table;
                if (!table) return;
                var sectionHeading = document.createElement('h3');
                sectionHeading.textContent = section.title || '';
                wrapper.appendChild(sectionHeading);
                wrapper.appendChild(cleanExportNode(table));
                widest = Math.max(widest, table.querySelectorAll('thead th').length);
            });

            if (!options.orientation && widest > 6) options.orientation = 'L';
            if (!options.page_size && widest > 11) options.page_size = 'A3';
            return postGlobalPdf(wrapper.innerHTML, options);
        }
    };

    // Capture every current/future DataTables PDF button before pdfMake runs.
    document.addEventListener('click', function (event) {
        var target = event.target && event.target.closest ? event.target.closest('.buttons-pdf,.buttons-pdfHtml5') : null;
        if (!target) return;

        var wrapper = target.closest('.dataTables_wrapper');
        var table = wrapper ? wrapper.querySelector('table') : null;
        if (!table) return;

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();

        target.disabled = true;
        window.erpGlobalPdf.exportTable(table, {
            page_title: pageTitle(),
            filename: pageTitle()
        }).catch(function (error) {
            if (window.console) console.error('[Global PDF Export]', error);
            window.alert('PDF export failed. Please try again.');
        }).then(function () {
            target.disabled = false;
        });
    }, true);

    function decorateWhatsApp(message) {
        refreshMetadata();
        message = text(message);
        if (/^Business Name\s*:/i.test(message) && message.indexOf('Page No:') !== -1) return message;

        var lines = [
            'Business Name: ' + (documentValue('business_name') || 'Not Available'),
            'Business Location: ' + (documentValue('business_location') || 'All Locations'),
            'Page Title / Name: ' + (documentValue('page_title_name') || pageTitle() || 'Document'),
            'Date Range Selected: ' + (documentValue('date_range_selected') || 'All Dates'),
            'Page No: 1'
        ];
        if (message) lines.push('', message);

        var footer = document.getElementById('erp-global-reports-pages-footer');
        if (footer && text(footer.textContent)) lines.push('', text(footer.textContent));
        return lines.join('\n');
    }

    window.erpGlobalWhatsApp = {
        decorate: decorateWhatsApp,
        url: function (recipient, message) {
            var phone = String(recipient == null ? '' : recipient).replace(/[^0-9]/g, '');
            return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(decorateWhatsApp(message));
        },
        open: function (recipient, message, target) {
            return window.open(this.url(recipient, message), target || '_blank');
        }
    };

    function refreshMetadata() {
        if (!header) return;
        setValue('page_title_name', pageTitle());

        var location = selectedText([
            '[name="business_location_id"]', '[name="location_id"]',
            '[name="selected_location_id"]', '#business_location_id',
            '#location_id', 'select[id*="location"]:not([multiple])'
        ]);
        if (location) setValue('business_location', location);

        var range = dateRange();
        if (range) setValue('date_range_selected', range);
    }

    function installDataTablePdfBridge() {
        var $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.dataTable || !$.fn.dataTable.ext || !$.fn.dataTable.ext.buttons) return false;

        var buttons = $.fn.dataTable.ext.buttons;
        var action = function (event, dataTable, button, config) {
            config = config || {};
            var table = dataTable && dataTable.table ? dataTable.table().node() : null;
            var configuredTitle = typeof config.title === 'function' ? config.title.call(this, dataTable, button, config) : config.title;
            var configuredFilename = typeof config.filename === 'function' ? config.filename.call(this, dataTable, button, config) : config.filename;
            var orientation = String(config.orientation || '').toLowerCase() === 'landscape' ? 'L' : 'P';
            var pageSize = String(config.pageSize || 'A4').toUpperCase();

            if (!table) return;
            window.erpGlobalPdf.exportTable(table, {
                page_title: configuredTitle && configuredTitle !== '*' ? configuredTitle : pageTitle(),
                filename: configuredFilename && configuredFilename !== '*' ? configuredFilename : pageTitle(),
                orientation: orientation,
                page_size: pageSize
            }).catch(function (error) {
                if (window.console) console.error('[Global PDF Export]', error);
                window.alert('PDF export failed. Please try again.');
            });
        };

        ['pdfHtml5', 'pdf'].forEach(function (name) {
            var definition = buttons[name];
            if (definition && typeof definition === 'object') {
                definition.action = action;
                definition._erpGlobalPdf = true;
            }
        });

        return true;
    }

    function installDataTablePrintChrome() {
        var $ = window.jQuery;
        var footer = document.getElementById('erp-global-reports-pages-footer');
        if (! $ || ! $.fn || ! $.fn.dataTable || ! $.fn.dataTable.ext || ! $.fn.dataTable.ext.buttons) return false;

        var printButton = $.fn.dataTable.ext.buttons.print;
        if (!printButton || typeof printButton.action !== 'function' || printButton._erpGlobalChromeWrapped) return true;

        var originalAction = printButton.action;
        printButton.action = function (event, dataTable, button, config) {
            refreshMetadata();
            var printConfig = $.extend({}, config || {});
            var originalCustomize = printConfig.customize;

            printConfig.customize = function (printWindow) {
                if (typeof originalCustomize === 'function') originalCustomize.apply(this, arguments);
                if (!printWindow || !printWindow.document) return;

                var printDocument = printWindow.document;
                if (header && !printDocument.getElementById('erp-global-document-print-header')) {
                    var headerClone = header.cloneNode(true);
                    printDocument.body.insertBefore(headerClone, printDocument.body.firstChild);
                }
                if (footer && !printDocument.getElementById('erp-global-reports-pages-footer')) {
                    printDocument.body.appendChild(footer.cloneNode(true));
                }

                var style = printDocument.createElement('style');
                style.setAttribute('data-erp-global-document-chrome', '1');
                style.textContent = '@page{margin-top:35mm;margin-bottom:__BOTTOM_MARGIN__;counter-increment:page;}'
                    + '#erp-global-document-print-header{display:block!important;position:fixed!important;top:0!important;right:0!important;left:0!important;z-index:2147483647!important;width:100%!important;box-sizing:border-box!important;padding:2mm 7mm 1.5mm!important;background:#fff!important;color:#000!important;font:8pt/1.15 Arial,sans-serif!important;}'
                    + '#erp-global-document-print-header table{width:100%!important;border-collapse:collapse!important;border:.25mm solid #777!important;}'
                    + '#erp-global-document-print-header th,#erp-global-document-print-header td{padding:.75mm 1.5mm!important;border-bottom:.2mm solid #bbb!important;text-align:left!important;}'
                    + '#erp-global-document-print-header th{width:31%!important;background:#f1f2f3!important;font-weight:700!important;}'
                    + '.erp-global-browser-page-number{position:relative;display:inline-block;min-width:2.5em;}'
                    + '.erp-global-browser-page-number::after{position:absolute;top:0;left:0;width:100%;background:#fff;content:counter(page);}'
                    + '#erp-global-reports-pages-footer{position:fixed!important;right:0!important;bottom:0!important;left:0!important;z-index:2147483647!important;width:100%!important;box-sizing:border-box!important;padding:3mm 8mm 2mm!important;border-top:.25mm solid #777!important;background:#fff!important;color:#000!important;font:9pt/1.25 Arial,sans-serif!important;text-align:center!important;}';
                printDocument.head.appendChild(style);
            };

            return originalAction.call(this, event, dataTable, button, printConfig);
        };
        printButton._erpGlobalChromeWrapped = true;
        return true;
    }

    refreshMetadata();
    window.addEventListener('beforeprint', refreshMetadata);
    document.addEventListener('change', refreshMetadata, true);
    if (!installDataTablePdfBridge()) {
        document.addEventListener('DOMContentLoaded', installDataTablePdfBridge, {once: true});
        window.setTimeout(installDataTablePdfBridge, 500);
        window.setTimeout(installDataTablePdfBridge, 1500);
    }
    if (!installDataTablePrintChrome()) {
        document.addEventListener('DOMContentLoaded', installDataTablePrintChrome, {once: true});
        window.setTimeout(installDataTablePrintChrome, 1000);
    }
})(window, document);
</script>
HTML;

        $pdfEndpoint = '/global-documents/pdf';
        $csrfToken = '';
        try {
            $pdfEndpoint = route('global.documents.pdf', [], false);
            $csrfToken = csrf_token();
        } catch (\Throwable $exception) {
            // Keep safe relative/fallback values for public or early-boot pages.
        }

        return str_replace(
            [
                '__GLOBAL_HEADER__',
                '__GLOBAL_FOOTER__',
                '__BOTTOM_MARGIN__',
                '__GLOBAL_PDF_ENDPOINT__',
                '__GLOBAL_CSRF_TOKEN__',
            ],
            [
                $headerHtml,
                $footerHtml,
                $bottomMargin,
                json_encode($pdfEndpoint, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            ],
            $markup
        );
    }

    /**
     * @param  string  $html
     * @return bool
     */
    public function isAlreadyRendered($html)
    {
        $footer = $this->normaliseVisibleText($this->text());
        if ($footer === '' || mb_strlen($footer) < 4) {
            return false;
        }

        $document = $this->normaliseVisibleText((string) $html);

        return $document !== '' && mb_stripos($document, $footer) !== false;
    }

    private function normaliseVisibleText($value)
    {
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', (string) $value);
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
    }

    private function centralConnectionName()
    {
        if (! empty(config('database.connections.system.database'))) {
            return 'system';
        }

        return config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }
}
