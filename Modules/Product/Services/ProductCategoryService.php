<?php

namespace Modules\Product\Services;

use Modules\Product\Entities\ProductCategory;
use Modules\Product\Utils\ProductTenantUtil;

class ProductCategoryService
{
    public function __construct(private ProductTenantUtil $tenant) {}

    public function query() { return ProductCategory::forBusiness($this->tenant->businessId()); }
    public function create(array $data): ProductCategory { $data['business_id'] = $this->tenant->businessId(); $data['category_type'] = 'product'; return ProductCategory::create($data); }
    public function update(ProductCategory $row, array $data): ProductCategory { $row->update($data); return $row->refresh(); }
    public function delete(ProductCategory $row): void { $row->delete(); }
}
