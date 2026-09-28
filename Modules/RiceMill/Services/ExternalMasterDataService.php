<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads shared ERP masters without crossing the active tenant/business.
 *
 * v27 deliberately uses request-local memoisation only. It removes repeated
 * schema scans and repeated Location/Store queries inside the same page request
 * while keeping master changes visible immediately on the next request.
 */
class ExternalMasterDataService
{
    private array $memo = [];
    private array $columnMemo = [];
    private ?array $storeMetaMemo = null;
    private bool $storeMetaResolved = false;

    public function __construct(private LocationAccessService $locationAccess) {}

    public function customers(int $businessId): array { return $this->contacts($businessId, 'customer'); }
    public function suppliers(int $businessId): array { return $this->contacts($businessId, 'supplier'); }

    /** Return names only for the contacts visible on the current result page. */
    public function contactNamesByIds(int $businessId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0)));
        if (! $ids || ! Schema::hasTable('contacts')) {
            return [];
        }

        return DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->mapWithKeys(static fn ($name, $id) => [(int) $id => (string) $name])
            ->all();
    }

    private function contacts(int $businessId, string $type): array
    {
        $key = 'contacts:' . $businessId . ':' . $type;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }
        if (! Schema::hasTable('contacts')) {
            return $this->memo[$key] = [];
        }

        $cols = $this->columns('contacts');
        $q = DB::table('contacts')->where('business_id', $businessId);
        if (in_array('type', $cols, true)) {
            $q->where(function ($x) use ($type) {
                $x->where('type', $type)->orWhere('type', 'both');
            });
        }
        if (in_array('is_active', $cols, true)) {
            $q->where('is_active', 1);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }

        return $this->memo[$key] = $q->orderBy('name')
            ->limit(5000)
            ->get(['id', 'name'])
            ->map(static fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name])
            ->all();
    }

    public function locations(int $businessId): array
    {
        return $this->locationAccess->options($businessId);
    }

    /**
     * Rice Mill transaction setup can legitimately span all Locations owned by
     * the active Business.  Use the business boundary (tenant DB + business_id)
     * for administrative/operational Rice Mill screens that are explicitly
     * designed for multi-location processing.
     *
     * @return array<int, array{id:int,name:string,business_id:int}>
     */
    public function businessLocations(int $businessId): array
    {
        return $this->locationAccess->businessOptions($businessId);
    }

    public function stores(int $businessId): array
    {
        $key = 'stores:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $meta = $this->storeMeta();
        if (! $meta) {
            return $this->memo[$key] = [];
        }

        $q = DB::table($meta['table']);
        if ($meta['business']) {
            $q->where('business_id', $businessId);
        }

        if ($meta['location']) {
            $allowedLocationIds = $this->locationAccess->ids($businessId);
            if (! $allowedLocationIds) {
                return $this->memo[$key] = [];
            }
            $q->whereIn('location_id', $allowedLocationIds);
        }

        $this->applyActiveStoreScope($q, $meta);
        $select = ['id', $meta['name']];
        if ($meta['location']) {
            $select[] = 'location_id';
        }

        return $this->memo[$key] = $q->orderBy($meta['name'])->get($select)
            ->map(static function ($row) use ($meta) {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->{$meta['name']},
                    'location_id' => isset($row->location_id) ? (int) $row->location_id : null,
                ];
            })->all();
    }

    /** Return every active Store for the active Tenant UID + Business UID. */
    public function businessStores(int $businessId): array
    {
        $key = 'business-stores:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $meta = $this->storeMeta();
        if (! $meta) {
            return $this->memo[$key] = [];
        }

        $query = DB::table($meta['table']);
        if ($meta['business']) {
            $query->where('business_id', $businessId);
        }
        $this->applyActiveStoreScope($query, $meta);

        $select = ['id', $meta['name']];
        if ($meta['location']) {
            $select[] = 'location_id';
        }

        return $this->memo[$key] = $query->orderBy($meta['name'])->get($select)
            ->map(static function ($row) use ($meta) {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->{$meta['name']},
                    'location_id' => isset($row->location_id) ? (int) $row->location_id : null,
                ];
            })->all();
    }

    /**
     * Resolve a historical Store name inside the active tenant/business even
     * when that Store has since been deactivated.  Read-only history pages use
     * this instead of the active-store dropdown list.
     */
    public function businessStoreName(int $businessId, ?int $storeId): ?string
    {
        if (! $storeId) {
            return null;
        }

        $meta = $this->storeMeta();
        if (! $meta) {
            return null;
        }

        $query = DB::table($meta['table'])->where('id', $storeId);
        if ($meta['business']) {
            $query->where('business_id', $businessId);
        }

        $name = $query->value($meta['name']);
        return $name !== null ? (string) $name : null;
    }


    /** Validate one Business Location through the same tenant/business boundary. */
    public function assertBusinessLocation(?int $locationId, int $businessId, bool $required = false): void
    {
        $this->locationAccess->assertBusinessLocation($locationId, $businessId, $required);
    }

    /** Validate one Store directly instead of loading every Store into PHP. */
    public function assertBusinessStore(?int $storeId, int $businessId, ?int $locationId = null): void
    {
        if (! $storeId) {
            return;
        }

        $meta = $this->storeMeta();
        abort_unless($meta, 403, 'Stores are not available in the active tenant.');

        $query = DB::table($meta['table'])->where('id', $storeId);
        if ($meta['business']) {
            $query->where('business_id', $businessId);
        }
        if ($locationId && $meta['location']) {
            $query->where('location_id', $locationId);
        }
        $this->applyActiveStoreScope($query, $meta);

        abort_unless($query->exists(), 403, 'The selected store does not belong to the active tenant/business/location.');
    }

    /** Validate one Store directly against the operational Location boundary. */
    public function assertStore(?int $storeId, int $businessId, ?int $locationId = null): void
    {
        if (! $storeId) {
            return;
        }

        $meta = $this->storeMeta();
        abort_unless($meta, 403, 'Stores are not available in the active tenant.');

        $query = DB::table($meta['table'])->where('id', $storeId);
        if ($meta['business']) {
            $query->where('business_id', $businessId);
        }
        if ($meta['location']) {
            $allowedLocationIds = $this->locationAccess->ids($businessId);
            abort_unless($allowedLocationIds, 403, 'No Store location is available for this business or user.');
            $query->whereIn('location_id', $allowedLocationIds);
            if ($locationId) {
                $query->where('location_id', $locationId);
            }
        }
        $this->applyActiveStoreScope($query, $meta);

        abort_unless($query->exists(), 403, 'The selected store is not available for this business, user or selected location.');
    }

    public function users(int $businessId): array
    {
        $key = 'users:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }
        if (! Schema::hasTable('users')) {
            return $this->memo[$key] = [];
        }

        $cols = $this->columns('users');
        $q = DB::table('users');
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }

        $select = ['id'];
        foreach (['first_name', 'last_name', 'username'] as $column) {
            if (in_array($column, $cols, true)) {
                $select[] = $column;
            }
        }

        return $this->memo[$key] = $q->orderBy(in_array('first_name', $cols, true) ? 'first_name' : 'id')
            ->limit(5000)
            ->get($select)
            ->map(static function ($r) {
                $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                return [
                    'id' => (int) $r->id,
                    'name' => $name !== '' ? $name : ($r->username ?? ('User ' . $r->id)),
                ];
            })->all();
    }

    /**
     * Product categories from the live Products New category master.
     *
     * IMPORTANT: Products New's current Category screens read/write the shared
     * tenant `categories` table. Therefore these IDs are exactly the IDs the
     * Products New module uses; Rice Mill must not read `products_new_categories`.
     *
     * @return array<int,array{id:int,name:string}>
     */
    public function productCategories(int $businessId): array
    {
        // Product Category Mapping must show CATEGORY rows only. Sub categories
        // are exposed separately by productCategoryHierarchy() and must never
        // appear in the Paddy/Rice Product Category dropdowns.
        return $this->productCategoryHierarchy($businessId)['categories'] ?? [];
    }

    /**
     * Split the live Products New category master into Category and Sub Category
     * dropdown data. Products New uses the tenant `categories` table.
     *
     * @return array{categories:array<int,array{id:int,name:string}>,subcategories:array<int,array{id:int,name:string,parent_id:int,parent_name:string}>}
     */
    public function productCategoryHierarchy(int $businessId): array
    {
        $key = 'products-new-live-category-hierarchy:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $result = ['categories' => [], 'subcategories' => []];
        if (! Schema::hasTable('categories')) {
            return $this->memo[$key] = $result;
        }

        $cols = $this->columns('categories');
        if (! in_array('id', $cols, true) || ! in_array('name', $cols, true)) {
            return $this->memo[$key] = $result;
        }

        $q = DB::table('categories');
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }

        $select = ['id','name'];
        if (in_array('parent_id', $cols, true)) {
            $select[] = 'parent_id';
        }

        $rows = $q->orderBy('name')->limit(5000)->get($select);
        $names = [];
        foreach ($rows as $row) {
            $names[(int) $row->id] = (string) $row->name;
        }

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $parentId = in_array('parent_id', $cols, true) ? (int) ($row->parent_id ?? 0) : 0;
            if ($parentId > 0) {
                $result['subcategories'][] = [
                    'id' => $id,
                    'name' => (string) $row->name,
                    'parent_id' => $parentId,
                    'parent_name' => (string) ($names[$parentId] ?? ''),
                ];
            } else {
                $result['categories'][] = [
                    'id' => $id,
                    'name' => (string) $row->name,
                ];
            }
        }

        usort($result['categories'], static fn ($a,$b) => strnatcasecmp($a['name'],$b['name']));
        usort($result['subcategories'], static function ($a,$b) {
            $parent = strnatcasecmp($a['parent_name'],$b['parent_name']);
            return $parent !== 0 ? $parent : strnatcasecmp($a['name'],$b['name']);
        });

        return $this->memo[$key] = $result;
    }

    /**
     * All active Products from the same live Product source used by Products New.
     * Returned ids are exactly `products.id`.
     *
     * @return array<int,array{id:int,name:string,code:string,category_id:int|null,sub_category_id:int|null,unit:string}>
     */
    public function productsNewProducts(int $businessId): array
    {
        $key = 'products-new-live-all-products:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }
        if (! Schema::hasTable('products')) {
            return $this->memo[$key] = [];
        }

        $cols = $this->columns('products');
        if (! in_array('id', $cols, true) || ! in_array('name', $cols, true)) {
            return $this->memo[$key] = [];
        }

        $q = DB::table('products');
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        // `not_for_selling` is a sales restriction, not an inactive Product flag.
        // Rice Mill must still be able to select raw Paddy, packaging and Output Products.
        if (in_array('is_inactive', $cols, true)) {
            $q->where(function ($x) { $x->whereNull('is_inactive')->orWhere('is_inactive', 0); });
        }
        if (in_array('products_new_status', $cols, true)) {
            $q->where(function ($x) {
                $x->whereNull('products_new_status')
                    ->orWhereNotIn('products_new_status', ['inactive','suspended','discontinued','archived']);
            });
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }

        $select = ['id','name'];
        foreach (['sku','category_id','sub_category_id','unit_id'] as $column) {
            if (in_array($column, $cols, true)) {
                $select[] = $column;
            }
        }
        $rows = $q->orderBy('name')->limit(5000)->get($select);

        $unitMap = [];
        if (in_array('unit_id', $cols, true) && Schema::hasTable('units')) {
            $unitCols = $this->columns('units');
            $unitNameCol = null;
            foreach (['short_name','actual_name','name'] as $candidate) {
                if (in_array($candidate, $unitCols, true)) {
                    $unitNameCol = $candidate;
                    break;
                }
            }
            if ($unitNameCol && in_array('id', $unitCols, true)) {
                $unitIds = $rows->pluck('unit_id')->filter()->map(fn ($id)=>(int)$id)->unique()->values()->all();
                if ($unitIds) {
                    $uq = DB::table('units')->whereIn('id', $unitIds);
                    if (in_array('business_id', $unitCols, true)) {
                        $uq->where('business_id', $businessId);
                    }
                    if (in_array('deleted_at', $unitCols, true)) {
                        $uq->whereNull('deleted_at');
                    }
                    $unitMap = $uq->pluck($unitNameCol, 'id')
                        ->mapWithKeys(static fn ($name,$id)=>[(int)$id=>(string)$name])->all();
                }
            }
        }

        return $this->memo[$key] = $rows->map(static function ($row) use ($unitMap) {
            $unitId = isset($row->unit_id) ? (int) $row->unit_id : 0;
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'code' => trim((string) ($row->sku ?? '')),
                'category_id' => isset($row->category_id) ? (int) $row->category_id : null,
                'sub_category_id' => isset($row->sub_category_id) ? (int) $row->sub_category_id : null,
                'unit' => trim((string) ($unitMap[$unitId] ?? '')) ?: 'pcs',
            ];
        })->all();
    }

    /**
     * Products from the live Products New Product master for one mapped category.
     *
     * Products New's current Product screens read/write the shared tenant
     * `products` table, so every ID returned here is exactly `products.id` as
     * used by Products New itself.
     *
     * @return array<int,array{id:int,name:string,code:string}>
     */
    public function productsByCategory(int $businessId, int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $key = 'products-new-live-by-category:' . $businessId . ':' . $categoryId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if (! Schema::hasTable('products')) {
            return $this->memo[$key] = [];
        }

        $cols = $this->columns('products');
        if (! in_array('id', $cols, true) || ! in_array('name', $cols, true)) {
            return $this->memo[$key] = [];
        }

        // A mapped Product Category represents the whole category tree. Products
        // may be stored against the parent category or one of its sub categories,
        // depending on the Products New record. Include both forms.
        $categoryIds = $this->categoryTreeIds($businessId, $categoryId);
        if (! $categoryIds) {
            $categoryIds = [$categoryId];
        }

        $query = DB::table('products');
        if (in_array('business_id', $cols, true)) {
            $query->where('business_id', $businessId);
        }

        if (in_array('category_id', $cols, true) || in_array('sub_category_id', $cols, true)) {
            $query->where(function ($q) use ($categoryIds, $cols) {
                $hasCategory = in_array('category_id', $cols, true);
                if ($hasCategory) {
                    $q->whereIn('category_id', $categoryIds);
                }
                if (in_array('sub_category_id', $cols, true)) {
                    $hasCategory ? $q->orWhereIn('sub_category_id', $categoryIds) : $q->whereIn('sub_category_id', $categoryIds);
                }
            });
        } else {
            return $this->memo[$key] = [];
        }

        // Do not filter `not_for_selling`: Paddy/raw-material Products are often
        // intentionally not sellable but must still be available to Rice Mill.
        if (in_array('is_inactive', $cols, true)) {
            $query->where(function ($q) {
                $q->whereNull('is_inactive')->orWhere('is_inactive', 0);
            });
        }
        if (in_array('products_new_status', $cols, true)) {
            $query->where(function ($q) {
                $q->whereNull('products_new_status')
                    ->orWhereNotIn('products_new_status', ['inactive','suspended','discontinued','archived']);
            });
        }
        if (in_array('deleted_at', $cols, true)) {
            $query->whereNull('deleted_at');
        }

        $select = ['id','name'];
        if (in_array('sku', $cols, true)) {
            $select[] = 'sku';
        }

        return $this->memo[$key] = $query
            ->orderBy('name')
            ->limit(5000)
            ->get($select)
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'code' => trim((string) ($row->sku ?? '')),
            ])
            ->all();
    }

    /**
     * Full Products New rows for one mapped top-level Category, including Products
     * linked through any descendant Sub Category.
     *
     * @return array<int,array{id:int,name:string,code:string,category_id:int|null,sub_category_id:int|null,unit:string}>
     */
    public function productsNewProductsForCategory(int $businessId, int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }
        $allowed = array_flip($this->categoryTreeIds($businessId, $categoryId));
        if (! $allowed) {
            $allowed[$categoryId] = true;
        }

        return array_values(array_filter(
            $this->productsNewProducts($businessId),
            static function (array $product) use ($allowed): bool {
                $category = (int) ($product['category_id'] ?? 0);
                $subCategory = (int) ($product['sub_category_id'] ?? 0);
                return isset($allowed[$category]) || isset($allowed[$subCategory]);
            }
        ));
    }

    /** Resolve a Category/Sub Category id to its top-level Products New Category. */
    public function topLevelProductCategoryId(int $businessId, int $categoryId): int
    {
        if ($categoryId <= 0 || ! Schema::hasTable('categories')) {
            return $categoryId;
        }
        $key = 'products-new-top-category:' . $businessId . ':' . $categoryId;
        if (array_key_exists($key, $this->memo)) {
            return (int) $this->memo[$key];
        }

        $cols = $this->columns('categories');
        if (! in_array('id', $cols, true) || ! in_array('parent_id', $cols, true)) {
            return (int) ($this->memo[$key] = $categoryId);
        }

        $q = DB::table('categories')->select(['id','parent_id']);
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }
        $parents = $q->pluck('parent_id','id')->mapWithKeys(static fn ($parent,$id) => [(int)$id => (int)($parent ?? 0)])->all();

        $current = $categoryId;
        $seen = [];
        while ($current > 0 && ! isset($seen[$current])) {
            $seen[$current] = true;
            $parent = (int) ($parents[$current] ?? 0);
            if ($parent <= 0) {
                break;
            }
            $current = $parent;
        }
        return (int) ($this->memo[$key] = ($current > 0 ? $current : $categoryId));
    }

    /** @return array<int,int> */
    private function categoryTreeIds(int $businessId, int $rootCategoryId): array
    {
        if ($rootCategoryId <= 0 || ! Schema::hasTable('categories')) {
            return [];
        }
        $rootCategoryId = $this->topLevelProductCategoryId($businessId, $rootCategoryId);
        $cols = $this->columns('categories');
        if (! in_array('id', $cols, true)) {
            return [$rootCategoryId];
        }
        if (! in_array('parent_id', $cols, true)) {
            return [$rootCategoryId];
        }

        $q = DB::table('categories')->select(['id','parent_id']);
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }
        $rows = $q->get();
        $children = [];
        foreach ($rows as $row) {
            $parent = (int) ($row->parent_id ?? 0);
            $children[$parent][] = (int) $row->id;
        }

        $result = [];
        $queue = [$rootCategoryId];
        while ($queue) {
            $id = (int) array_shift($queue);
            if ($id <= 0 || in_array($id, $result, true)) {
                continue;
            }
            $result[] = $id;
            foreach ($children[$id] ?? [] as $childId) {
                $queue[] = (int) $childId;
            }
        }
        return $result;
    }

    /**
     * Resolve one Product using the live Product source of Products New.
     * The returned ID is exactly the Product ID used by Products New (`products.id`).
     *
     * @return array{id:int,name:string,code:string,category_id:int|null,sub_category_id:int|null,unit:string}|null
     */
    public function productsNewProduct(int $businessId, int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }
        foreach ($this->productsNewProducts($businessId) as $product) {
            if ((int) $product['id'] === $productId) {
                return $product;
            }
        }
        return null;
    }

    /** @return array<int,array{id:int,name:string,amount:float,is_tax_group:int}> */
    public function purchaseTaxes(int $businessId): array
    {
        $key = 'purchase-taxes:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }
        if (! Schema::hasTable('tax_rates')) {
            return $this->memo[$key] = [];
        }

        $cols = $this->columns('tax_rates');
        if (! in_array('id', $cols, true) || ! in_array('name', $cols, true)) {
            return $this->memo[$key] = [];
        }
        $q = DB::table('tax_rates');
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }
        if (in_array('is_active', $cols, true)) {
            $q->where('is_active', 1);
        }

        $select = ['id','name'];
        if (in_array('amount', $cols, true)) $select[] = 'amount';
        if (in_array('is_tax_group', $cols, true)) $select[] = 'is_tax_group';

        return $this->memo[$key] = $q->orderBy('name')->get($select)->map(static function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'amount' => (float) ($r->amount ?? 0),
                'is_tax_group' => (int) ($r->is_tax_group ?? 0),
            ];
        })->all();
    }

    public function purchaseTaxById(int $businessId, int $taxId): ?array
    {
        foreach ($this->purchaseTaxes($businessId) as $tax) {
            if ((int) $tax['id'] === $taxId) return $tax;
        }
        return null;
    }

    /**
     * List every open List Account classified under Current Liabilities.
     *
     * Finance/List Accounts classifies accounts primarily through
     * accounts.account_type_id -> account_types.id. Account Groups are an
     * additional classification through accounts.asset_type -> account_groups.id,
     * and account_groups.account_type_id links each group back to its Account Type.
     *
     * The previous Rice Mill query incorrectly looked for an Account Group literally
     * named "Current Liabilities". In this ERP, "Current Liabilities" is normally
     * an Account Type/Sub Type, while groups below it can have names such as
     * Accounts Payable or other business-defined groups.
     *
     * Therefore include an account when either:
     *  - its account_type_id is Current Liabilities (or a child type), OR
     *  - its asset_type points to an Account Group linked to Current Liabilities.
     *
     * @return array<int,array{id:int,name:string}>
     */
    public function currentLiabilityAccounts(int $businessId): array
    {
        $key = 'current-liability-accounts:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if (! Schema::hasTable('accounts') || ! Schema::hasTable('account_types')) {
            return $this->memo[$key] = [];
        }

        $accountCols = $this->columns('accounts');
        $typeCols = $this->columns('account_types');
        if (
            ! in_array('id', $accountCols, true)
            || ! in_array('name', $accountCols, true)
            || ! in_array('account_type_id', $accountCols, true)
            || ! in_array('id', $typeCols, true)
            || ! in_array('name', $typeCols, true)
        ) {
            return $this->memo[$key] = [];
        }

        // Resolve the Current Liabilities Account Type used by Finance/List Accounts.
        $typeQ = DB::table('account_types')
            ->whereRaw("LOWER(TRIM(name)) IN ('current liabilities', 'current liability')");
        if (in_array('business_id', $typeCols, true)) {
            $typeQ->where('business_id', $businessId);
        }
        if (in_array('deleted_at', $typeCols, true)) {
            $typeQ->whereNull('deleted_at');
        }

        $typeIds = $typeQ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (! $typeIds) {
            return $this->memo[$key] = [];
        }

        // Include any child Account Types below Current Liabilities as well.
        if (in_array('parent_account_type_id', $typeCols, true)) {
            $allTypesQ = DB::table('account_types');
            if (in_array('business_id', $typeCols, true)) {
                $allTypesQ->where('business_id', $businessId);
            }
            if (in_array('deleted_at', $typeCols, true)) {
                $allTypesQ->whereNull('deleted_at');
            }
            $allTypes = $allTypesQ->get(['id', 'parent_account_type_id']);

            $resolved = array_fill_keys($typeIds, true);
            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($allTypes as $type) {
                    $id = (int) $type->id;
                    $parent = (int) ($type->parent_account_type_id ?? 0);
                    if ($parent > 0 && isset($resolved[$parent]) && ! isset($resolved[$id])) {
                        $resolved[$id] = true;
                        $changed = true;
                    }
                }
            }
            $typeIds = array_map('intval', array_keys($resolved));
        }

        // Resolve all Account Groups belonging to Current Liabilities.
        $groupIds = [];
        if (Schema::hasTable('account_groups')) {
            $groupCols = $this->columns('account_groups');
            if (in_array('id', $groupCols, true) && in_array('account_type_id', $groupCols, true)) {
                $groupQ = DB::table('account_groups')->whereIn('account_type_id', $typeIds);
                if (in_array('business_id', $groupCols, true)) {
                    $groupQ->where('business_id', $businessId);
                }
                if (in_array('deleted_at', $groupCols, true)) {
                    $groupQ->whereNull('deleted_at');
                }
                if (in_array('is_active', $groupCols, true)) {
                    $groupQ->where('is_active', 1);
                }

                $groupIds = $groupQ->pluck('id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        $q = DB::table('accounts');
        if ($groupIds && in_array('asset_type', $accountCols, true)) {
            $q->where(function ($x) use ($typeIds, $groupIds) {
                $x->whereIn('account_type_id', $typeIds)
                    ->orWhereIn('asset_type', $groupIds);
            });
        } else {
            $q->whereIn('account_type_id', $typeIds);
        }

        if (in_array('business_id', $accountCols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('is_closed', $accountCols, true)) {
            $q->where('is_closed', 0);
        }
        if (in_array('deleted_at', $accountCols, true)) {
            $q->whereNull('deleted_at');
        }
        // Match Finance/List Accounts: an account is available while it is not closed.
        // Do not additionally filter by visible/active flags because valid Current
        // Liabilities accounts can be hidden from some generic payment lists while
        // still being valid List Accounts for accounting setup.

        return $this->memo[$key] = $q->orderBy('name')->get(['id','name'])
            ->map(static fn ($r) => ['id'=>(int)$r->id,'name'=>(string)$r->name])
            ->all();
    }

    /**
     * Backwards-compatible alias retained for older Rice Mill code paths.
     * The Settings requirement now uses the Current Liabilities Account Group.
     *
     * @return array<int,array{id:int,name:string}>
     */
    public function accountsPayableAccounts(int $businessId): array
    {
        return $this->currentLiabilityAccounts($businessId);
    }

    /**
     * Payment methods enabled/mapped for this business and the Account Books
     * linked to each method in Super Admin / All Businesses / Manage New.
     *
     * The latest Manage New screen stores an Account Group id in
     * business_locations.default_payment_accounts[method].account. Older
     * installations may store an Accounts.id directly. This adapter accepts
     * both formats and expands Account Groups to their open List Accounts.
     *
     * @return array{methods:array<string,string>,accounts:array<string,array<int,string>>,by_location:array<int,array<string,array<int,string>>>}
     */
    public function paymentMethodAccounts(int $businessId, string $context = 'purchase'): array
    {
        $context = strtolower(trim($context));
        $flag = match ($context) {
            'sale', 'sales' => 'is_sale_enabled',
            'expense', 'expenses', 'production' => 'is_expense_enabled',
            'purchase_return' => 'is_purchase_return_enabled',
            'sale_return' => 'is_sale_return_enabled',
            default => 'is_purchase_enabled',
        };

        $key = 'payment-method-accounts:' . $businessId . ':' . $flag;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $result = ['methods'=>[], 'accounts'=>[], 'by_location'=>[]];
        if (! Schema::hasTable('business_locations')) {
            return $this->memo[$key] = $result;
        }
        $cols = $this->columns('business_locations');
        if (! in_array('id', $cols, true) || ! in_array('default_payment_accounts', $cols, true)) {
            return $this->memo[$key] = $result;
        }

        $labels = $this->paymentMethodLabels($businessId);
        $q = DB::table('business_locations');
        if (in_array('business_id', $cols, true)) {
            $q->where('business_id', $businessId);
        }
        if (in_array('is_active', $cols, true)) {
            $q->where('is_active', 1);
        }
        if (in_array('deleted_at', $cols, true)) {
            $q->whereNull('deleted_at');
        }

        $locations = $q->get(['id','default_payment_accounts']);
        foreach ($locations as $location) {
            $decoded = $this->decodeJsonArray($location->default_payment_accounts ?? null);
            foreach ($decoded as $method => $config) {
                $method = trim((string) $method);
                if ($method === '') {
                    continue;
                }
                $config = is_array($config) ? $config : ['account'=>$config];

                $enabled = ! array_key_exists('is_enabled', $config)
                    || $this->truthyPaymentFlag($config['is_enabled']);
                // Legacy locations did not have per-screen flags. Treat a
                // missing flag as enabled, matching Manage New's compatibility
                // behaviour; an explicit 0 always disables it.
                $screenEnabled = ! array_key_exists($flag, $config)
                    || $this->truthyPaymentFlag($config[$flag]);

                if (! $enabled || ! $screenEnabled) {
                    continue;
                }

                $accountToken = isset($config['account'])
                    ? (int) $config['account']
                    : (isset($config['account_id']) ? (int) $config['account_id'] : 0);
                if ($accountToken <= 0) {
                    continue;
                }

                $accounts = $this->resolvePaymentAccountBooks($businessId, $accountToken, (int) $location->id);
                if (! $accounts) {
                    continue;
                }

                $label = $labels[$method] ?? ucwords(str_replace('_',' ', $method));
                $result['methods'][$method] = $label;
                foreach ($accounts as $accountId => $accountName) {
                    $result['accounts'][$method][(int)$accountId] = (string)$accountName;
                    $result['by_location'][(int)$location->id][$method][(int)$accountId] = (string)$accountName;
                }
            }
        }

        ksort($result['methods']);
        foreach ($result['accounts'] as &$accounts) {
            asort($accounts, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($accounts);
        foreach ($result['by_location'] as &$locationMethods) {
            foreach ($locationMethods as &$accounts) {
                asort($accounts, SORT_NATURAL | SORT_FLAG_CASE);
            }
            unset($accounts);
        }
        unset($locationMethods);

        return $this->memo[$key] = $result;
    }

    /**
     * Existing Purchase code keeps its public API while using the corrected
     * Manage New Account Group -> Account Book resolver above.
     */
    public function purchasePaymentMethodAccounts(int $businessId): array
    {
        return $this->paymentMethodAccounts($businessId, 'purchase');
    }

    /** @return array<int,string> */
    private function resolvePaymentAccountBooks(int $businessId, int $accountToken, ?int $locationId = null): array
    {
        if (! Schema::hasTable('accounts')) {
            return [];
        }

        $accountCols = $this->columns('accounts');

        // Manage New saves an Account Group id in
        // business_locations.default_payment_accounts[method].account.
        // Once that Account Group exists for this business it is authoritative:
        // expand ONLY that group to its real Account Books.  Do not fall back to
        // Accounts.id merely because the selected group currently has no books;
        // Account Group ids and Account ids can overlap and that old fallback can
        // therefore display a completely unrelated account (for example Petty Cash
        // for Direct Bank Deposit).
        if (Schema::hasTable('account_groups')) {
            $groupCols = $this->columns('account_groups');
            $groupQuery = DB::table('account_groups')->where('id', $accountToken);
            if (in_array('business_id', $groupCols, true)) {
                $groupQuery->where('business_id', $businessId);
            }
            if (in_array('deleted_at', $groupCols, true)) {
                $groupQuery->whereNull('deleted_at');
            }

            $groupExists = $groupQuery->exists();
            if ($groupExists) {
                if (! in_array('asset_type', $accountCols, true)) {
                    return [];
                }

                $accounts = DB::table('accounts')->where('asset_type', $accountToken);
                if (in_array('business_id', $accountCols, true)) {
                    $accounts->where('business_id', $businessId);
                }
                // Account::getAccountByAccountGroupId() uses non-main Account
                // Books. Keep the same rule here so the dropdown does not expose
                // a parent/main account instead of the selectable Account Books.
                if (in_array('is_main_account', $accountCols, true)) {
                    $accounts->where('is_main_account', 0);
                }
                if (in_array('is_closed', $accountCols, true)) {
                    $accounts->where('is_closed', 0);
                }
                if (in_array('deleted_at', $accountCols, true)) {
                    $accounts->whereNull('deleted_at');
                }

                // The payment-method mapping itself is per Business Location.
                // Account Books may be exact-location or business-wide. Older
                // databases also use an empty string for business-wide, so treat
                // '', 'all' and NULL consistently.
                $baseAccounts = clone $accounts;
                if ($locationId && in_array('location_id', $accountCols, true)) {
                    $accounts->where(function ($q) use ($locationId) {
                        $q->where('location_id', (string) $locationId)
                            ->orWhere('location_id', 'all')
                            ->orWhere('location_id', '')
                            ->orWhereNull('location_id');
                    });
                }

                $resolved = $accounts->orderBy('name')->pluck('name','id')
                    ->mapWithKeys(static fn ($name,$id)=>[(int)$id=>(string)$name])
                    ->all();

                // Some older accounts were created before location_id existed
                // or carry a stale location value. If the selected Account Group
                // is definitely valid but the location scope produces no rows,
                // fall back ONLY to open Account Books within that same mapped
                // group. Never reinterpret the group id as an Accounts.id.
                if (! $resolved && $locationId) {
                    $resolved = $baseAccounts->orderBy('name')->pluck('name','id')
                        ->mapWithKeys(static fn ($name,$id)=>[(int)$id=>(string)$name])
                        ->all();
                }

                return $resolved;
            }
        }

        // Backwards compatibility only for genuinely old data where the token
        // is an Accounts.id and no Account Group with that id exists.
        return $this->accountNamesByIds($businessId, [$accountToken], $locationId);
    }

    private function truthyPaymentFlag($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        return in_array(strtolower(trim((string)$value)), ['1','true','yes','on'], true);
    }

    /** @return array<int,string> */
    private function accountNamesByIds(int $businessId, array $ids, ?int $locationId = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval',$ids), static fn ($id)=>$id>0)));
        if (! $ids || ! Schema::hasTable('accounts')) {
            return [];
        }
        $cols = $this->columns('accounts');
        $q = DB::table('accounts')->whereIn('id',$ids);
        if (in_array('business_id',$cols,true)) {
            $q->where('business_id',$businessId);
        }
        if (in_array('is_closed',$cols,true)) {
            $q->where('is_closed',0);
        }
        if (in_array('deleted_at',$cols,true)) {
            $q->whereNull('deleted_at');
        }
        if ($locationId && in_array('location_id',$cols,true)) {
            $q->where(function ($x) use ($locationId) {
                $x->where('location_id',(string)$locationId)
                    ->orWhere('location_id','all')
                    ->orWhere('location_id','')
                    ->orWhereNull('location_id');
            });
        }
        return $q->pluck('name','id')->mapWithKeys(static fn ($name,$id)=>[(int)$id=>(string)$name])->all();
    }

    /**
     * Product-level Available quantity from Products New Stock Center.
     *
     * The Products New service is used when installed so this value follows
     * the exact Stock Center location/business rules. A schema-safe fallback
     * is retained for installations where that service is unavailable.
     *
     * @param array<int,int> $productIds
     * @return array<int,float>
     */
    public function productsNewAvailableStockByProduct(int $businessId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval',$productIds), static fn ($id)=>$id>0)));
        if (! $productIds) {
            return [];
        }

        $serviceClass = '\\Modules\\ProductsNew\\Services\\StockCenterService';
        if (class_exists($serviceClass)) {
            try {
                $stock = app($serviceClass);
                $filters = $stock->normaliseFilters([]);
                $query = $stock->query($filters);
                $rows = $query->whereIn('p.id', $productIds)->get();
                $totals = array_fill_keys($productIds, 0.0);
                foreach ($rows as $row) {
                    $pid = (int) ($row->product_id ?? 0);
                    if ($pid > 0) {
                        $totals[$pid] = ($totals[$pid] ?? 0.0) + (float) ($row->qty_available ?? 0);
                    }
                }
                return $totals;
            } catch (\Throwable $e) {
                // Fall through to the same core stock tables below.
            }
        }

        if (! Schema::hasTable('products') || ! Schema::hasTable('variations') || ! Schema::hasTable('variation_location_details')) {
            return array_fill_keys($productIds, 0.0);
        }

        $allowedLocations = $this->locations($businessId);
        $locationIds = array_values(array_filter(array_map(
            static fn ($row)=>(int)($row['id'] ?? 0),
            $allowedLocations
        )));
        $locationId = (int) (session('user.location_id') ?: 0);
        if ($locationId <= 0 || ! in_array($locationId, $locationIds, true)) {
            $locationId = $locationIds[0] ?? 0;
        }

        $query = DB::table('variation_location_details as vld')
            ->join('variations as v','v.id','=','vld.variation_id')
            ->join('products as p','p.id','=','v.product_id')
            ->where('p.business_id',$businessId)
            ->whereIn('p.id',$productIds);
        if ($locationId > 0) {
            $query->where('vld.location_id',$locationId);
        }
        if (in_array('deleted_at',$this->columns('variations'),true)) {
            $query->whereNull('v.deleted_at');
        }
        if (in_array('deleted_at',$this->columns('variation_location_details'),true)) {
            $query->whereNull('vld.deleted_at');
        }

        $rows = $query->groupBy('p.id')
            ->select('p.id as product_id')
            ->selectRaw('COALESCE(SUM(vld.qty_available),0) as qty_available')
            ->get();

        $totals = array_fill_keys($productIds, 0.0);
        foreach ($rows as $row) {
            $totals[(int)$row->product_id] = (float)$row->qty_available;
        }
        return $totals;
    }

    /** @return array<string,string> */
    private function paymentMethodLabels(int $businessId): array
    {
        $labels = [
            'cash'=>'Cash', 'card'=>'Card', 'cheque'=>'Cheque',
            'bank_transfer'=>'Bank Transfer', 'other'=>'Other',
            'custom_pay_1'=>'Custom Payment 1', 'custom_pay_2'=>'Custom Payment 2',
            'custom_pay_3'=>'Custom Payment 3',
        ];

        foreach (['business','businesses'] as $table) {
            if (! Schema::hasTable($table)) continue;
            $cols = $this->columns($table);
            if (! in_array('id',$cols,true)) continue;
            $q = DB::table($table);
            if (in_array('id',$cols,true)) $q->where('id',$businessId);
            $select = [];
            foreach (['custom_labels','custom_payment_1','custom_payment_2','custom_payment_3'] as $column) {
                if (in_array($column,$cols,true)) $select[]=$column;
            }
            if (!$select) break;
            $row = $q->first($select);
            if (!$row) break;
            if (isset($row->custom_labels)) {
                $custom = $this->decodeJsonArray($row->custom_labels);
                foreach ([1,2,3] as $n) {
                    foreach (["custom_payment_{$n}","custom_pay_{$n}"] as $ck) {
                        if (!empty($custom[$ck])) $labels["custom_pay_{$n}"]=(string)$custom[$ck];
                    }
                }
            }
            foreach ([1,2,3] as $n) {
                $column="custom_payment_{$n}";
                if (isset($row->{$column}) && trim((string)$row->{$column})!=='') {
                    $labels["custom_pay_{$n}"]=(string)$row->{$column};
                }
            }
            break;
        }
        return $labels;
    }

    private function decodeJsonArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value)) return (array)$value;
        if (! is_string($value) || trim($value)==='') return [];
        $decoded = json_decode($value,true);
        return is_array($decoded) ? $decoded : [];
    }

    private function columns(string $table): array
    {
        if (! array_key_exists($table, $this->columnMemo)) {
            $this->columnMemo[$table] = Schema::getColumnListing($table);
        }
        return $this->columnMemo[$table];
    }

    private function storeMeta(): ?array
    {
        if ($this->storeMetaResolved) {
            return $this->storeMetaMemo;
        }
        $this->storeMetaResolved = true;

        foreach (['stores', 'business_stores'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $cols = $this->columns($table);
            if (! in_array('id', $cols, true)) {
                continue;
            }
            $nameCol = in_array('name', $cols, true)
                ? 'name'
                : (in_array('store_name', $cols, true) ? 'store_name' : null);
            if (! $nameCol) {
                continue;
            }

            return $this->storeMetaMemo = [
                'table' => $table,
                'columns' => $cols,
                'name' => $nameCol,
                'business' => in_array('business_id', $cols, true),
                'location' => in_array('location_id', $cols, true),
                'status' => in_array('status', $cols, true),
                'active' => in_array('is_active', $cols, true),
                'deleted' => in_array('deleted_at', $cols, true),
            ];
        }

        return $this->storeMetaMemo = null;
    }

    private function applyActiveStoreScope($query, array $meta): void
    {
        if ($meta['status']) {
            $query->where('status', 1);
        } elseif ($meta['active']) {
            $query->where('is_active', 1);
        }
        if ($meta['deleted']) {
            $query->whereNull('deleted_at');
        }
    }
}
