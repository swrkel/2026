<?php

namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewDuplicateReview;
use Modules\ProductsNew\Entities\ProductsNewProduct;

class ProductDuplicateService
{
    public function scan(?int $businessId = null): int
    {
        $businessId = $businessId ?: session('business.id');
        $columns = $this->productColumns();

        if (! in_array('id', $columns, true)
            || ! in_array('name', $columns, true)
            || ($businessId && ! in_array('business_id', $columns, true))) {
            return 0;
        }

        $matchFields = array_values(array_intersect(
            ['sku', 'barcode', 'supplier_code', 'manufacturer_code'],
            $columns
        ));

        $select = ['id', 'name'];
        foreach (['business_id', 'sku', 'barcode', 'supplier_code', 'manufacturer_code'] as $column) {
            if (in_array($column, $columns, true)) {
                $select[] = $column;
            }
        }

        $query = ProductsNewProduct::query();
        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $products = $query->limit(1000)->get($select);
        $created = 0;

        foreach ($products as $product) {
            foreach ($products as $candidate) {
                if ($product->id >= $candidate->id) {
                    continue;
                }

                $score = 0;
                $fields = [];

                foreach ($matchFields as $field) {
                    if (! empty($product->{$field}) && $product->{$field} === $candidate->{$field}) {
                        $score += 35;
                        $fields[] = $field;
                    }
                }

                similar_text(
                    strtolower((string) $product->name),
                    strtolower((string) $candidate->name),
                    $percent
                );

                if ($percent >= 85) {
                    $score += min(30, $percent / 3);
                    $fields[] = 'name_similarity';
                }

                if ($score >= 35) {
                    ProductsNewDuplicateReview::firstOrCreate([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'duplicate_product_id' => $candidate->id,
                    ], [
                        'score' => $score,
                        'matched_fields' => $fields,
                        'status' => 'open',
                        'created_by' => auth()->id(),
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }

    public function list(array $filters = [])
    {
        return DB::table('products_new_duplicate_reviews as d')
            ->leftJoin('products as p', 'p.id', '=', 'd.product_id')
            ->leftJoin('products as dp', 'dp.id', '=', 'd.duplicate_product_id')
            ->select('d.*', 'p.name as product_name', 'p.sku as product_sku', 'dp.name as duplicate_name', 'dp.sku as duplicate_sku')
            ->when($filters['business_id'] ?? session('business.id'), fn ($q, $id) => $q->where('d.business_id', $id))
            ->when($filters['status'] ?? 'open', fn ($q, $status) => $q->where('d.status', $status))
            ->orderByDesc('d.score')
            ->paginate($filters['per_page'] ?? 25);
    }

    public function resolve(int $id, string $status): void
    {
        ProductsNewDuplicateReview::whereKey($id)->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    private function productColumns(): array
    {
        return Schema::hasTable('products')
            ? Schema::getColumnListing('products')
            : [];
    }
}
