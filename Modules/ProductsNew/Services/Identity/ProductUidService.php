<?php

namespace Modules\ProductsNew\Services\Identity;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Business Product UID foundation.
 *
 * Important invariants:
 * - Existing local numeric IDs remain authoritative for all legacy relationships.
 * - A UID belongs to exactly one local product row (therefore one business).
 * - Locations/stores do NOT receive new product UIDs; they continue to reference
 *   the same local product/variation through existing stock tables.
 * - Different businesses/tenants never share or auto-merge a UID, even when
 *   product names, SKU values or barcodes are identical.
 * - Every method is schema-aware so older tenant databases without UID columns
 *   continue to operate without SQL errors.
 */
class ProductUidService
{
    public const PRODUCT_UID_COLUMN = 'product_uid';
    public const VARIATION_UID_COLUMN = 'variation_uid';
    public const FEATURE_VERSION = 'business_product_uid_v1';

    public function productUidSupported(): bool
    {
        return Schema::hasTable('products')
            && Schema::hasColumn('products', self::PRODUCT_UID_COLUMN);
    }

    public function variationUidSupported(): bool
    {
        return Schema::hasTable('variations')
            && Schema::hasColumn('variations', self::VARIATION_UID_COLUMN);
    }

    /**
     * Add a new globally unique UID to an INSERT payload when the tenant schema
     * supports it. Older tenants receive the payload untouched.
     */
    public function prepareNewProductData(array $data): array
    {
        // Never trust an externally supplied UID. A new local product always
        // receives a fresh identity generated inside the ERP. This prevents an
        // import/API/form from copying another business product's UID.
        unset($data[self::PRODUCT_UID_COLUMN]);

        if ($this->productUidSupported()) {
            $data[self::PRODUCT_UID_COLUMN] = $this->newUid();
        }

        return $data;
    }

    /**
     * Ensure one existing local product has a UID. This is intentionally a
     * lazy, non-destructive backfill: it does not compare names/SKUs/barcodes
     * and therefore cannot merge products.
     */
    public function ensureProductUid(int $productId, ?int $expectedBusinessId = null): ?string
    {
        if (! $this->productUidSupported()) {
            return null;
        }

        return DB::transaction(function () use ($productId, $expectedBusinessId): ?string {
            $query = DB::table('products')->where('id', $productId)->lockForUpdate();

            if ($expectedBusinessId !== null && Schema::hasColumn('products', 'business_id')) {
                $query->where('business_id', $expectedBusinessId);
            }

            $product = $query->first(['id', self::PRODUCT_UID_COLUMN]);
            if (! $product) {
                return null;
            }

            $existing = trim((string) ($product->{self::PRODUCT_UID_COLUMN} ?? ''));
            if ($existing !== '') {
                return $existing;
            }

            return $this->writeUniqueUid('products', $productId, self::PRODUCT_UID_COLUMN);
        });
    }

    /**
     * Ensure one existing variation has a UID. Variations remain children of
     * their existing local product; no product or variation matching is done.
     */
    public function ensureVariationUid(int $variationId, ?int $expectedProductId = null): ?string
    {
        if (! $this->variationUidSupported()) {
            return null;
        }

        return DB::transaction(function () use ($variationId, $expectedProductId): ?string {
            $query = DB::table('variations')->where('id', $variationId)->lockForUpdate();

            if ($expectedProductId !== null && Schema::hasColumn('variations', 'product_id')) {
                $query->where('product_id', $expectedProductId);
            }

            $variation = $query->first(['id', self::VARIATION_UID_COLUMN]);
            if (! $variation) {
                return null;
            }

            $existing = trim((string) ($variation->{self::VARIATION_UID_COLUMN} ?? ''));
            if ($existing !== '') {
                return $existing;
            }

            return $this->writeUniqueUid('variations', $variationId, self::VARIATION_UID_COLUMN);
        });
    }

    /**
     * Generate a RFC 4122 UUID. UUIDs are globally unique and do not encode
     * tenant/business/location information, which avoids leaking tenancy data.
     */
    public function newUid(): string
    {
        return strtolower((string) Str::uuid());
    }

    public function capabilitySnapshot(): array
    {
        return [
            'feature_version' => self::FEATURE_VERSION,
            'product_uid_supported' => $this->productUidSupported(),
            'variation_uid_supported' => $this->variationUidSupported(),
            'database' => DB::connection()->getDatabaseName(),
        ];
    }

    protected function writeUniqueUid(string $table, int $id, string $column): ?string
    {
        // A collision is extraordinarily unlikely, but retrying makes the code
        // safe even when a database UNIQUE index is present.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $uid = $this->newUid();

            try {
                $updated = DB::table($table)
                    ->where('id', $id)
                    ->where(function ($query) use ($column) {
                        $query->whereNull($column)->orWhere($column, '');
                    })
                    ->update([
                        $column => $uid,
                        'updated_at' => now(),
                    ]);

                if ($updated > 0) {
                    return $uid;
                }

                $existing = trim((string) DB::table($table)->where('id', $id)->value($column));
                return $existing !== '' ? $existing : null;
            } catch (QueryException $exception) {
                // Retry only likely uniqueness collisions. Any other persistent
                // database failure will be re-thrown on the final attempt.
                if ($attempt === 4) {
                    throw $exception;
                }
            }
        }

        return null;
    }
}
