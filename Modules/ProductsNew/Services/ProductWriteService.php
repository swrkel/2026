<?php

namespace Modules\ProductsNew\Services;

use Modules\ProductsNew\Services\ProductActionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Entities\ProductsNewProductMeta;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
use Modules\ProductsNew\Services\Identity\ProductUidRegistryService;
use Modules\ProductsNew\Services\Identity\ProductUidService;

class ProductWriteService
{
    private const SKU_MAX_LENGTH = 191;

    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductTimelineService $timeline,
        protected ProductHealthService $health,
        protected InventoryMovementService $inventory,
        protected ProductsNewFinanceStockService $finance,
        protected ProductIdentityGuard $identityGuard,
        protected ProductUidService $productUids,
        protected ProductUidRegistryService $uidRegistry
    ) {}

    public function create(array $data): ProductsNewProduct
    {
        $product = DB::transaction(function () use ($data) {
            $businessId = $this->guard->businessId();
            $data['business_id'] = $businessId;

            // UID is additive and schema-aware. Legacy tenant databases without
            // products.product_uid receive the original payload unchanged.
            $data = $this->productUids->prepareNewProductData($data);

            /*
             * IS2193: when Manage New hides the second-level Pumper Dashboard
             * field, a newly-created product must not become visible there just
             * because the legacy products table happens to default the column to
             * 1.  If the field is available, the submitted Yes/No value wins.
             * Edit is intentionally different: an absent field on edit preserves
             * the product's existing choice.
             */
            if (! array_key_exists('show_in_pumper_dashboard', $data)) {
                $data['show_in_pumper_dashboard'] = 0;
            }

            // Products New is a finished-goods master. Never let category/type
            // (including Fuel) route its inventory value to Raw Material.
            $data['stock_type'] = $this->finance->finishedGoodsAccountId($businessId);

            if ($this->hasOpeningStock($data)) {
                $data['enable_stock'] = 1;
            }

            if (Schema::hasTable('products') && Schema::hasColumn('products', 'created_by')) {
                $data['created_by'] = $this->guard->userId();
            }

            // Permanent product-master duplicate guard. This runs before image
            // storage or automatic SKU generation so a duplicate Single product
            // can never be "made unique" merely by receiving a new auto SKU.
            $this->identityGuard->assertAllowed($data);

            $uploadedImage = $data['image'] ?? null;
            unset($data['image_current']);

            if ($uploadedImage instanceof UploadedFile) {
                $data['image'] = $this->storeProductImage($uploadedImage, (string) ($data['name'] ?? 'product'));
            } else {
                unset($data['image']);
            }

            $requestedSku = $this->normaliseSku($data['sku'] ?? null);
            $mustGenerateSku = $requestedSku === null;

            if ($mustGenerateSku) {
                // products.sku is NOT NULL in the core ERP schema.
                $data['sku'] = $this->temporarySku($businessId);
            } else {
                $data['sku'] = $requestedSku;
            }

            $product = ProductsNewProduct::create($this->clean($data));

            if ($mustGenerateSku) {
                $product->sku = $this->generateUniqueAutomaticSku(
                    (int) $product->id,
                    $businessId,
                    (int) $product->id
                );
                $product->save();
                $product->refresh();
            }

            $variation = $this->syncDefaultVariation($product, $data);
            $this->syncProductLocations($product, $data);
            $this->postOpeningStock($product, $variation, $data);
            $this->syncMeta($product, $data);
            $this->persistProfitBasis($product, $data);

            $this->timeline->log($product->id, 'created', [
                'source' => 'products_new',
                'name' => $product->name,
                'sku' => $product->sku,
                'opening_stock_qty' => $this->openingStockTotal($data),
            ]);

            return $product;
        });

        // Central registry synchronisation is best-effort and happens only after
        // the tenant transaction has committed. With no dedicated central
        // connection configured this is a no-op.
        $this->uidRegistry->syncProductBestEffort($product);

        return $product;
    }

    public function update(ProductsNewProduct $product, array $data): ProductsNewProduct
    {
        $updatedProduct = DB::transaction(function () use ($product, $data) {
            $before = $product->toArray();
            $businessId = $this->guard->businessId();

            // UID-ready tenants lazily backfill an edited legacy product. No
            // matching or merging is performed; this exact local product row
            // receives its own globally unique identity.
            $this->productUids->ensureProductUid((int) $product->id, $businessId);
            $product->refresh();

            // Product UID is immutable and system-owned. Ignore any value posted
            // by a form, import or integration during edit.
            unset($data['product_uid']);

            // Stock Account is module policy, not an editable product attribute.
            // Force every Products New item to the business Finished Goods Account.
            $data['stock_type'] = $this->finance->finishedGoodsAccountId($businessId);

            /*
             * MA-002 (IS-1919 #4): enforce the locked fields on the SERVER too.
             *
             * The edit form disables Category, Sub Category, Unit and Selling Price Tax
             * once a product has been used in a purchase or
             * a sale. A disabled control is a convenience, not a guarantee - it
             * can be re-enabled in the browser in seconds, and anything posted
             * directly never sees the form at all.
             *
             * So the stored values are simply put back. Nothing is rejected and
             * no error is raised: the rest of the edit saves normally and these
             * four keep what they had, which is the behaviour someone editing a
             * used product expects anyway.
             */
            if (app(ProductActionService::class)->isUsedInTransactions((int) $product->id, $businessId)) {
                foreach (['category_id', 'sub_category_id', 'unit_id', 'sale_tax'] as $lockedField) {
                    if (array_key_exists($lockedField, $data)) {
                        $data[$lockedField] = $product->{$lockedField};
                    }
                }
            }

            // Edit must not be usable to rename/re-barcode a product into the
            // identity of another product master. SKU itself stays immutable.
            $identityData = $data;
            $identityData['sku'] = $product->sku;
            $identityData['type'] = $data['type'] ?? $product->type ?? 'single';
            $this->identityGuard->assertAllowed($identityData, (int) $product->id);

            $uploadedImage = $data['image'] ?? null;
            unset($data['image_current']);

            if ($uploadedImage instanceof UploadedFile) {
                $data['image'] = $this->storeProductImage($uploadedImage, (string) ($data['name'] ?? $product->name ?? 'product'));
            } else {
                unset($data['image']);
            }

            // SKU is immutable after product creation. Never trust or persist an
            // SKU value posted through the edit endpoint. A legacy blank SKU is
            // repaired automatically so the core products.sku NOT NULL contract
            // remains satisfied without allowing the user to change it.
            unset($data['sku']);

            if ($this->normaliseSku($product->sku) === null) {
                $data['sku'] = $this->generateUniqueAutomaticSku(
                    (int) $product->id,
                    $businessId,
                    (int) $product->id
                );
            }

            $product->update($this->clean($data));
            $freshProduct = $product->fresh();

            $this->syncDefaultVariation($freshProduct, $data);
            $this->syncProductLocations($freshProduct, $data);
            $this->syncMeta($freshProduct, $data);
            $this->persistProfitBasis($freshProduct, $data);

            $this->timeline->log($product->id, 'updated', [
                'before' => Arr::only($before, ['name', 'sku', 'category_id', 'brand_id', 'tax_type']),
                'after' => Arr::only($freshProduct->toArray(), ['name', 'sku', 'category_id', 'brand_id', 'tax_type']),
            ]);

            return $freshProduct;
        });

        $this->uidRegistry->syncProductBestEffort($updatedProduct);

        return $updatedProduct;
    }

    protected function clean(array $data): array
    {
        $allowed = [
            'business_id', 'product_uid', 'name', 'type', 'unit_id', 'brand_id', 'category_id', 'sub_category_id',
            'tax', 'sale_tax', 'tax_type', 'sku', 'barcode', 'alert_quantity', 'enable_stock',
            // MA-002: which basis the stored profit_percent was measured on.
            // schemaPayload() drops any key that is not a real column, so this is
            // harmless on a tenant where the migration has not run yet - the field
            // is simply ignored until the column exists.
            'profit_basis',
            'not_for_selling', 'is_inactive', 'products_new_status', 'vat_claimed', 'show_in_pumper_dashboard', 'stock_type', 'weight', 'product_description',
            'preparation_time_in_minutes', 'date', 'warranty_id', 'image', 'created_by',
        ];

        $payload = collect($data)->only($allowed)->toArray();

        if (Schema::hasTable('products')) {
            $columns = array_flip(Schema::getColumnListing('products'));
            $payload = array_filter(
                $payload,
                fn (string $key): bool => isset($columns[$key]),
                ARRAY_FILTER_USE_KEY
            );
        }

        $payload['enable_stock'] = !empty($payload['enable_stock']) ? 1 : 0;
        $payload['not_for_selling'] = !empty($payload['not_for_selling']) ? 1 : 0;
        if (array_key_exists('is_inactive', $payload)) {
            $payload['is_inactive'] = !empty($payload['is_inactive']) ? 1 : 0;
        }
        if (array_key_exists('products_new_status', $payload)) {
            $payload['products_new_status'] = strtolower(trim((string) $payload['products_new_status'])) ?: 'active';
        }
        if (array_key_exists('vat_claimed', $payload)) {
            $payload['vat_claimed'] = !empty($payload['vat_claimed']) ? 1 : 0;
        }
        if (array_key_exists('show_in_pumper_dashboard', $payload)) {
            $payload['show_in_pumper_dashboard'] = !empty($payload['show_in_pumper_dashboard']) ? 1 : 0;
        }
        $payload['type'] = $payload['type'] ?? 'single';
        $payload['tax_type'] = $payload['tax_type'] ?? 'exclusive';

        return $payload;
    }

    /**
     * Create/update the core dummy product variation so pricing, stock and POS
     * integrations can use a newly-created Products New item immediately.
     *
     * @return array{product_variation_id:int,variation_id:int}|null
     */
    protected function syncDefaultVariation(ProductsNewProduct $product, array $data): ?array
    {
        if (!Schema::hasTable('product_variations') || !Schema::hasTable('variations')) {
            if ($this->hasPriceData($data) || $this->hasOpeningStock($data)) {
                throw ValidationException::withMessages([
                    'pricing' => 'The core product variation tables are missing. Please run the main product database migrations before saving prices or opening stock.',
                ]);
            }

            return null;
        }

        $now = now();

        // Serialise default-variation creation for this product. Without this,
        // two simultaneous saves can both observe "no variation" and insert a
        // second dummy variation for the same logical Single product.
        if (Schema::hasTable('products')) {
            DB::table('products')->where('id', $product->id)->lockForUpdate()->value('id');
        }

        $productVariationQuery = DB::table('product_variations')
            ->where('product_id', $product->id);

        if (Schema::hasColumn('product_variations', 'is_dummy')) {
            $productVariationQuery->where('is_dummy', 1);
        }

        $productVariation = $productVariationQuery->orderBy('id')->first();

        if ($productVariation) {
            $productVariationId = (int) $productVariation->id;

            $update = $this->schemaPayload('product_variations', [
                'name' => 'DUMMY',
                'is_dummy' => 1,
                'updated_at' => $now,
            ]);

            if ($update !== []) {
                DB::table('product_variations')->where('id', $productVariationId)->update($update);
            }
        } else {
            $productVariationId = (int) DB::table('product_variations')->insertGetId(
                $this->schemaPayload('product_variations', [
                    'variation_template_id' => null,
                    'name' => 'DUMMY',
                    'product_id' => $product->id,
                    'is_dummy' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $variationQuery = DB::table('variations')
            ->where('product_id', $product->id)
            ->where('product_variation_id', $productVariationId);

        if (Schema::hasColumn('variations', 'deleted_at')) {
            $variationQuery->whereNull('deleted_at');
        }

        $variation = $variationQuery->orderBy('id')->first();
        $prices = $this->normalisedPrices($data);

        $variationPayload = $this->schemaPayload('variations', [
            'name' => 'DUMMY',
            'product_id' => $product->id,
            'sub_sku' => $product->sku,
            'product_variation_id' => $productVariationId,
            'variation_value_id' => null,
            'default_purchase_price' => $prices['single_dpp'],
            'dpp_inc_tax' => $prices['single_dpp_inc_tax'],
            'profit_percent' => $prices['profit_percent'],
            'default_sell_price' => $prices['single_dsp'],
            'sell_price_inc_tax' => $prices['single_dsp_inc_tax'],
            'updated_at' => $now,
        ]);

        if ($variation) {
            $variationId = (int) $variation->id;
            DB::table('variations')->where('id', $variationId)->update($variationPayload);
        } else {
            $variationPayload = array_merge(
                $variationPayload,
                $this->schemaPayload('variations', ['created_at' => $now])
            );
            $variationId = (int) DB::table('variations')->insertGetId($variationPayload);
        }

        // Same variation identity follows the product across all locations. On
        // old tenant schemas this method is a no-op.
        $this->productUids->ensureVariationUid($variationId, (int) $product->id);

        return [
            'product_variation_id' => $productVariationId,
            'variation_id' => $variationId,
        ];
    }

    protected function syncProductLocations(ProductsNewProduct $product, array $data): void
    {
        if (!Schema::hasTable('product_locations')) {
            return;
        }

        if (!array_key_exists('product_locations', $data) && empty($data['product_locations_present'])) {
            return;
        }

        $locationIds = collect($data['product_locations'] ?? [])
            ->merge($this->openingStockLocationIds($data))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowed = $this->allowedLocationIds($locationIds->all());

        DB::table('product_locations')->where('product_id', $product->id)->delete();

        if ($allowed === []) {
            return;
        }

        DB::table('product_locations')->insert(
            collect($allowed)
                ->map(fn (int $locationId): array => [
                    'product_id' => (int) $product->id,
                    'location_id' => $locationId,
                ])
                ->all()
        );
    }

    /**
     * Post opening stock entered directly in Add Product.
     */
    protected function postOpeningStock(ProductsNewProduct $product, ?array $variation, array $data): void
    {
        // Opening-stock valuation must use purchase cost INCLUDING tax, matching
        // the core Finance stock-account valuation. A blank row cost inherits the
        // product's inclusive purchase cost; an explicitly entered 0 remains 0.
        $defaultInclusiveCost = (float) ($this->normalisedPrices($data)['single_dpp_inc_tax'] ?? 0);

        $rows = collect($data['opening_stock'] ?? [])
            ->map(function ($row) use ($defaultInclusiveCost): array {
                $row = is_array($row) ? $row : [];
                $rowCost = $this->numericValue($row['unit_cost'] ?? null);

                return [
                    'location_id' => isset($row['location_id']) ? (int) $row['location_id'] : 0,
                    'store_id' => !empty($row['store_id']) ? (int) $row['store_id'] : null,
                    'qty' => $this->numericValue($row['qty'] ?? null) ?? 0.0,
                    'unit_cost' => $rowCost === null ? $defaultInclusiveCost : $rowCost,
                ];
            })
            ->filter(fn (array $row): bool => $row['location_id'] > 0 && $row['qty'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        if (!Schema::hasTable('products_new_inventory_movements')) {
            throw ValidationException::withMessages([
                'opening_stock' => 'The Products New inventory movement table is missing. Please install the Products New stock database tables before entering opening stock.',
            ]);
        }

        if (!Schema::hasTable('variation_location_details')) {
            throw ValidationException::withMessages([
                'opening_stock' => 'The core variation location stock table is missing. Please run the main product database migrations before entering opening stock.',
            ]);
        }

        if (!$variation) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Opening stock could not be posted because the default product variation was not created.',
            ]);
        }

        $allowedLocations = $this->allowedLocationIds($rows->pluck('location_id')->all());
        $allowedLocationMap = array_fill_keys($allowedLocations, true);
        $allowedStores = $this->allowedStoreMap($rows->pluck('store_id')->filter()->all());

        $reference = trim((string) ($data['opening_stock_reference'] ?? ''));
        if ($reference === '') {
            $reference = sprintf('OS-PN-%d-%s', $product->id, now()->format('YmdHis'));
        }

        $date = $data['opening_stock_date'] ?? today()->toDateString();

        foreach ($rows as $row) {
            if (!isset($allowedLocationMap[$row['location_id']])) {
                continue;
            }

            $storeId = $row['store_id'];
            if ($storeId !== null) {
                $store = $allowedStores[$storeId] ?? null;

                // Never post stock to a store from another location/business.
                if (!$store || (int) $store->location_id !== (int) $row['location_id']) {
                    continue;
                }
            }

            $this->inventory->record([
                'product_id' => (int) $product->id,
                'product_variation_id' => (int) $variation['product_variation_id'],
                'variation_id' => (int) $variation['variation_id'],
                'location_id' => (int) $row['location_id'],
                'store_id' => $storeId,
                'movement_type' => 'opening_stock',
                'movement_date' => $date,
                'qty' => $row['qty'],
                'unit_cost' => $row['unit_cost'],
                'reference_no' => $reference,
                'notes' => $storeId
                    ? 'Opening stock entered while adding the product (store #' . $storeId . ').'
                    : 'Opening stock entered while adding the product.',
            ]);
        }
    }

    protected function syncMeta($product, array $data): void
    {
        if (!Schema::hasTable('products_new_product_meta')) {
            return;
        }

        $payload = $this->health->score($product, []);
        $existing = ProductsNewProductMeta::where('product_id', $product->id)->first();

        /*
         * IS2212 - compatibility persistence for Profit Percentage On.
         *
         * The core products.profit_basis migration exists, but multi-tenant
         * installations can have older tenant schemas where that column has not
         * been applied yet. clean() must not write a non-existent core column, so
         * persist the same explicit choice in the module-owned meta settings too.
         * When the core column exists, both remain in sync.
         */
        $settings = ($existing && is_array($existing->settings)) ? $existing->settings : [];

        if (array_key_exists('profit_basis', $data)) {
            $candidate = strtolower(trim((string) ($data['profit_basis'] ?? '')));

            if (in_array($candidate, ['exclusive', 'inclusive'], true)) {
                $settings['profit_basis'] = $candidate;
            }
        }

        $metaValues = [
            'business_id' => $this->guard->businessId(),
            'primary_image' => $product->image ?? ($existing->primary_image ?? null),
            'gallery' => array_key_exists('gallery', $data) ? ($data['gallery'] ?? []) : ($existing->gallery ?? []),
            'attachments' => array_key_exists('attachments', $data) ? ($data['attachments'] ?? []) : ($existing->attachments ?? []),
            'health_score' => $payload['score'],
            'health_payload' => $payload,
            'updated_by' => $this->guard->userId(),
        ];

        if (Schema::hasColumn('products_new_product_meta', 'settings')) {
            $metaValues['settings'] = $settings;
        }

        ProductsNewProductMeta::updateOrCreate(
            ['product_id' => $product->id],
            $metaValues
        );
    }

    /**
     * IS2215 - persist Profit Percentage On independently of legacy schema state.
     *
     * The current products table may have profit_basis, an older tenant may not,
     * and the original migration may have populated old rows with its default
     * "exclusive" without that ever being an operator choice. Products New owns
     * products_new_product_meta.settings, so save the explicit choice there too.
     *
     * Using the query builder here is intentional: this is a tiny compatibility
     * write and should not depend on an Eloquent cast or mass-assignment behaviour.
     */
    protected function persistProfitBasis(ProductsNewProduct $product, array $data): void
    {
        if (! array_key_exists('profit_basis', $data)) {
            return;
        }

        $basis = strtolower(trim((string) ($data['profit_basis'] ?? '')));

        if (! in_array($basis, ['exclusive', 'inclusive'], true)) {
            return;
        }

        if (
            Schema::hasTable('products')
            && Schema::hasColumn('products', 'profit_basis')
        ) {
            $productQuery = DB::table('products')->where('id', (int) $product->id);

            if (Schema::hasColumn('products', 'business_id')) {
                $productQuery->where('business_id', $this->guard->businessId());
            }

            $productQuery->update(['profit_basis' => $basis]);
            $product->setAttribute('profit_basis', $basis);
        }

        if (
            ! Schema::hasTable('products_new_product_meta')
            || ! Schema::hasColumn('products_new_product_meta', 'settings')
        ) {
            return;
        }

        $metaQuery = DB::table('products_new_product_meta')
            ->where('product_id', (int) $product->id);

        if (Schema::hasColumn('products_new_product_meta', 'business_id')) {
            $metaQuery->where('business_id', $this->guard->businessId());
        }

        $meta = $metaQuery->first(['id', 'settings']);

        // syncMeta() normally creates this row. If a very old tenant has a
        // partial meta schema and no row exists, do not invent an incomplete one;
        // the tax_type compatibility fallback in ProductLookupService still keeps
        // VAT-inclusive products correct on edit.
        if (! $meta) {
            return;
        }

        $settings = [];
        $raw = $meta->settings ?? null;

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $settings = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $settings = $raw;
        }

        $settings['profit_basis'] = $basis;

        DB::table('products_new_product_meta')
            ->where('id', (int) $meta->id)
            ->update(['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    protected function normalisedPrices(array $data): array
    {
        $purchaseEx = $this->numericValue($data['single_dpp'] ?? null);
        $purchaseInc = $this->numericValue($data['single_dpp_inc_tax'] ?? null);
        $profit = $this->numericValue($data['profit_percent'] ?? null);
        $sellEx = $this->numericValue($data['single_dsp'] ?? null);
        $sellInc = $this->numericValue($data['single_dsp_inc_tax'] ?? null);

        if ($purchaseEx === null && $purchaseInc !== null) {
            $purchaseEx = $purchaseInc;
        }
        if ($purchaseInc === null && $purchaseEx !== null) {
            $purchaseInc = $purchaseEx;
        }
        if ($sellEx === null && $purchaseEx !== null && $profit !== null) {
            $sellEx = $purchaseEx * (1 + ($profit / 100));
        }
        if ($sellInc === null && $sellEx !== null) {
            $sellInc = $sellEx;
        }
        if ($sellEx === null && $sellInc !== null) {
            $sellEx = $sellInc;
        }

        return [
            'single_dpp' => $purchaseEx ?? 0,
            'single_dpp_inc_tax' => $purchaseInc ?? 0,
            'profit_percent' => $profit ?? 0,
            'single_dsp' => $sellEx ?? 0,
            'single_dsp_inc_tax' => $sellInc ?? 0,
        ];
    }

    protected function hasPriceData(array $data): bool
    {
        foreach (['single_dpp', 'single_dpp_inc_tax', 'profit_percent', 'single_dsp', 'single_dsp_inc_tax'] as $field) {
            if ($this->numericValue($data[$field] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    protected function hasOpeningStock(array $data): bool
    {
        return $this->openingStockTotal($data) > 0;
    }

    protected function openingStockTotal(array $data): float
    {
        return (float) collect($data['opening_stock'] ?? [])->sum(function ($row): float {
            $row = is_array($row) ? $row : [];

            return max(0, (float) ($this->numericValue($row['qty'] ?? null) ?? 0));
        });
    }

    protected function openingStockLocationIds(array $data): array
    {
        return collect($data['opening_stock'] ?? [])
            ->filter(function ($row): bool {
                $row = is_array($row) ? $row : [];

                return (float) ($this->numericValue($row['qty'] ?? null) ?? 0) > 0;
            })
            ->pluck('location_id')
            ->all();
    }

    protected function allowedLocationIds(array $ids): array
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] || !Schema::hasTable('business_locations')) {
            return [];
        }

        $query = DB::table('business_locations')->whereIn('id', $ids);
        if (Schema::hasColumn('business_locations', 'business_id')) {
            $this->guard->applyBusiness($query, 'business_locations.business_id');
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array<int,object>
     */
    protected function allowedStoreMap(array $ids): array
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] || !Schema::hasTable('stores')) {
            return [];
        }

        $query = DB::table('stores')->whereIn('id', $ids);
        if (Schema::hasColumn('stores', 'business_id')) {
            $this->guard->applyBusiness($query, 'stores.business_id');
        }
        if (Schema::hasColumn('stores', 'status')) {
            $query->where('status', 1);
        }

        return $query->get(['id', 'location_id'])->keyBy('id')->all();
    }

    protected function schemaPayload(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = array_flip(Schema::getColumnListing($table));

        return array_filter(
            $payload,
            fn (string $key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function storeProductImage(UploadedFile $file, string $productName): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $baseName = Str::slug($productName) ?: 'product';
        $fileName = mb_substr($baseName, 0, 80) . '-' . Str::lower(Str::random(12)) . '.' . $extension;
        $directory = public_path('uploads/img');

        File::ensureDirectoryExists($directory, 0755, true);
        $file->move($directory, $fileName);

        return $fileName;
    }

    protected function numericValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalised = trim(str_replace(',', '', (string) $value));

        if ($normalised === '' || !is_numeric($normalised)) {
            return null;
        }

        return (float) $normalised;
    }

    protected function normaliseSku(mixed $sku): ?string
    {
        if ($sku === null) {
            return null;
        }

        $normalised = trim((string) $sku);

        return $normalised === '' ? null : mb_substr($normalised, 0, self::SKU_MAX_LENGTH);
    }

    protected function temporarySku(int $businessId): string
    {
        return mb_substr(
            sprintf('__PN_AUTO_%d_%s', $businessId, Str::uuid()->toString()),
            0,
            self::SKU_MAX_LENGTH
        );
    }

    protected function generateUniqueAutomaticSku(int $productId, int $businessId, ?int $ignoreId = null): string
    {
        $prefix = $this->businessSkuPrefix($businessId);
        $baseSku = $prefix . str_pad((string) $productId, 4, '0', STR_PAD_LEFT);

        if ($baseSku === '') {
            $baseSku = 'PN' . str_pad((string) $productId, 4, '0', STR_PAD_LEFT);
        }

        $baseSku = mb_substr($baseSku, 0, self::SKU_MAX_LENGTH);
        $candidate = $baseSku;
        $counter = 1;

        while ($this->skuExists($candidate, $ignoreId)) {
            $suffix = '-' . $counter;
            $candidate = mb_substr(
                $baseSku,
                0,
                self::SKU_MAX_LENGTH - mb_strlen($suffix)
            ) . $suffix;
            $counter++;

            if ($counter > 1000) {
                $suffix = '-' . Str::upper(Str::random(8));
                $candidate = mb_substr(
                    $baseSku,
                    0,
                    self::SKU_MAX_LENGTH - mb_strlen($suffix)
                ) . $suffix;
            }
        }

        return $candidate;
    }

    protected function businessSkuPrefix(int $businessId): string
    {
        try {
            foreach (['business', 'businesses'] as $table) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'sku_prefix')) {
                    continue;
                }

                $prefix = DB::table($table)->where('id', $businessId)->value('sku_prefix');

                if ($prefix !== null) {
                    return trim((string) $prefix);
                }
            }
        } catch (\Throwable) {
            // The product can still be saved with its ID-based SKU.
        }

        return '';
    }

    protected function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        $query = DB::table('products')->where('sku', $sku);
        $this->guard->applyBusiness($query);

        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    protected function preventDuplicateSku(?string $sku, ?int $ignoreId = null): void
    {
        $normalisedSku = $this->normaliseSku($sku);

        if ($normalisedSku === null) {
            return;
        }

        if ($this->skuExists($normalisedSku, $ignoreId)) {
            throw ValidationException::withMessages([
                'sku' => __('productsnew::product.sku_already_exists'),
            ]);
        }
    }
}
