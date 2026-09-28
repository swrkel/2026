<?php

namespace Modules\ProductsNew\Services\Settings;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductSettingWriteService
{
    public function businessId(): int
    {
        return (int) (session('business.id')
            ?? request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? 0);
    }

    public function createCategory(array $data): int
    {
        $this->assertCategoryStorageAvailable();

        return DB::transaction(function () use ($data): int {
            $payload = $this->categoryPayload($data, true);
            $categoryId = (int) DB::table('categories')->insertGetId($payload);
            $this->syncCategoryProfile($categoryId, $data);

            return $categoryId;
        });
    }

    public function updateCategory(int $categoryId, array $data): void
    {
        $this->assertCategoryStorageAvailable();

        DB::transaction(function () use ($categoryId, $data): void {
            $this->categoryForBusiness($categoryId);
            $payload = $this->categoryPayload($data, false);
            DB::table('categories')->where('id', $categoryId)->update($payload);
            $this->syncCategoryProfile($categoryId, $data);
        });
    }

    public function deleteCategory(int $categoryId): void
    {
        DB::transaction(function () use ($categoryId): void {
            $category = $this->categoryForBusiness($categoryId);

            if (Schema::hasColumn('categories', 'parent_id')
                && DB::table('categories')->where('parent_id', $categoryId)
                    ->when(Schema::hasColumn('categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                    ->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'This category has subcategories and cannot be deleted.',
                ]);
            }

            if (Schema::hasTable('products')) {
                $used = DB::table('products')->where(function ($where) use ($categoryId): void {
                    if (Schema::hasColumn('products', 'category_id')) {
                        $where->where('category_id', $categoryId);
                    }
                    if (Schema::hasColumn('products', 'sub_category_id')) {
                        $method = Schema::hasColumn('products', 'category_id') ? 'orWhere' : 'where';
                        $where->{$method}('sub_category_id', $categoryId);
                    }
                });

                if (Schema::hasColumn('products', 'business_id')) {
                    $used->where('business_id', $this->businessId());
                }
                if (Schema::hasColumn('products', 'deleted_at')) {
                    $used->whereNull('deleted_at');
                }
                if ($used->exists()) {
                    throw ValidationException::withMessages([
                        'category' => 'This category is used by products and cannot be deleted.',
                    ]);
                }
            }

            if (Schema::hasColumn('categories', 'deleted_at')) {
                DB::table('categories')->where('id', $category->id)->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('categories')->where('id', $category->id)->delete();
            }

            if (Schema::hasTable('products_new_category_profiles')) {
                DB::table('products_new_category_profiles')->where('category_id', $category->id)->delete();
            }
        });
    }

