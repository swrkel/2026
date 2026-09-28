<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\Schema;
use Modules\RiceMill\Models\RiceProduct;
use Modules\RiceMill\Models\Setting;

/**
 * Resolves the Products New Products explicitly selected in
 * Settings > Product Category Mapping > Out Put Type.
 *
 * Selections are kept in rcm_settings.settings JSON so this configuration does
 * not require an additional database table. A selected Products New Product
 * that is also linked to an active Rice Mill Rice Product posts to Finished
 * Rice Stock; the remaining selected Products are handled as dynamic
 * by-products using a stable pn_{products.id} output type.
 */
class OutputTypeProductService
{
    public function __construct(private ExternalMasterDataService $masters) {}

    /** @return array<int,array{product_id:int,category_id:int,sub_category_id:int}> */
    public function selections(int $businessId): array
    {
        $row = Setting::forBusiness($businessId)->select(['settings'])->first();
        $settings = (array) optional($row)->settings;

        // Out Put Type is limited to Products under the Products New top-level
        // Categories "Rice" and "By Products". Filtering here also protects
        // Production from older/stale mappings created before this rule.
        $eligible = $this->eligibleProductMap($businessId);

        $seen = [];
        $out = [];
        foreach ((array) ($settings['output_type_product_selections'] ?? []) as $selection) {
            if (! is_array($selection)) {
                continue;
            }
            $productId = (int) ($selection['product_id'] ?? 0);
            if ($productId <= 0 || isset($seen[$productId]) || ! isset($eligible[$productId])) {
                continue;
            }
            $seen[$productId] = true;
            $product = $eligible[$productId];
            $out[] = [
                'product_id' => $productId,
                'category_id' => (int) ($product['top_category_id'] ?? $selection['category_id'] ?? 0),
                'sub_category_id' => (int) ($product['sub_category_id'] ?? $selection['sub_category_id'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Definitions used by the Production Output grid.
     *
     * @return array<int,array{
     *   source_product_id:int,name:string,code:string,unit:string,
     *   category_id:int,sub_category_id:int,output_type:string,
     *   rice_product_id:?int,yield_key:?string
     * }>
     */
    public function productionDefinitions(int $businessId): array
    {
        $selections = $this->selections($businessId);
        if (! $selections) {
            return [];
        }

        $wantedIds = array_values(array_unique(array_map(
            static fn ($row) => (int) $row['product_id'],
            $selections
        )));

        $masterById = [];
        foreach ($this->masters->productsNewProducts($businessId) as $product) {
            $id = (int) ($product['id'] ?? 0);
            if ($id > 0 && in_array($id, $wantedIds, true)) {
                $masterById[$id] = $product;
            }
        }

        $riceBySource = $this->riceProductsBySourceId($businessId, $wantedIds, $masterById);
        $definitions = [];
        foreach ($selections as $selection) {
            $sourceId = (int) $selection['product_id'];
            $master = $masterById[$sourceId] ?? null;
            if (! $master) {
                continue;
            }

            $riceProduct = $riceBySource[$sourceId] ?? null;
            $name = trim((string) ($master['name'] ?? ''));
            $yieldKey = $riceProduct ? 'rice' : $this->inferByproductYieldKey($name);

            $definitions[] = [
                'source_product_id' => $sourceId,
                'name' => $name !== '' ? $name : ('Product #' . $sourceId),
                'code' => (string) ($master['code'] ?? ''),
                'unit' => (string) ($master['unit'] ?? ''),
                'category_id' => (int) ($master['category_id'] ?? 0),
                'sub_category_id' => (int) ($master['sub_category_id'] ?? 0),
                'output_type' => $riceProduct ? 'rice' : ('pn_' . $sourceId),
                'rice_product_id' => $riceProduct ? (int) $riceProduct->id : null,
                'yield_key' => $yieldKey,
            ];
        }

        return $definitions;
    }

    /** @return array<int,array> */
    public function definitionMap(int $businessId): array
    {
        $map = [];
        foreach ($this->productionDefinitions($businessId) as $definition) {
            $map[(int) $definition['source_product_id']] = $definition;
        }
        return $map;
    }

    /** Resolve labels for current and historical output/by-product types. */
    public function typeLabels(int $businessId, array $types = []): array
    {
        $labels = [
            'rice' => 'Rice',
            'broken_rice' => 'Broken Rice',
            'bran' => 'Bran',
            'husk' => 'Husk',
            'other' => 'Other',
        ];

        $wantedProductIds = [];
        foreach ($types as $type) {
            if (preg_match('/^pn_(\d+)$/', (string) $type, $match)) {
                $wantedProductIds[] = (int) $match[1];
            }
        }
        if (! $wantedProductIds) {
            foreach ($this->productionDefinitions($businessId) as $definition) {
                $labels[(string) $definition['output_type']] = (string) $definition['name'];
            }
            return $labels;
        }

        $wantedProductIds = array_values(array_unique($wantedProductIds));
        foreach ($this->masters->productsNewProducts($businessId) as $product) {
            $id = (int) ($product['id'] ?? 0);
            if ($id > 0 && in_array($id, $wantedProductIds, true)) {
                $labels['pn_' . $id] = (string) ($product['name'] ?? ('Product #' . $id));
            }
        }

        return $labels;
    }

    private function riceProductsBySourceId(int $businessId, array $sourceIds, array $masterById): array
    {
        $columns = ['id','name','code','paddy_variety_id'];
        $hasLink = Schema::hasTable('rcm_products') && Schema::hasColumn('rcm_products', 'products_new_product_id');
        if ($hasLink) {
            $columns[] = 'products_new_product_id';
        }

        // Load active Rice Mill Products once. Even when the link column exists,
        // some older rows may still be NULL and need the exact Name + SKU fallback.
        $rows = RiceProduct::forBusiness($businessId)->where('active', 1)->get($columns);

        $map = [];
        foreach ($rows as $row) {
            if ($hasLink) {
                $sourceId = (int) ($row->products_new_product_id ?? 0);
                if ($sourceId > 0) {
                    $map[$sourceId] = $row;
                }
            }
        }

        // Backward compatibility for tenants where the optional Products New
        // link column has not been imported/backfilled yet.
        foreach ($sourceIds as $sourceId) {
            if (isset($map[$sourceId]) || ! isset($masterById[$sourceId])) {
                continue;
            }
            $master = $masterById[$sourceId];
            $masterName = trim((string) ($master['name'] ?? ''));
            $masterCode = trim((string) ($master['code'] ?? ''));
            foreach ($rows as $row) {
                if (
                    strcasecmp(trim((string) $row->name), $masterName) === 0
                    && strcasecmp(trim((string) $row->code), $masterCode) === 0
                ) {
                    $map[$sourceId] = $row;
                    break;
                }
            }
        }

        return $map;
    }

    /** @return array<int,array> */
    private function eligibleProductMap(int $businessId): array
    {
        $categories = [];
        foreach ($this->masters->productCategories($businessId) as $category) {
            $name = strtolower(trim((string) ($category['name'] ?? '')));
            if (in_array($name, ['rice', 'by products'], true)) {
                $categories[] = $category;
            }
        }

        $map = [];
        foreach ($categories as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            foreach ($this->masters->productsNewProductsForCategory($businessId, $categoryId) as $product) {
                $id = (int) ($product['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $product['top_category_id'] = $categoryId;
                $product['top_category_name'] = (string) ($category['name'] ?? '');
                $map[$id] = $product;
            }
        }

        return $map;
    }

    private function inferByproductYieldKey(string $name): ?string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return null;
        }
        if (str_contains($name, 'broken') && str_contains($name, 'rice')) {
            return 'broken';
        }
        if (str_contains($name, 'bran')) {
            return 'bran';
        }
        if (str_contains($name, 'husk')) {
            return 'husk';
        }
        return null;
    }
}
