<?php

namespace Modules\ProductsNew\Services\ImportExport;

class ProductImportTemplateService
{
    public function headers(): array
    {
        return [
            'product_name',
            'sku',
            'barcode',
            'category',
            'brand',
            'unit',
            'purchase_price',
            'purchase_price_inc_tax',
            'profit_percent',
            'selling_price',
            'selling_price_inc_tax',
            'tax_type',
            'alert_quantity',
            'business_location',
            'status',
            'product_description',
        ];
    }

    public function sampleRow(): array
    {
        return [
            'Sample Imported Product',
            'IMP-SAMPLE-001',
            '890000000001',
            '',
            '',
            '',
            '100.0000',
            '100.0000',
            '25.0000',
            '125.0000',
            '125.0000',
            'exclusive',
            '5.000',
            '',
            'active',
            'Replace this sample row with your own product details.',
        ];
    }

    public function csvTemplate(): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return implode(',', $this->headers()) . "\n";
        }

        // UTF-8 BOM keeps Excel/LibreOffice from damaging non-ASCII product names.
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $this->headers());
        fputcsv($stream, $this->sampleRow());
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }
}