    public function createBrand(array $data): int
    {
        return DB::table('brands')->insertGetId([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'business_id' => $this->businessId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createUnit(array $data): int
    {
        return DB::table('units')->insertGetId([
            'actual_name' => trim($data['actual_name']),
            'short_name' => trim($data['short_name']),
            'allow_decimal' => !empty($data['allow_decimal']) ? 1 : 0,
            'business_id' => $this->businessId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createVariation(array $data): int
    {
        return DB::table('variation_templates')->insertGetId([
            'name' => trim($data['name']),
            'business_id' => $this->businessId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function categoryPayload(array $data, bool $creating): array
    {
        $this->validateParentCategory($data);

        $columns = Schema::getColumnListing('categories');
        $isSubCategory = !empty($data['add_as_sub_category']);

        // Top-level categories must use NULL, not 0. Some tenant schemas enforce a
        // self-referencing foreign key on parent_id, where 0 is invalid.
        $parentId = $isSubCategory ? (int) ($data['parent_id'] ?? 0) : $this->topLevelParentValue();

        $payload = [
            'name' => trim((string) $data['name']),
            'short_code' => $this->nullIfBlank($data['short_code'] ?? null),
            'parent_id' => $parentId,
            'category_type' => 'product',
            'description' => null,
            'add_related_account' => $this->nullIfBlank($data['add_related_account'] ?? null),
            'cogs_account_id' => $this->integerOrNull($data['cogs_account_id'] ?? null),
            'sales_income_account_id' => $this->integerOrNull($data['sales_income_account_id'] ?? null),
            'weight_excess_loss_applicable' => !empty($data['weight_excess_loss_applicable']) ? 1 : 0,
            'vat_exempted' => (string) ($data['vat_exempted'] ?? 'No'),
            'vat_based_on' => (string) ($data['vat_based_on'] ?? 'sale_price'),
            'apply_vat_on' => (string) ($data['apply_vat_on'] ?? 'on_product_sub_category_settings'),
            'vat_not_applicable' => 0,
            'category_code_is_hsn' => !empty($data['category_code_is_hsn']) ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($creating) {
            $payload['business_id'] = $this->businessId();
            $payload['created_by'] = (int) (auth()->id() ?? 0);
            $payload['created_at'] = now();
        }

        return Arr::only($payload, $columns);
    }

    private function topLevelParentValue()
    {
        if (!Schema::hasColumn('categories', 'parent_id')) {
            return null;
        }

        try {
            $database = DB::connection()->getDatabaseName();
            $column = DB::table('information_schema.COLUMNS')
                ->select(['IS_NULLABLE', 'COLUMN_DEFAULT'])
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'categories')
                ->where('COLUMN_NAME', 'parent_id')
                ->first();

            if ($column && strtoupper((string) $column->IS_NULLABLE) === 'NO') {
                return $column->COLUMN_DEFAULT === null ? 0 : $column->COLUMN_DEFAULT;
            }
        } catch (\Throwable $exception) {
            // The normal Products/UltimatePOS categories schema allows NULL.
        }

        return null;
    }

    private function validateParentCategory(array $data): void
    {
        if (empty($data['add_as_sub_category'])) {
            return;
        }

        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($parentId <= 0) {
            throw ValidationException::withMessages(['parent_id' => 'Please select a parent category.']);
        }

        $query = DB::table('categories')->where('id', $parentId);
        if (Schema::hasColumn('categories', 'business_id')) {
            $query->where('business_id', $this->businessId());
        }
        if (Schema::hasColumn('categories', 'parent_id')) {
            $query->whereRaw('COALESCE(parent_id, 0) = 0');
        }
        if (Schema::hasColumn('categories', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (!$query->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'The selected parent category is invalid.']);
        }
    }

    private function categoryForBusiness(int $categoryId)
    {
        $query = DB::table('categories')->where('id', $categoryId);
        if (Schema::hasColumn('categories', 'business_id')) {
            $query->where('business_id', $this->businessId());
        }
        if (Schema::hasColumn('categories', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $category = $query->first();
        if (!$category) {
            abort(404);
        }

        return $category;
    }

    private function syncCategoryProfile(int $categoryId, array $data): void
    {
        if (!Schema::hasTable('products_new_category_profiles')) {
            return;
        }

        $columns = Schema::getColumnListing('products_new_category_profiles');
        if (!in_array('category_id', $columns, true)) {
            return;
        }

        $identity = ['category_id' => $categoryId];
        $values = Arr::only([
            'business_id' => $this->businessId(),
            'category_code_is_hsn' => !empty($data['category_code_is_hsn']) ? 1 : 0,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ], $columns);

        $existing = DB::table('products_new_category_profiles')
            ->where('category_id', $categoryId)
            ->exists();

        if ($existing) {
            if ($values !== []) {
                DB::table('products_new_category_profiles')
                    ->where('category_id', $categoryId)
                    ->update($values);
            }
            return;
        }

        $insert = array_merge($identity, $values, Arr::only([
            'created_by' => auth()->id(),
            'created_at' => now(),
        ], $columns));

        DB::table('products_new_category_profiles')->insert($insert);
    }

    private function assertCategoryStorageAvailable(): void
    {
        if (!Schema::hasTable('categories')) {
            throw ValidationException::withMessages([
                'category' => 'The categories table is not available in the active tenant database.',
            ]);
        }

        if ($this->businessId() <= 0 && Schema::hasColumn('categories', 'business_id')) {
            throw ValidationException::withMessages([
                'category' => 'The active business could not be resolved. Please sign out and sign in again.',
            ]);
        }
    }

    private function nullIfBlank($value)
    {
        return trim((string) $value) === '' ? null : trim((string) $value);
    }

    private function integerOrNull($value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
