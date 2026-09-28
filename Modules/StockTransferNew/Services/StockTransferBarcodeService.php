<?php
namespace Modules\StockTransferNew\Services;
class StockTransferBarcodeService {
    public function __construct(protected StockTransferProductBridgeService $products) {}
    public function lineFromBarcode(string $barcode): array {
        $product=$this->products->findByBarcode($barcode);
        if(!$product){ throw new \RuntimeException('No product found for barcode: '.$barcode); }
        return ['product_id'=>$product->id ?? null,'variation_id'=>$product->variation_id ?? null,'sku'=>$product->sku ?? $barcode,'product_name'=>$product->name ?? $product->product_name ?? 'Product','qty_requested'=>1,'unit_cost'=>$product->default_purchase_price ?? $product->purchase_price ?? 0];
    }
}
