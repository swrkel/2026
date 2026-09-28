<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryDataService
{
    /** @var array<int, array<int, array<string, mixed>>> */
    protected array $subUnitCache = [];

    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema,
        protected PurchaseEntryFormService $form,
        protected PurchaseEntrySupplierLookupService $supplierLookup
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function suppliers(string $term = '', int $page = 1, int $perPage = PurchaseEntrySupplierLookupService::DEFAULT_PAGE_SIZE): array
    {
        return $this->supplierLookup->search($term, $page, $perPage)['results'];
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, pagination: array{page:int, per_page:int, more:bool}}
     */
    public function supplierPage(
        string $term = '',
        int $page = 1,
        int $perPage = PurchaseEntrySupplierLookupService::DEFAULT_PAGE_SIZE
    ): array {
        return $this->supplierLookup->search($term, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function supplier(int $id): ?array
    {
        if (! Schema::hasTable('contacts')) {
            return null;
        }

        $query = DB::table('contacts')
            ->where('business_id', $this->numbers->businessId())
            ->whereIn('type', ['supplier', 'both'])
            ->where('id', $id);
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $row = $query->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'name' => trim((string) ($row->supplier_business_name ?: $row->name)),
            'pay_term_number' => $row->pay_term_number ?? null,
            'pay_term_type' => $row->pay_term_type ?? null,
            'mobile' => $row->mobile ?? null,
            'email' => $row->email ?? null,
            'outstanding' => $this->supplierOutstanding((int) $row->id),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function pendingPurchaseOrders(int $supplierId): array
    {
        if (! Schema::hasTable('transactions')) {
            return [];
        }

        $query = $this->pendingPurchaseOrderQuery($supplierId);
        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id');
        }
        if (Schema::hasTable('stores')) {
            $query->leftJoin('stores as st', 'st.id', '=', 'po.store_id');
        }

        $columns = [
            'po.id', 'po.invoice_no', 'po.ref_no', 'po.status', 'po.transaction_date',
            'po.invoice_date', 'po.final_total', 'po.location_id', 'po.store_id',
        ];
        $columns[] = Schema::hasTable('business_locations')
            ? 'bl.name as location_name'
            : DB::raw("'' as location_name");
        $columns[] = Schema::hasTable('stores')
            ? 'st.name as store_name'
            : DB::raw("'' as store_name");

        return $query
            ->orderByDesc('po.transaction_date')
            ->orderByDesc('po.id')
            ->get($columns)
            ->map(function ($row): array {
                $number = trim((string) ($row->invoice_no ?? '')) ?: ('PO #' . (int) $row->id);
                $details = array_values(array_filter([
                    trim((string) ($row->ref_no ?? '')) !== '' ? 'Ref: ' . trim((string) $row->ref_no) : null,
                    ! empty($row->invoice_date) ? 'Expected: ' . substr((string) $row->invoice_date, 0, 10) : null,
                    trim((string) ($row->location_name ?? '')) ?: null,
                    trim((string) ($row->store_name ?? '')) ?: null,
                ]));

                return [
                    'id' => (int) $row->id,
                    'invoice_no' => $number,
                    'text' => $number . ($details ? ' — ' . implode(' · ', $details) : ''),
                    'ref_no' => $row->ref_no,
                    'status' => (string) $row->status,
                    'transaction_date' => $row->transaction_date,
                    'invoice_date' => $row->invoice_date,
                    'final_total' => (float) ($row->final_total ?? 0),
                    'location_id' => (int) ($row->location_id ?? 0),
                    'location_name' => (string) ($row->location_name ?? ''),
                    'store_id' => (int) ($row->store_id ?? 0),
                    'store_name' => (string) ($row->store_name ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function purchaseOrder(int $id, int $supplierId): ?array
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('purchase_lines')) {
            return null;
        }

        $query = $this->pendingPurchaseOrderQuery($supplierId)->where('po.id', $id);
        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id');
        }
        if (Schema::hasTable('stores')) {
            $query->leftJoin('stores as st', 'st.id', '=', 'po.store_id');
        }

        $columns = [
            'po.id', 'po.invoice_no', 'po.ref_no', 'po.status', 'po.transaction_date',
            'po.invoice_date', 'po.final_total', 'po.total_before_tax', 'po.tax_id',
            'po.tax_amount', 'po.discount_type', 'po.discount_amount', 'po.shipping_details',
            'po.shipping_charges', 'po.price_adjustment', 'po.additional_notes',
            'po.exchange_rate', 'po.pay_term_number', 'po.pay_term_type', 'po.location_id',
            'po.store_id',
        ];
        $columns[] = Schema::hasColumn('transactions', 'is_vat')
            ? 'po.is_vat'
            : DB::raw('0 as is_vat');
        $columns[] = Schema::hasTable('business_locations')
            ? 'bl.name as location_name'
            : DB::raw("'' as location_name");
        $columns[] = Schema::hasTable('stores')
            ? 'st.name as store_name'
            : DB::raw("'' as store_name");

        $order = $query->first($columns);
        if (! $order) {
            return null;
        }

        $locationId = (int) ($order->location_id ?? 0);
        $storeId = (int) ($order->store_id ?? 0);
        $exchangeRate = max(0.000001, (float) ($order->exchange_rate ?: 1));

        $lineColumns = array_merge($this->productColumns(), [
            $this->purchaseLineColumn('quantity', 'order_quantity', '0'),
            $this->purchaseLineColumn('bonus_qty', 'order_free_qty', '0'),
            $this->purchaseLineColumn('pp_without_discount', 'order_base_cost', '0'),
            $this->purchaseLineColumn('discount_percent', 'order_discount_percent', '0'),
            $this->purchaseLineColumn('purchase_price', 'order_purchase_price', '0'),
            $this->purchaseLineColumn('purchase_price_inc_tax', 'order_purchase_price_inc_tax', '0'),
            $this->purchaseLineColumn('tax_id', 'order_tax_id', 'NULL'),
            $this->purchaseLineColumn('sub_unit_id', 'order_sub_unit_id', 'NULL'),
            $this->purchaseLineColumn('lot_number', 'order_lot_number', "''"),
            $this->purchaseLineColumn('mfg_date', 'order_mfg_date', 'NULL'),
            $this->purchaseLineColumn('exp_date', 'order_exp_date', 'NULL'),
        ]);

        $lineQuery = $this->productQuery()
            ->join('purchase_lines as pl', 'pl.variation_id', '=', 'v.id')
            ->where('pl.transaction_id', $id)
            ->where('p.business_id', $this->numbers->businessId());
        if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
            $lineQuery->whereNull('pl.deleted_at');
        }

        $lines = $lineQuery
            ->orderBy('pl.id')
            ->get($lineColumns)
            ->map(function ($row) use ($locationId, $storeId, $exchangeRate): array {
                $product = $this->formatProduct($row, $locationId, $storeId > 0 ? $storeId : null);
                $selectedUnitId = (int) ($row->order_sub_unit_id ?: $product['unit_id']);
                $selectedUnit = collect($product['units'])->firstWhere('id', $selectedUnitId);
                $multiplier = max(0.000001, (float) ($selectedUnit['multiplier'] ?? 1));

                $product['selected_unit_id'] = $selectedUnitId;
                $product['unit_multiplier'] = $multiplier;
                $product['quantity'] = (float) ($row->order_quantity ?? 0) / $multiplier;
                $product['free_qty'] = (float) ($row->order_free_qty ?? 0) / $multiplier;
                $product['purchase_price'] = ((float) ($row->order_base_cost ?? 0) * $multiplier) / $exchangeRate;
                $product['purchase_price_inc_tax'] = ((float) ($row->order_purchase_price_inc_tax ?? 0) * $multiplier) / $exchangeRate;
                $product['discount_type'] = 'percentage';
                $product['discount_value'] = (float) ($row->order_discount_percent ?? 0);
                $product['tax_id'] = ! empty($row->order_tax_id) ? (int) $row->order_tax_id : null;
                $product['lot_number'] = (string) ($row->order_lot_number ?? '');
                $product['mfg_date'] = $row->order_mfg_date ? substr((string) $row->order_mfg_date, 0, 10) : '';
                $product['exp_date'] = $row->order_exp_date ? substr((string) $row->order_exp_date, 0, 10) : '';

                return $product;
            })
            ->values()
            ->all();

        $subtotal = max(0, (float) ($order->total_before_tax ?? 0));
        $storedDiscount = max(0, (float) ($order->discount_amount ?? 0));
        $discountType = (string) ($order->discount_type ?: 'fixed');
        $discountValue = $discountType === 'percentage'
            ? ($subtotal > 0 ? ($storedDiscount / $subtotal) * 100 : 0)
            : $storedDiscount / $exchangeRate;

        return [
            'id' => (int) $order->id,
            'invoice_no' => (string) $order->invoice_no,
            'ref_no' => $order->ref_no,
            'status' => (string) $order->status,
            'transaction_date' => $order->transaction_date,
            'invoice_date' => $order->invoice_date,
            'final_total' => (float) ($order->final_total ?? 0),
            'location_id' => $locationId,
            'location_name' => (string) ($order->location_name ?? ''),
            'store_id' => $storeId,
            'store_name' => (string) ($order->store_name ?? ''),
            'pay_term_number' => $order->pay_term_number,
            'pay_term_type' => $order->pay_term_type,
            'exchange_rate' => $exchangeRate,
            'discount_type' => $discountType,
            'discount_amount' => round($discountValue, 6),
            'tax_id' => $order->tax_id ? (int) $order->tax_id : null,
            'shipping_details' => (string) ($order->shipping_details ?? ''),
            'shipping_charges' => (float) ($order->shipping_charges ?? 0) / $exchangeRate,
            'price_adjustment' => (float) ($order->price_adjustment ?? 0) / $exchangeRate,
            'additional_notes' => (string) ($order->additional_notes ?? ''),
            'is_vat' => (bool) ($order->is_vat ?? false),
            'lines' => $lines,
        ];
    }

    protected function pendingPurchaseOrderQuery(int $supplierId): Builder
    {
        $query = DB::table('transactions as po')
            ->where('po.business_id', $this->numbers->businessId())
            ->where('po.contact_id', $supplierId)
            ->where('po.type', 'purchase_order')
            ->whereIn('po.status', ['ordered', 'pending']);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('po.deleted_at');
        }

        // Older converted records may still have an order status. Exclude any
        // purchase already carrying the same supplier/order number as well.
        if (Schema::hasColumn('transactions', 'order_no')) {
            $query->whereNotExists(function ($converted): void {
                $converted->selectRaw('1')
                    ->from('transactions as received_purchase')
                    ->whereColumn('received_purchase.business_id', 'po.business_id')
                    ->whereColumn('received_purchase.contact_id', 'po.contact_id')
                    ->whereColumn('received_purchase.order_no', 'po.invoice_no')
                    ->where('received_purchase.type', 'purchase');
                if (Schema::hasColumn('transactions', 'deleted_at')) {
                    $converted->whereNull('received_purchase.deleted_at');
                }
            });
        }

        return $query;
    }

    protected function purchaseLineColumn(string $column, string $alias, string $default): mixed
    {
        return Schema::hasColumn('purchase_lines', $column)
            ? "pl.$column as $alias"
            : DB::raw("$default as $alias");
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createSupplier(array $data): array
    {
        if (! Schema::hasTable('contacts')) {
            throw new \RuntimeException('The contacts table is not available in this tenant database.');
        }

        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        $contactCode = 'SUP-' . $businessId . '-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(4));

        $payload = $this->schema->filter('contacts', [
            'business_id' => $businessId,
            'type' => 'supplier',
            'register_module' => 'purchase',
            'supplier_business_name' => trim((string) ($data['supplier_business_name'] ?? '')) ?: null,
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'contact_id' => $contactCode,
            'tax_number' => trim((string) ($data['tax_number'] ?? '')) ?: null,
            'mobile' => trim((string) ($data['mobile'] ?? '')) ?: null,
            'pay_term_number' => isset($data['pay_term_number']) && $data['pay_term_number'] !== '' ? (int) $data['pay_term_number'] : null,
            'pay_term_type' => $data['pay_term_type'] ?? null,
            'created_by' => $userId,
            'active' => 1,
            'contact_status' => 'active',
            'notification_contacts' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) DB::table('contacts')->insertGetId($payload);

        return $this->supplier($id) ?? ['id' => $id, 'name' => $payload['name'] ?? 'Supplier'];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createProduct(array $data, int $locationId, ?int $storeId): array
    {
        foreach (['products', 'product_variations', 'variations', 'units'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required product table [$table] is missing in this tenant database.");
            }
        }

        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        $unitId = (int) ($data['unit_id'] ?? 0);
        $unitQuery = DB::table('units')->where('business_id', $businessId)->where('id', $unitId);
        if (Schema::hasColumn('units', 'deleted_at')) {
            $unitQuery->whereNull('deleted_at');
        }
        if (! $unitQuery->exists()) {
            throw new \InvalidArgumentException('The selected product unit is not valid for this business.');
        }

        $taxId = ! empty($data['tax_id']) ? (int) $data['tax_id'] : null;
        $taxRate = 0.0;
        if ($taxId && Schema::hasTable('tax_rates')) {
            $taxQuery = DB::table('tax_rates')->where('business_id', $businessId)->where('id', $taxId);
            if (Schema::hasColumn('tax_rates', 'deleted_at')) {
                $taxQuery->whereNull('deleted_at');
            }
            $taxRate = (float) ($taxQuery->value('amount') ?? 0);
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku === '') {
            $sku = 'PNE-' . $businessId . '-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(4));
        }
        if (DB::table('products')->where('business_id', $businessId)->where('sku', $sku)->exists()) {
            throw new \InvalidArgumentException('The entered SKU is already used by another product.');
        }

        $enteredPurchasePrice = max(0, $this->numbers->number($data['purchase_price'] ?? 0));
        $taxType = (string) ($data['tax_type'] ?? 'exclusive');
        if ($taxType === 'inclusive' && $taxRate > 0) {
            $purchaseIncTax = $enteredPurchasePrice;
            $purchasePrice = $enteredPurchasePrice / (1 + ($taxRate / 100));
        } else {
            $purchasePrice = $enteredPurchasePrice;
            $purchaseIncTax = $purchasePrice + ($purchasePrice * $taxRate / 100);
        }
        $sellingPrice = max(0, $this->numbers->number($data['selling_price'] ?? 0));
        $profitPercent = $purchaseIncTax > 0 ? (($sellingPrice / $purchaseIncTax) - 1) * 100 : 0;

        $variationId = DB::transaction(function () use (
            $data, $businessId, $userId, $unitId, $taxId, $sku, $purchasePrice,
            $purchaseIncTax, $sellingPrice, $profitPercent, $taxType
        ): int {
            $productId = (int) DB::table('products')->insertGetId($this->schema->filter('products', [
                'name' => trim((string) $data['name']),
                'business_id' => $businessId,
                'type' => 'single',
                'unit_id' => $unitId,
                'tax' => $taxId,
                'tax_type' => $taxType,
                'enable_stock' => ! empty($data['enable_stock']) ? 1 : 0,
                'sku' => $sku,
                'barcode_type' => 'C128',
                'created_by' => $userId,
                'is_inactive' => 0,
                'not_for_selling' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $productVariationId = (int) DB::table('product_variations')->insertGetId($this->schema->filter('product_variations', [
                'name' => 'DUMMY',
                'product_id' => $productId,
                'is_dummy' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            return (int) DB::table('variations')->insertGetId($this->schema->filter('variations', [
                'name' => 'DUMMY',
                'product_id' => $productId,
                'sub_sku' => $sku,
                'product_variation_id' => $productVariationId,
                'default_purchase_price' => $purchasePrice,
                'dpp_inc_tax' => $purchaseIncTax,
                'profit_percent' => round($profitPercent, 6),
                'default_sell_price' => $sellingPrice,
                'sell_price_inc_tax' => $sellingPrice,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }, 3);

        return $this->product($variationId, $locationId, $storeId)
            ?? throw new \RuntimeException('The product was created but could not be loaded.');
    }

    /** @return array<int, array<string, mixed>> */
    public function stores(int $locationId): array
    {
        return $this->form->stores($this->numbers->businessId(), $locationId)
            ->map(fn ($row): array => ['id' => (int) $row->id, 'text' => (string) $row->name])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function products(string $term, int $locationId, ?int $storeId): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('variations')) {
            return [];
        }

        $term = trim($term);
        $query = $this->productQuery()
            ->where('p.business_id', $this->numbers->businessId());

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('p.name', 'like', $like)
                    ->orWhere('p.sku', 'like', $like)
                    ->orWhere('v.sub_sku', 'like', $like)
                    ->orWhere('v.name', 'like', $like)
                    ->orWhere('pv.name', 'like', $like);
            });
        }

        return $query
            ->orderBy('p.name')
            ->orderBy('pv.name')
            ->orderBy('v.name')
            ->limit(60)
            ->get($this->productColumns())
            ->map(fn ($row): array => $this->formatProduct($row, $locationId, $storeId))
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function product(int $variationId, int $locationId, ?int $storeId): ?array
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('variations')) {
            return null;
        }

        $row = $this->productQuery()
            ->where('p.business_id', $this->numbers->businessId())
            ->where('v.id', $variationId)
            ->first($this->productColumns());

        return $row ? $this->formatProduct($row, $locationId, $storeId) : null;
    }

    public function referenceExists(int $supplierId, string $reference, ?int $excludeId = null): bool
    {
        if ($reference === '' || ! Schema::hasTable('transactions')) {
            return false;
        }

        $query = DB::table('transactions')
            ->where('business_id', $this->numbers->businessId())
            ->where('type', 'purchase')
            ->where('contact_id', $supplierId)
            ->where('ref_no', $reference);
        if ($excludeId && $excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    protected function productQuery(): Builder
    {
        $query = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id');

        if (Schema::hasTable('tax_rates')) {
            $query->leftJoin('tax_rates as tr', 'tr.id', '=', 'p.tax');
        }
        /*
         * IS2317: Purchase product lookup must remain compatible with both the
         * legacy product status fields and Products New.  Older tenant data can
         * have stale/inconsistent `is_inactive` values even though the standard
         * operational `not_for_selling` flag says the product is selectable.
         *
         * Use ONE canonical availability flag in priority order instead of
         * combining several historical flags and accidentally hiding a valid
         * product.  This mirrors the long-standing purchase-selector behaviour:
         * `not_for_selling` is authoritative when present; older schemas fall
         * back to `is_inactive`; very new schemas without either flag may use
         * `products_new_status`.
         */
        if (Schema::hasColumn('products', 'not_for_selling')) {
            $query->where(function ($active): void {
                $active->whereNull('p.not_for_selling')
                    ->orWhere('p.not_for_selling', 0);
            });
        } elseif (Schema::hasColumn('products', 'is_inactive')) {
            $query->where(function ($active): void {
                $active->whereNull('p.is_inactive')
                    ->orWhere('p.is_inactive', 0);
            });
        }

        if (Schema::hasColumn('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }
        if (Schema::hasColumn('variations', 'deleted_at')) {
            $query->whereNull('v.deleted_at');
        }

        return $query;
    }

    /** @return array<int, mixed> */
    protected function productColumns(): array
    {
        $columns = [
            'p.id as product_id', 'p.name as product_name', 'p.sku', 'p.type as product_type',
            'p.unit_id', 'v.id as variation_id', 'v.product_variation_id', 'v.name as variation_name',
            'v.sub_sku', 'v.default_purchase_price', 'v.dpp_inc_tax', 'v.profit_percent',
            'v.sell_price_inc_tax', 'pv.name as variation_group', 'u.short_name as unit_name',
            'u.allow_decimal',
        ];

        $columns[] = Schema::hasColumn('products', 'enable_stock')
            ? 'p.enable_stock'
            : DB::raw('1 as enable_stock');
        $columns[] = Schema::hasColumn('products', 'sub_unit_ids')
            ? 'p.sub_unit_ids'
            : DB::raw("'' as sub_unit_ids");
        $columns[] = Schema::hasColumn('products', 'tax')
            ? 'p.tax as tax_id'
            : DB::raw('NULL as tax_id');
        $columns[] = Schema::hasColumn('products', 'tax_type')
            ? 'p.tax_type'
            : DB::raw("'exclusive' as tax_type");
        $columns[] = Schema::hasColumn('products', 'expiry_period')
            ? 'p.expiry_period'
            : DB::raw('NULL as expiry_period');
        $columns[] = Schema::hasColumn('products', 'expiry_period_type')
            ? 'p.expiry_period_type'
            : DB::raw('NULL as expiry_period_type');

        if (Schema::hasTable('tax_rates')) {
            $columns[] = 'tr.name as tax_name';
            $columns[] = 'tr.amount as tax_rate';
        } else {
            $columns[] = DB::raw("'' as tax_name");
            $columns[] = DB::raw('0 as tax_rate');
        }

        return $columns;
    }

    /** @return array<string, mixed> */
    protected function formatProduct(object $row, int $locationId, ?int $storeId): array
    {
        /*
         * Current Stock must match Products New > Stock Centre exactly.
         *
         * Stock Centre's "Available" column is the location-level value from
         * variation_location_details.qty_available.  Add Purchase previously
         * switched to variation_store_details whenever a Store was selected
         * (Store is mandatory on this form), so the figure shown here was a
         * store balance while Stock Centre showed the complete location
         * balance.  That made both screens disagree even though both values
         * could be internally valid for their different scopes.
         *
         * Keep Store for the purchase destination itself, but do not use it to
         * change the Current Stock display.  This is intentionally implemented
         * inside Purchase and does not create a runtime dependency on the
         * ProductsNew module.
         */
        $stock = 0.0;
        if ($locationId > 0 && Schema::hasTable('variation_location_details')) {
            $stockQuery = DB::table('variation_location_details')
                ->where('variation_id', $row->variation_id)
                ->where('location_id', $locationId);

            // Extra business guard when the standard locations table is present.
            if (Schema::hasTable('business_locations')
                && Schema::hasColumn('business_locations', 'business_id')) {
                $locationBelongsToBusiness = DB::table('business_locations')
                    ->where('id', $locationId)
                    ->where('business_id', $this->numbers->businessId())
                    ->exists();

                if ($locationBelongsToBusiness) {
                    $stock = (float) ($stockQuery->value('qty_available') ?? 0);
                }
            } else {
                $stock = (float) ($stockQuery->value('qty_available') ?? 0);
            }
        }

        $variationLabel = trim(implode(' — ', array_filter([
            $row->product_type === 'variable' ? $row->variation_group : null,
            $row->product_type === 'variable' ? $row->variation_name : null,
        ])));
        $label = $row->product_name . ($variationLabel !== '' ? ' (' . $variationLabel . ')' : '');
        $sku = (string) ($row->sub_sku ?: $row->sku);

        return [
            'id' => (int) $row->variation_id,
            'text' => $label . ($sku !== '' ? ' — ' . $sku : ''),
            'product_id' => (int) $row->product_id,
            'product_name' => (string) $row->product_name,
            'variation_id' => (int) $row->variation_id,
            'product_variation_id' => (int) $row->product_variation_id,
            'variation_name' => $variationLabel,
            'sku' => $sku,
            'unit_id' => (int) $row->unit_id,
            'unit_name' => (string) ($row->unit_name ?: 'Unit'),
            'allow_decimal' => (bool) $row->allow_decimal,
            'units' => $this->unitOptions((int) $row->product_id, (int) $row->unit_id, (string) ($row->sub_unit_ids ?? '')),
            'enable_stock' => (bool) $row->enable_stock,
            'current_stock' => $stock,
            'purchase_price' => (float) ($row->default_purchase_price ?: 0),
            'purchase_price_inc_tax' => (float) ($row->dpp_inc_tax ?: $row->default_purchase_price ?: 0),
            'selling_price' => (float) ($row->sell_price_inc_tax ?: 0),
            'profit_percent' => (float) ($row->profit_percent ?: 0),
            'tax_id' => $row->tax_id ? (int) $row->tax_id : null,
            'tax_name' => (string) ($row->tax_name ?: ''),
            'tax_rate' => (float) ($row->tax_rate ?: 0),
            'tax_type' => (string) ($row->tax_type ?? 'exclusive'),
            'expiry_period' => $row->expiry_period,
            'expiry_period_type' => $row->expiry_period_type,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function unitOptions(int $productId, int $baseUnitId, string $rawSubUnitIds): array
    {
        if (isset($this->subUnitCache[$productId])) {
            return $this->subUnitCache[$productId];
        }
        if (! Schema::hasTable('units')) {
            return $this->subUnitCache[$productId] = [];
        }

        $ids = $this->parseIds($rawSubUnitIds);
        $ids[] = $baseUnitId;
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return $this->subUnitCache[$productId] = [];
        }

        $rows = DB::table('units')
            ->where('business_id', $this->numbers->businessId())
            ->whereIn('id', $ids)
            ->when(Schema::hasColumn('units', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'actual_name', 'short_name', 'allow_decimal', 'base_unit_id', 'base_unit_multiplier']);

        $options = $rows->map(function ($unit) use ($baseUnitId): array {
            $multiplier = (int) $unit->id === $baseUnitId ? 1.0 : max(0.000001, (float) ($unit->base_unit_multiplier ?: 1));

            return [
                'id' => (int) $unit->id,
                'name' => (string) ($unit->short_name ?: $unit->actual_name),
                'multiplier' => $multiplier,
                'allow_decimal' => (bool) $unit->allow_decimal,
                'is_base' => (int) $unit->id === $baseUnitId,
            ];
        })->sortByDesc('is_base')->values()->all();

        return $this->subUnitCache[$productId] = $options;
    }

    /** @return array<int, int> */
    protected function parseIds(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        if (str_starts_with($raw, 'a:')) {
            try {
                $unserialized = @unserialize($raw, ['allowed_classes' => false]);
                if (is_array($unserialized)) {
                    return array_values(array_filter(array_map('intval', $unserialized)));
                }
            } catch (\Throwable) {
                // Fall through to comma-separated parsing.
            }
        }

        return array_values(array_filter(array_map('intval', preg_split('/[^0-9]+/', $raw) ?: [])));
    }

    protected function supplierOutstanding(int $supplierId): float
    {
        if (! Schema::hasTable('transactions')) {
            return 0.0;
        }

        $businessId = $this->numbers->businessId();
        $purchaseQuery = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $supplierId)
            ->where('type', 'purchase')
            ->whereNotIn('status', ['cancelled', 'draft']);
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $purchaseQuery->whereNull('deleted_at');
        }
        $purchases = (float) $purchaseQuery->sum('final_total');

        $returns = 0.0;
        $returnQuery = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $supplierId)
            ->where('type', 'purchase_return');
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $returnQuery->whereNull('deleted_at');
        }
        $returns = (float) $returnQuery->sum('final_total');

        $payments = 0.0;
        if (Schema::hasTable('transaction_payments')) {
            $paymentQuery = DB::table('transaction_payments as tp')
                ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
                ->where('t.business_id', $businessId)
                ->where('t.contact_id', $supplierId)
                ->where('t.type', 'purchase');
            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $paymentQuery->whereNull('tp.deleted_at');
            }
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $paymentQuery->whereNull('t.deleted_at');
            }
            $payments = (float) $paymentQuery->sum('tp.amount');
        }

        return round($purchases - $returns - $payments, 6);
    }
}
