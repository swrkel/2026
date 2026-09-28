<?php

namespace Modules\Product\Services;

use Modules\Product\Entities\ProductBrand;
use Modules\Product\Utils\ProductTenantUtil;

class ProductBrandService
{
    public function __construct(private ProductTenantUtil $tenant) {}

    public function query() { return ProductBrand::forBusiness($this->tenant->businessId()); }
    public function create(array $data): ProductBrand { $data['business_id'] = $this->tenant->businessId(); return ProductBrand::create($data); }
    public function update(ProductBrand $row, array $data): ProductBrand { $row->update($data); return $row->refresh(); }
    public function delete(ProductBrand $row): void { $row->delete(); }
}
