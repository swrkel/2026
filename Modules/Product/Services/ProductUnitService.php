<?php

namespace Modules\Product\Services;

use Modules\Product\Entities\ProductUnit;
use Modules\Product\Utils\ProductTenantUtil;

class ProductUnitService
{
    public function __construct(private ProductTenantUtil $tenant) {}

    public function query() { return ProductUnit::forBusiness($this->tenant->businessId()); }
    public function create(array $data): ProductUnit { $data['business_id'] = $this->tenant->businessId(); return ProductUnit::create($data); }
    public function update(ProductUnit $row, array $data): ProductUnit { $row->update($data); return $row->refresh(); }
    public function delete(ProductUnit $row): void { $row->delete(); }
}
