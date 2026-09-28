<?php

namespace Modules\ProductsNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

/**
 * Central duplicate-product protection for Products New.
 *
 * This guard is intentionally enforced in the service layer so manual Add,
 * Edit and CSV Import all share the same rules and cannot be bypassed by a
 * different controller/form.
 */
class ProductIdentityGuard
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}

    public function assertAllowed(array $data, ?int $ignoreProductId = null): void
    {
        $sku = $this->nullable($data['sku'] ?? null);
        $barcode = $this->nullable($data['barcode'] ?? null);
        $name = trim((string) ($data['name'] ?? $data['product_name'] ?? ''));
        $type = strtolower(trim((string) ($data['type'] ?? 'single'))) ?: 'single';

        if ($sku !== null && ($match = $this->findSkuConflict($sku, $ignoreProductId))) {
            throw ValidationException::withMessages([
                'sku' => $this->conflictMessage('SKU', $match),
            ]);
        }

        if ($barcode !== null && ($match = $this->findBarcodeConflict($barcode, $ignoreProductId))) {
            throw ValidationException::withMessages([
                'barcode' => $this->conflictMessage('Barcode', $match),
            ]);
        }

        // A Single product is one logical product master per business.  This is
        // the important protection for blank-SKU/manual creates where the system
        // would otherwise generate a fresh SKU and accidentally create a second
        // master for the same item.
        if ($type === 'single' && $name !== '' && ($match = $this->findSingleNameConflict($name, $ignoreProductId))) {
            throw ValidationException::withMessages([
                'name' => 'This Single product already exists as "' . (string) $match->name . '"'
                    . (! empty($match->sku) ? ' (SKU: ' . (string) $match->sku . ')' : '')
                    . '. Please edit the existing product instead of creating another product master.',
            ]);
        }
    }

    public function findSkuConflict(string $sku, ?int $ignoreProductId = null): ?object
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'sku')) {
            return null;
        }

        return $this->baseQuery($ignoreProductId)
            ->whereRaw('LOWER(TRIM(`sku`)) = ?', [mb_strtolower(trim($sku))])
            ->first($this->conflictColumns());
    }

    public function findBarcodeConflict(string $barcode, ?int $ignoreProductId = null): ?object
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'barcode')) {
            return null;
        }

        return $this->baseQuery($ignoreProductId)
            ->whereNotNull('barcode')
            ->whereRaw('LOWER(TRIM(`barcode`)) = ?', [mb_strtolower(trim($barcode))])
            ->first($this->conflictColumns());
    }

    public function findSingleNameConflict(string $name, ?int $ignoreProductId = null): ?object
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'name')) {
            return null;
        }

        $query = $this->baseQuery($ignoreProductId);

        if (Schema::hasColumn('products', 'type')) {
            $query->whereRaw("LOWER(COALESCE(`type`, 'single')) = 'single'");
        }

        // Ignore whitespace/case only; punctuation is retained so genuinely
        // different product names such as A/B and AB are not falsely blocked.
        $normalised = $this->normaliseNameKey($name);

        return $query
            ->whereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(`name`), ' ', ''), CHAR(9), ''), CHAR(10), ''), CHAR(13), '')) = ?",
                [$normalised]
            )
            ->first($this->conflictColumns());
    }

    public function normaliseNameKey(string $name): string
    {
        $name = mb_strtolower(trim($name));
        return preg_replace('/\s+/u', '', $name) ?? $name;
    }

    public function normaliseLookupKey($value): ?string
    {
        $value = $this->nullable($value);
        return $value === null ? null : mb_strtolower($value);
    }

    protected function baseQuery(?int $ignoreProductId = null)
    {
        $query = DB::table('products');

        if (Schema::hasColumn('products', 'business_id')) {
            $this->guard->applyBusiness($query, 'products.business_id');
        }
        if (Schema::hasColumn('products', 'deleted_at')) {
            $query->whereNull('products.deleted_at');
        }
        if ($ignoreProductId !== null) {
            $query->where('products.id', '<>', $ignoreProductId);
        }

        return $query;
    }

    protected function conflictColumns(): array
    {
        $columns = ['id', 'name'];
        if (Schema::hasColumn('products', 'sku')) {
            $columns[] = 'sku';
        }
        if (Schema::hasColumn('products', 'barcode')) {
            $columns[] = 'barcode';
        }
        return $columns;
    }

    protected function conflictMessage(string $field, object $match): string
    {
        return $field . ' already belongs to "' . (string) ($match->name ?? ('Product #' . $match->id)) . '"'
            . (! empty($match->sku) ? ' (SKU: ' . (string) $match->sku . ')' : '')
            . '. Please use the existing product master.';
    }

    protected function nullable($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
