<?php
namespace Modules\DistributionNew\Services\Scanner;
use Modules\DistributionNew\Entities\DisnewBarcodeLabel;

class DisnewBarcodeService
{
    public function createLabel(array $data): DisnewBarcodeLabel
    {
        $data['barcode_value'] = $data['barcode_value'] ?? $this->buildCode($data);
        return DisnewBarcodeLabel::create($data);
    }

    public function buildCode(array $data): string
    {
        $business = str_pad((string)($data['business_id'] ?? 0), 4, '0', STR_PAD_LEFT);
        $product = str_pad((string)($data['product_id'] ?? 0), 8, '0', STR_PAD_LEFT);
        $batch = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['batch_no'] ?? 'GEN'), 0, 8));
        return 'DN-'.$business.'-'.$product.'-'.$batch.'-'.date('ymdHis');
    }

    public function resolve(string $barcode): ?DisnewBarcodeLabel
    {
        return DisnewBarcodeLabel::where('barcode_value', $barcode)
            ->orWhere('qr_value', $barcode)
            ->first();
    }
}
