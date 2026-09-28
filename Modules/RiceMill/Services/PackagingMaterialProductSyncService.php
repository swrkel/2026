<?php
namespace Modules\RiceMill\Services;

use Modules\RiceMill\Models\{PackagingMaterial,Setting};

/**
 * Keeps Products New selections mirrored as Rice Mill Packaging Materials.
 *
 * The selected Products New product id is stored in Rice Mill Settings JSON,
 * together with the internal rcm_packaging_materials id created for stock and
 * usage tracking. No host Product model dependency is introduced.
 */
class PackagingMaterialProductSyncService
{
    public function __construct(private ExternalMasterDataService $masters) {}

    /**
     * Return only Packaging Material Product selections that are explicitly
     * saved in Settings > Product Category Mapping > Packaging Material.
     *
     * @return array<int,array<string,mixed>>
     */
    public function savedSelections(int $businessId): array
    {
        $setting = Setting::forBusiness($businessId)->select(['settings'])->first();
        $storedSettings = (array) optional($setting)->settings;

        return array_values(array_filter(
            (array) ($storedSettings['packaging_material_product_selections'] ?? []),
            static fn ($row) => is_array($row) && (int) ($row['product_id'] ?? 0) > 0
        ));
    }

    /**
     * Resolve saved Product selections to their internal Rice Mill Packaging
     * Material ids and persist missing material_id values back to Settings.
     * Existing stock/status/note values are preserved by syncSelections().
     *
     * @return array<int,array<string,mixed>>
     */
    public function syncSavedSelections(int $businessId, ?int $userId = null): array
    {
        $setting = Setting::forBusiness($businessId)->first();
        $storedSettings = (array) optional($setting)->settings;
        $selections = array_values(array_filter(
            (array) ($storedSettings['packaging_material_product_selections'] ?? []),
            static fn ($row) => is_array($row) && (int) ($row['product_id'] ?? 0) > 0
        ));

        if (! $selections) {
            return [];
        }

        $resolved = $this->syncSelections($businessId, $selections, $userId);
        if ($setting && $resolved !== $selections) {
            $storedSettings['packaging_material_product_selections'] = $resolved;
            $setting->settings = $storedSettings;
            $setting->updated_by = $userId;
            $setting->save();
        }

        return $resolved;
    }

    /**
     * @param array<int,array<string,mixed>> $selections
     * @return array<int,int>
     */
    public function materialIds(array $selections): array
    {
        $ids = [];
        foreach ($selections as $selection) {
            if (! is_array($selection)) {
                continue;
            }
            $id = (int) ($selection['material_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<string,mixed> $selection
     */
    public function syncSelection(int $businessId, array $selection, ?int $userId = null): ?PackagingMaterial
    {
        $productId = (int) ($selection['product_id'] ?? 0);
        if ($productId <= 0) {
            return null;
        }

        $product = $this->masters->productsNewProduct($businessId, $productId);
        if (! $product) {
            return null;
        }

        // Respect the existing Rice Mill Packaging Material field lengths even
        // when Products New allows a longer Product name/SKU/unit.
        $productCode = trim((string) ($product['code'] ?? ''));
        $productName = trim((string) ($product['name'] ?? ''));
        $productUnit = trim((string) ($product['unit'] ?? '')) ?: 'pcs';
        $materialCode = $productCode !== '' ? mb_substr($productCode, 0, 40) : null;
        $materialName = mb_substr($productName, 0, 150);
        $materialUnit = mb_substr($productUnit, 0, 30);

        $materialId = (int) ($selection['material_id'] ?? 0);
        $material = $materialId > 0
            ? PackagingMaterial::forBusiness($businessId)->whereKey($materialId)->first()
            : null;

        if (! $material) {
            // Older selections may not yet have material_id in Settings. Match
            // safely by truncated SKU/code + name first and then exact name.
            if ($materialCode !== null) {
                $material = PackagingMaterial::forBusiness($businessId)
                    ->where('code', $materialCode)
                    ->where('name', $materialName)
                    ->first();
            }
            if (! $material) {
                $material = PackagingMaterial::forBusiness($businessId)
                    ->where('name', $materialName)
                    ->first();
            }
        }

        $attributes = [
            'code' => $materialCode,
            'name' => $materialName,
            'unit' => $materialUnit,
        ];

        if ($material) {
            // Keep current_qty, active and user-maintained note untouched.
            $material->fill($attributes)->save();
            return $material;
        }

        return PackagingMaterial::create($attributes + [
            'business_id' => $businessId,
            'current_qty' => 0,
            'active' => 1,
            'note' => 'Auto-linked from Products New packaging material selection.',
            'created_by' => $userId,
        ]);
    }

    /**
     * @param array<int,array<string,mixed>> $selections
     * @return array<int,array<string,mixed>> selections with resolved material_id
     */
    public function syncSelections(int $businessId, array $selections, ?int $userId = null): array
    {
        $resolved = [];
        foreach ($selections as $selection) {
            if (! is_array($selection)) {
                continue;
            }
            $material = $this->syncSelection($businessId, $selection, $userId);
            if ($material) {
                $selection['material_id'] = (int) $material->id;
            }
            $resolved[] = $selection;
        }
        return $resolved;
    }
}
