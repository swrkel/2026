<?php

namespace Modules\ProductsNew\Services\Identity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Throwable;

/**
 * Optional, best-effort synchronisation into a central UID registry.
 *
 * This service is OFF unless an explicit dedicated central connection name is
 * configured. It never auto-discovers/reuses the tenant-repointed `mysql`
 * connection because that could write registry data into a tenant database.
 * Registry failures are logged and never roll back normal tenant product work.
 */
class ProductUidRegistryService
{
    public function __construct(protected ProductUidService $uids) {}

    public function syncProductBestEffort(ProductsNewProduct $product): void
    {
        $connectionName = trim((string) config('productsnew.uid.registry_connection', ''));
        if ($connectionName === '') {
            return;
        }

        // This ERP repoints its default connection to the active tenant. Refuse
        // to use that connection name as the central registry target even when
        // explicitly configured. A dedicated central connection alias is
        // required, which prevents accidental writes to the tenant database.
        if ($connectionName === DB::getDefaultConnection()) {
            Log::warning('ProductsNew UID registry sync disabled: registry connection must be a dedicated non-default central connection.', [
                'connection' => $connectionName,
            ]);
            return;
        }

        $productUid = $this->productUidValue($product);
        if ($productUid === null) {
            return;
        }

        try {
            $connection = DB::connection($connectionName);
            $schema = $connection->getSchemaBuilder();
            $productTable = (string) config('productsnew.uid.registry_product_table', 'products_new_uid_registry');
            $variationTable = (string) config('productsnew.uid.registry_variation_table', 'products_new_variation_uid_registry');
            $capabilityTable = (string) config('productsnew.uid.registry_capability_table', 'products_new_uid_tenant_capabilities');

            if (! $schema->hasTable($productTable)) {
                return;
            }

            $tenantKey = $this->tenantKey();
            $businessId = (int) ($product->business_id ?? 0);
            $localProductId = (int) $product->id;
            $databaseName = DB::connection()->getDatabaseName();

            if ($schema->hasTable($capabilityTable)) {
                $this->syncCapability($connectionName, $capabilityTable, $tenantKey, $databaseName);
            }

            // Never overwrite another tenant/business mapping even if a corrupt
            // database somehow reuses a UID. Log and leave both source systems
            // untouched for manual review.
            $byUid = $connection->table($productTable)->where('product_uid', $productUid)->first();
            if ($byUid) {
                if (
                    (string) $byUid->tenant_key !== $tenantKey
                    || (int) $byUid->business_id !== $businessId
                    || (int) $byUid->tenant_product_id !== $localProductId
                ) {
                    Log::error('ProductsNew UID registry conflict: UID already belongs to another product identity.', [
                        'product_uid' => $productUid,
                        'tenant_key' => $tenantKey,
                        'business_id' => $businessId,
                        'tenant_product_id' => $localProductId,
                    ]);
                    return;
                }

                $connection->table($productTable)
                    ->where('product_uid', $productUid)
                    ->update($this->productRegistryPayload($product, $tenantKey, $databaseName, false));
            } else {
                $byLocalIdentity = $connection->table($productTable)
                    ->where('tenant_key', $tenantKey)
                    ->where('business_id', $businessId)
                    ->where('tenant_product_id', $localProductId)
                    ->first();

                if ($byLocalIdentity && (string) $byLocalIdentity->product_uid !== $productUid) {
                    Log::error('ProductsNew UID registry conflict: local product already has a different central UID.', [
                        'existing_product_uid' => (string) $byLocalIdentity->product_uid,
                        'incoming_product_uid' => $productUid,
                        'tenant_key' => $tenantKey,
                        'business_id' => $businessId,
                        'tenant_product_id' => $localProductId,
                    ]);
                    return;
                }

                $connection->table($productTable)->insert(
                    array_merge(
                        ['product_uid' => $productUid],
                        $this->productRegistryPayload($product, $tenantKey, $databaseName, true)
                    )
                );
            }

            if ($schema->hasTable($variationTable) && $this->uids->variationUidSupported()) {
                $this->syncVariations($connectionName, $variationTable, $product, $tenantKey, $databaseName, $productUid);
            }
        } catch (Throwable $exception) {
            Log::warning('ProductsNew central UID registry sync skipped after an error; tenant product save remains committed.', [
                'product_id' => (int) $product->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function syncVariations(
        string $connectionName,
        string $table,
        ProductsNewProduct $product,
        string $tenantKey,
        string $databaseName,
        string $productUid
    ): void {
        $connection = DB::connection($connectionName);

        $rows = DB::table('variations')
            ->where('product_id', (int) $product->id)
            ->whereNotNull(ProductUidService::VARIATION_UID_COLUMN)
            ->where(ProductUidService::VARIATION_UID_COLUMN, '<>', '')
            ->get(['id', ProductUidService::VARIATION_UID_COLUMN, 'sub_sku']);

        foreach ($rows as $row) {
            $variationUid = trim((string) $row->{ProductUidService::VARIATION_UID_COLUMN});
            if ($variationUid === '') {
                continue;
            }

            $existing = $connection->table($table)->where('variation_uid', $variationUid)->first();
            $payload = [
                'product_uid' => $productUid,
                'tenant_key' => $tenantKey,
                'business_id' => (int) ($product->business_id ?? 0),
                'tenant_product_id' => (int) $product->id,
                'tenant_variation_id' => (int) $row->id,
                'source_database' => $databaseName,
                'variation_sku_snapshot' => mb_substr((string) ($row->sub_sku ?? ''), 0, 191),
                'source_schema_version' => ProductUidService::FEATURE_VERSION,
                'status' => 'active',
                'last_seen_at' => now(),
                'updated_at' => now(),
            ];

            if ($existing) {
                if (
                    (string) $existing->tenant_key !== $tenantKey
                    || (int) $existing->business_id !== (int) ($product->business_id ?? 0)
                    || (int) $existing->tenant_variation_id !== (int) $row->id
                ) {
                    Log::error('ProductsNew variation UID registry conflict detected.', [
                        'variation_uid' => $variationUid,
                        'tenant_key' => $tenantKey,
                        'tenant_variation_id' => (int) $row->id,
                    ]);
                    continue;
                }

                $connection->table($table)->where('variation_uid', $variationUid)->update($payload);
                continue;
            }

            $local = $connection->table($table)
                ->where('tenant_key', $tenantKey)
                ->where('business_id', (int) ($product->business_id ?? 0))
                ->where('tenant_variation_id', (int) $row->id)
                ->first();

            if ($local && (string) $local->variation_uid !== $variationUid) {
                Log::error('ProductsNew variation registry local identity already has a different UID.', [
                    'existing_variation_uid' => (string) $local->variation_uid,
                    'incoming_variation_uid' => $variationUid,
                    'tenant_key' => $tenantKey,
                    'tenant_variation_id' => (int) $row->id,
                ]);
                continue;
            }

            $connection->table($table)->insert(array_merge(
                ['variation_uid' => $variationUid, 'created_at' => now()],
                $payload
            ));
        }
    }

    protected function syncCapability(
        string $connectionName,
        string $table,
        string $tenantKey,
        string $databaseName
    ): void {
        $productSupported = $this->uids->productUidSupported();
        $variationSupported = $this->uids->variationUidSupported();

        // `exists()` is intentional: capability sync must stay cheap even in
        // very large live tenant databases. We only need to know whether any
        // unbackfilled row remains, not count every one on each product save.
        $hasMissingProducts = $productSupported
            ? DB::table('products')->where(function ($query) {
                $query->whereNull(ProductUidService::PRODUCT_UID_COLUMN)
                    ->orWhere(ProductUidService::PRODUCT_UID_COLUMN, '');
            })->exists()
            : true;

        $hasMissingVariations = $variationSupported
            ? DB::table('variations')->where(function ($query) {
                $query->whereNull(ProductUidService::VARIATION_UID_COLUMN)
                    ->orWhere(ProductUidService::VARIATION_UID_COLUMN, '');
            })->exists()
            : true;

        $payload = [
            'feature_version' => ProductUidService::FEATURE_VERSION,
            'product_uid_supported' => $productSupported ? 1 : 0,
            'variation_uid_supported' => $variationSupported ? 1 : 0,
            'backfill_completed' => ($productSupported && $variationSupported && ! $hasMissingProducts && ! $hasMissingVariations) ? 1 : 0,
            'last_seen_at' => now(),
            'updated_at' => now(),
        ];

        $connection = DB::connection($connectionName);
        $existing = $connection->table($table)
            ->where('tenant_key', $tenantKey)
            ->where('source_database', $databaseName)
            ->first();

        if ($existing) {
            $connection->table($table)
                ->where('tenant_key', $tenantKey)
                ->where('source_database', $databaseName)
                ->update($payload);
            return;
        }

        $connection->table($table)->insert(array_merge([
            'tenant_key' => $tenantKey,
            'source_database' => $databaseName,
            'created_at' => now(),
        ], $payload));
    }

    protected function productRegistryPayload(
        ProductsNewProduct $product,
        string $tenantKey,
        string $databaseName,
        bool $includeCreatedAt
    ): array {
        $payload = [
            'tenant_key' => $tenantKey,
            'business_id' => (int) ($product->business_id ?? 0),
            'tenant_product_id' => (int) $product->id,
            'source_database' => $databaseName,
            'product_name_snapshot' => mb_substr((string) ($product->name ?? ''), 0, 191),
            'sku_snapshot' => mb_substr((string) ($product->sku ?? ''), 0, 191),
            'barcode_snapshot' => mb_substr((string) ($product->barcode ?? ''), 0, 191),
            'source_schema_version' => ProductUidService::FEATURE_VERSION,
            'status' => 'active',
            'last_seen_at' => now(),
            'updated_at' => now(),
        ];

        if ($includeCreatedAt) {
            $payload['created_at'] = now();
        }

        return $payload;
    }

    protected function productUidValue(ProductsNewProduct $product): ?string
    {
        if (! $this->uids->productUidSupported()) {
            return null;
        }

        $uid = trim((string) ($product->{ProductUidService::PRODUCT_UID_COLUMN} ?? ''));
        return $uid !== '' ? $uid : null;
    }

    protected function tenantKey(): string
    {
        $configured = trim((string) config('productsnew.uid.tenant_key', ''));
        if ($configured !== '') {
            return $configured;
        }

        try {
            if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
                $tenant = tenancy()->tenant;
                if (method_exists($tenant, 'getTenantKey')) {
                    $key = trim((string) $tenant->getTenantKey());
                    if ($key !== '') {
                        return $key;
                    }
                }
                if (isset($tenant->id) && trim((string) $tenant->id) !== '') {
                    return trim((string) $tenant->id);
                }
            }
        } catch (Throwable $exception) {
            // Fallback below is intentionally deterministic for central-host
            // businesses and older tenancy implementations.
        }

        return 'db:' . DB::connection()->getDatabaseName();
    }
}
