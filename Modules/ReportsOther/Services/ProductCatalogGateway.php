<?php

namespace Modules\ReportsOther\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductCatalogGateway
{
    /**
     * Return the active tenant/business Product Category tree without importing
     * any Products / ProductsNew classes. ProductsNew is checked first because
     * it is the current product master in this ERP; legacy categories remains a
     * safe fallback for tenants that have not moved to ProductsNew yet.
     */
    public function tree(int $businessId): Collection
    {
        $cfg = (array) config('reportsother.catalog', []);

        foreach ($this->candidateTables($cfg) as $table) {
            $rows = $this->rowsForTable($table, $cfg, $businessId);
            if ($rows->isEmpty()) {
                continue;
            }

            return $this->buildTree($rows);
        }

        return collect();
    }

    public function selectedItems(int $businessId, array $categoryIds, array $subCategoryIds): Collection
    {
        $wanted = collect([
            ...array_map(fn ($v) => ['type' => 'category', 'id' => (int) $v], $categoryIds),
            ...array_map(fn ($v) => ['type' => 'sub_category', 'id' => (int) $v], $subCategoryIds),
        ])->filter(fn ($x) => $x['id'] > 0);

        if ($wanted->isEmpty()) {
            return collect();
        }

        $tree = $this->tree($businessId);
        $allowed = collect();

        foreach ($tree as $parent) {
            $allowed->push([
                'type' => 'category',
                'id' => (int) $parent['id'],
                'name' => (string) $parent['name'],
            ]);

            foreach ($parent['children'] as $child) {
                $allowed->push([
                    'type' => 'sub_category',
                    'id' => (int) $child->id,
                    'name' => (string) $child->name,
                ]);
            }
        }

        return $wanted->map(function ($wantedItem) use ($allowed) {
            return $allowed->first(fn ($item) =>
                $item['type'] === $wantedItem['type'] && $item['id'] === $wantedItem['id']
            );
        })->filter()->unique(fn ($item) => $item['type'].':'.$item['id'])->values();
    }

    private function candidateTables(array $cfg): array
    {
        $candidates = $cfg['table_candidates'] ?? ['products_new_categories', 'categories'];
        if (is_string($candidates)) {
            $candidates = explode(',', $candidates);
        }

        // Keep the current ProductsNew master first even when an older published
        // reportsother config still contains table=categories from v1/v2/v3.
        $tables = ['products_new_categories'];
        foreach ((array) $candidates as $candidate) {
            $tables[] = trim((string) $candidate);
        }
        $tables[] = trim((string) ($cfg['table'] ?? ''));
        $tables[] = 'categories';

        return collect($tables)
            ->filter(fn ($table) => $table !== '' && $this->identifier($table))
            ->unique()
            ->values()
            ->all();
    }

    private function rowsForTable(string $table, array $cfg, int $businessId): Collection
    {
        if (!Schema::hasTable($table)) {
            return collect();
        }

        $id = (string) ($cfg['id_column'] ?? 'id');
        $name = (string) ($cfg['name_column'] ?? 'name');
        $parent = (string) ($cfg['parent_column'] ?? 'parent_id');

        foreach ([$id, $name, $parent] as $column) {
            if (!$this->identifier($column) || !Schema::hasColumn($table, $column)) {
                return collect();
            }
        }

        $makeQuery = function () use ($table, $id, $name, $parent, $cfg, $businessId) {
            $query = DB::table($table)->select([
                "$id as id",
                "$name as name",
                "$parent as parent_id",
            ]);

            $businessColumn = trim((string) ($cfg['business_column'] ?? 'business_id'));
            if ($businessColumn !== '' && $this->identifier($businessColumn) && Schema::hasColumn($table, $businessColumn)) {
                $query->where($businessColumn, $businessId);
            }

            $activeColumn = trim((string) ($cfg['active_column'] ?? 'is_active'));
            if ($activeColumn !== '' && $this->identifier($activeColumn) && Schema::hasColumn($table, $activeColumn)) {
                $query->where($activeColumn, 1);
            }

            return $query;
        };

        $query = $makeQuery();
        $typeColumn = trim((string) ($cfg['type_column'] ?? 'category_type'));
        $typeValue = trim((string) ($cfg['type_value'] ?? 'product'));
        $canFilterType = $typeColumn !== '' && $typeValue !== ''
            && $this->identifier($typeColumn) && Schema::hasColumn($table, $typeColumn);

        if ($canFilterType) {
            $query->where($typeColumn, $typeValue);
        }

        $rows = $query->orderBy($name)->get();

        // Some older tenants use a different category_type value. If the table
        // exists but the product filter returns nothing, show the business's
        // categories rather than an empty mapping dropdown.
        if ($rows->isEmpty() && $canFilterType) {
            $rows = $makeQuery()->orderBy($name)->get();
        }

        return $rows->map(function ($row) {
            $row->id = (int) $row->id;
            $row->name = trim((string) $row->name);
            $row->parent_id = is_numeric($row->parent_id) ? (int) $row->parent_id : 0;
            return $row;
        })->filter(fn ($row) => $row->id > 0 && $row->name !== '')->values();
    }

    private function buildTree(Collection $rows): Collection
    {
        $byId = $rows->keyBy('id');
        $children = $rows->filter(fn ($row) => $row->parent_id > 0)->groupBy('parent_id');

        // Parent-less rows and orphaned rows are roots. The latter prevents an
        // otherwise valid category from disappearing because an old parent was deleted.
        $roots = $rows->filter(fn ($row) => $row->parent_id <= 0 || !$byId->has($row->parent_id));
        if ($roots->isEmpty()) {
            $roots = $rows;
        }

        return $roots->map(function ($row) use ($children) {
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'children' => $this->flattenChildren((int) $row->id, $children, 1, []),
            ];
        })->unique('id')->values();
    }

    private function flattenChildren(int $parentId, Collection $children, int $depth, array $visited): Collection
    {
        if (in_array($parentId, $visited, true) || $depth > 10) {
            return collect();
        }
        $visited[] = $parentId;

        $result = collect();
        foreach ($children->get($parentId, collect()) as $child) {
            $child->depth = $depth;
            $result->push($child);
            $result = $result->concat($this->flattenChildren((int) $child->id, $children, $depth + 1, $visited));
        }
        return $result->values();
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $value);
    }
}
