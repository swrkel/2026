<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntrySupplierLookupService
{
    public const DEFAULT_PAGE_SIZE = 50;

    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema
    ) {
    }

    /**
     * Return a small, paginated supplier result set suitable for an instant
     * autocomplete. Empty-term lookups are ordered by the newest contact id so
     * MySQL can stop as soon as one page is found instead of sorting every
     * supplier in the tenant database.
     *
     * @return array{results: array<int, array<string, mixed>>, pagination: array{page:int, per_page:int, more:bool}}
     */
    public function search(string $term = '', int $page = 1, int $perPage = self::DEFAULT_PAGE_SIZE): array
    {
        if (! $this->schema->tableExists('contacts')) {
            return $this->emptyPage($page, $perPage);
        }

        $columns = $this->schema->columns('contacts');
        if (! in_array('id', $columns, true)) {
            return $this->emptyPage($page, $perPage);
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $term = trim((string) preg_replace('/\s+/', ' ', $term));

        $query = $this->baseQuery($columns);
        $searchableColumns = array_values(array_intersect(
            ['name', 'supplier_business_name', 'contact_id', 'mobile', 'email'],
            $columns
        ));

        if ($term !== '' && $searchableColumns !== []) {
            $this->applyPrefixSearch($query, $searchableColumns, $term);
        }

        $select = array_values(array_intersect([
            'id', 'name', 'supplier_business_name', 'contact_id', 'mobile', 'email',
            'pay_term_number', 'pay_term_type',
        ], $columns));

        $rows = $query
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get($select);

        // A contains fallback is used only when the indexed prefix lookup found
        // nothing. This preserves searches such as "Fuel" for "Keerthi Fuel"
        // without forcing every keystroke to scan the complete contacts table.
        if ($term !== '' && $page === 1 && $rows->isEmpty() && $searchableColumns !== []) {
            $fallback = $this->baseQuery($columns);
            $this->applyContainsSearch($fallback, $searchableColumns, $term);
            $rows = $fallback
                ->orderByDesc('id')
                ->limit($perPage + 1)
                ->get($select);
        }

        $more = $rows->count() > $perPage;
        $rows = $rows->take($perPage);

        return [
            'results' => $rows->map(fn ($row): array => $this->mapRow($row))->values()->all(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'more' => $more,
            ],
        ];
    }

    /** @param array<int, string> $columns */
    protected function baseQuery(array $columns): Builder
    {
        $query = DB::table('contacts');

        if (in_array('business_id', $columns, true)) {
            $query->where('business_id', $this->numbers->businessId());
        }

        if (in_array('type', $columns, true)) {
            $query->whereIn('type', ['supplier', 'both']);
        }

        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        if (in_array('active', $columns, true)) {
            $query->where(function (Builder $builder): void {
                $builder->where('active', 1)->orWhereNull('active');
            });
        }

        if (in_array('contact_status', $columns, true)) {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('contact_status')
                    ->orWhere('contact_status', '')
                    ->orWhereRaw('LOWER(TRIM(contact_status)) = ?', ['active']);
            });
        }

        return $query;
    }

    /** @param array<int, string> $columns */
    protected function applyPrefixSearch(Builder $query, array $columns, string $term): void
    {
        $like = $this->escapeLike($term) . '%';

        $query->where(function (Builder $builder) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'like', $like);
            }
        });
    }

    /** @param array<int, string> $columns */
    protected function applyContainsSearch(Builder $query, array $columns, string $term): void
    {
        $like = '%' . $this->escapeLike($term) . '%';

        $query->where(function (Builder $builder) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'like', $like);
            }
        });
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** @return array<string, mixed> */
    protected function mapRow(object $row): array
    {
        $name = trim((string) (($row->supplier_business_name ?? null) ?: ($row->name ?? '')));
        $extras = array_values(array_filter([
            trim((string) ($row->contact_id ?? '')),
            trim((string) ($row->mobile ?? '')),
        ], fn (string $value): bool => $value !== ''));

        return [
            'id' => (int) $row->id,
            'text' => $name . ($extras !== [] ? ' — ' . implode(' / ', $extras) : ''),
            'name' => $name,
            'contact_id' => $row->contact_id ?? null,
            'pay_term_number' => $row->pay_term_number ?? null,
            'pay_term_type' => $row->pay_term_type ?? null,
            'mobile' => $row->mobile ?? null,
            'email' => $row->email ?? null,
        ];
    }

    /** @return array{results: array<int, mixed>, pagination: array{page:int, per_page:int, more:bool}} */
    protected function emptyPage(int $page, int $perPage): array
    {
        return [
            'results' => [],
            'pagination' => [
                'page' => max(1, $page),
                'per_page' => max(10, min(100, $perPage)),
                'more' => false,
            ],
        ];
    }
}
