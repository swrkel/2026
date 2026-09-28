<?php
namespace Modules\ProductsNew\Services\Integration;

use Modules\ProductsNew\Services\ProductLookupService;
use Modules\ProductsNew\Services\StockCenterService;
use Modules\ProductsNew\Services\PriceCenterService;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductsNewIntegrationBridge
{
    public function __construct(
        protected ProductLookupService $lookupService,
        protected StockCenterService $stockService,
        protected PriceCenterService $priceService,
        protected ProductsNewTenantGuard $tenantGuard
    ) {}

    public function lookupForModule(string $module, array $filters = []): array
    {
        $context = $this->tenantGuard->context($filters);
        return [
            'module' => $module,
            'context' => $context,
            'products' => method_exists($this->lookupService, 'lookup')
                ? $this->lookupService->lookup($filters)
                : [],
        ];
    }

    public function stockForModule(string $module, int $productId, array $filters = []): array
    {
        $filters['product_id'] = $productId;
        return [
            'module' => $module,
            'product_id' => $productId,
            'stock' => method_exists($this->stockService, 'summary')
                ? $this->stockService->summary($filters)
                : [],
        ];
    }

    public function priceForModule(string $module, int $productId, array $filters = []): array
    {
        $filters['product_id'] = $productId;
        return [
            'module' => $module,
            'product_id' => $productId,
            'price' => method_exists($this->priceService, 'activePrice')
                ? $this->priceService->activePrice($filters)
                : [],
        ];
    }

    public function supportedConsumers(): array
    {
        return ['POS','Purchasing','Manufacturing','Distribution','AutoService','HotelManagement','EVCharging','Membership','CommunicationHub','Finance'];
    }
}
