<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Utils\ProductTenantUtil;

class ProductSettingsService
{
    public function __construct(private ProductTenantUtil $tenant) {}

    public function get(): array
    {
        $settings = DB::table('business_product_settings')->where('business_id', $this->tenant->businessId())->first();
        return $settings ? (array) $settings : [];
    }

    public function save(array $data): void
    {
        DB::table('business_product_settings')->updateOrInsert(
            ['business_id' => $this->tenant->businessId()],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );
    }
}
