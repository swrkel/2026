<?php

namespace Modules\Product\Services;

use Modules\Product\Entities\Product;
use Modules\Product\Utils\ProductTenantUtil;

class ProductDataService
{
    public function __construct(private ProductTenantUtil $tenant) {}

    public function query()
    {
        return Product::query()->where('business_id', $this->tenant->businessId());
    }

    public function create(array $data): Product
    {
        $data['business_id'] = $this->tenant->businessId();
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
