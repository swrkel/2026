<?php

namespace Modules\ProductsNew\Services;

use DomainException;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductStatusService
{
    public function __construct(protected ProductsNewTenantGuard $guard)
    {
    }

    public function isInactive(object $product): bool
    {
        return (bool) ($product->not_for_selling ?? false)
            || (bool) ($product->is_inactive ?? false)
            || in_array(strtolower((string) ($product->products_new_status ?? '')), [
                'inactive', 'suspended', 'discontinued', 'archived',
            ], true);
    }

    /**
     * Apply the normal operational rule used by product selectors: only active
     * products are selectable. Both status columns are respected because the
     * application contains tenants created under different schema versions.
     */
    public function applyActiveOnly($query, string $tableAlias = 'products')
    {
        $columns = $this->productColumns();

        if (in_array('not_for_selling', $columns, true)) {
            $query->where(function ($active) use ($tableAlias): void {
                $active->whereNull($tableAlias . '.not_for_selling')
                    ->orWhere($tableAlias . '.not_for_selling', 0);
            });
        }

        if (in_array('is_inactive', $columns, true)) {
            $query->where(function ($active) use ($tableAlias): void {
                $active->whereNull($tableAlias . '.is_inactive')
                    ->orWhere($tableAlias . '.is_inactive', 0);
            });
        }

        if (in_array('products_new_status', $columns, true)) {
            $query->where(function ($active) use ($tableAlias): void {
                $active->whereNull($tableAlias . '.products_new_status')
                    ->orWhereNotIn($tableAlias . '.products_new_status', [
                        'inactive', 'suspended', 'discontinued', 'archived',
                    ]);
            });
        }

        return $query;
    }

    /**
     * Report selectors must normally hide inactive products, but an inactive
     * product remains selectable when it was active or had transactions during
     * the requested period. This preserves historical reporting without
     * reintroducing inactive products into current operational forms.
     */
    public function applyVisibleForDateRange(
        $query,
        Carbon $from,
        Carbon $to,
        string $tableAlias = 'products'
    ) {
        $columns = $this->productColumns();
        $hasStatusColumn = in_array('not_for_selling', $columns, true)
            || in_array('is_inactive', $columns, true)
            || in_array('products_new_status', $columns, true);

        if (! $hasStatusColumn) {
            return $query;
        }

        $businessId = $this->guard->businessId();

        $query->where(function ($visible) use ($from, $to, $tableAlias, $columns, $businessId): void {
            $visible->where(function ($active) use ($tableAlias, $columns): void {
                if (in_array('not_for_selling', $columns, true)) {
                    $active->where(function ($flag) use ($tableAlias): void {
                        $flag->whereNull($tableAlias . '.not_for_selling')
                            ->orWhere($tableAlias . '.not_for_selling', 0);
                    });
                }

                if (in_array('is_inactive', $columns, true)) {
                    $active->where(function ($flag) use ($tableAlias): void {
                        $flag->whereNull($tableAlias . '.is_inactive')
                            ->orWhere($tableAlias . '.is_inactive', 0);
                    });
                }

                if (in_array('products_new_status', $columns, true)) {
                    $active->where(function ($flag) use ($tableAlias): void {
                        $flag->whereNull($tableAlias . '.products_new_status')
                            ->orWhereNotIn($tableAlias . '.products_new_status', [
                                'inactive', 'suspended', 'discontinued', 'archived',
                            ]);
                    });
                }
            });

            $visible->orWhere(function ($historical) use ($from, $to, $tableAlias, $businessId): void {
                $hasEvidenceSource = false;

                if ($this->canUseStatusHistory()) {
                    $historical->whereExists(function (Builder $status) use ($from, $to, $tableAlias, $businessId): void {
                        $status->selectRaw('1')
                            ->from('products_new_status_transitions as pn_status')
                            ->whereColumn('pn_status.product_id', $tableAlias . '.id')
                            ->where('pn_status.business_id', $businessId)
                            ->where(function ($period) use ($from, $to): void {
                                // If deactivation happened on or after the report start,
                                // the product was active for at least part of the period.
                                $period->where(function ($inactive) use ($from): void {
                                    $inactive->whereIn('pn_status.to_status', [
                                        'inactive', 'suspended', 'discontinued', 'archived',
                                    ])->where('pn_status.created_at', '>=', $from);
                                })->orWhere(function ($active) use ($from, $to): void {
                                    // A reactivation during the selected period also makes
                                    // the product historically selectable.
                                    $active->where('pn_status.to_status', 'active')
                                        ->whereBetween('pn_status.created_at', [$from, $to]);
                                });
                            });
                    });
                    $hasEvidenceSource = true;
                }

                foreach ($this->historicalTransactionSubqueries($from, $to, $tableAlias, $businessId) as $subquery) {
                    if ($hasEvidenceSource) {
                        $historical->orWhereExists($subquery);
                    } else {
                        $historical->whereExists($subquery);
                        $hasEvidenceSource = true;
                    }
                }

                if (! $hasEvidenceSource) {
                    $historical->whereRaw('1 = 0');
                }
            });
        });

        return $query;
    }

    public function assertActiveProductId(int $productId): object
    {
        if (! Schema::hasTable('products')) {
            throw ValidationException::withMessages([
                'product_id' => 'The products table is not available.',
            ]);
        }

        $columns = $this->productColumns();
        $select = ['id'];
        foreach (['business_id', 'name', 'not_for_selling', 'is_inactive', 'products_new_status'] as $column) {
            if (in_array($column, $columns, true)) {
                $select[] = $column;
            }
        }

        $query = DB::table('products')->where('id', $productId);
        if (in_array('business_id', $columns, true)) {
            $query->where('business_id', $this->guard->businessId());
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        $product = $query->first(array_values(array_unique($select)));
        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is not available for this business.',
            ]);
        }

        if ($this->isInactive($product)) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is inactive. Activate it from Products New / List Products before using it in a current transaction.',
            ]);
        }

        return $product;
    }

    public function setActive(ProductsNewProduct $product, bool $active, ?string $note = null): void
    {
        $this->assertBusiness($product);
        $columns = $this->productColumns();
        $fromStatus = $this->isInactive($product) ? 'inactive' : 'active';
        $toStatus = $active ? 'active' : 'inactive';

        if ($fromStatus === $toStatus) {
            return;
        }

        DB::transaction(function () use ($product, $active, $note, $columns, $fromStatus, $toStatus): void {
            $updates = [];

            if (in_array('not_for_selling', $columns, true)) {
                $updates['not_for_selling'] = $active ? 0 : 1;
            }
            if (in_array('is_inactive', $columns, true)) {
                $updates['is_inactive'] = $active ? 0 : 1;
            }
            if (in_array('products_new_status', $columns, true)) {
                $updates['products_new_status'] = $toStatus;
            }

            if ($updates === []) {
                throw new DomainException(
                    'The product status cannot be changed because this tenant database has no supported active/inactive product column.'
                );
            }

            if (in_array('updated_at', $columns, true)) {
                $updates['updated_at'] = now();
            }

            $updateQuery = DB::table('products')->where('id', $product->id);
            if (in_array('business_id', $columns, true)) {
                $updateQuery->where('business_id', $this->guard->businessId());
            }

            if ($updateQuery->update($updates) < 1) {
                throw new DomainException('The product status was not changed. Please refresh the page and try again.');
            }

            $this->recordTransition((int) $product->id, $fromStatus, $toStatus, $note);
            $this->recordTimeline((int) $product->id, $toStatus, $note);
        });

        $product->refresh();
    }

    private function historicalTransactionSubqueries(
        Carbon $from,
        Carbon $to,
        string $tableAlias,
        int $businessId
    ): array {
        $subqueries = [];

        if ($this->canUseTransactionLine('purchase_lines')) {
            $dateColumn = $this->transactionDateColumn();
            $subqueries[] = function (Builder $q) use ($from, $to, $tableAlias, $businessId, $dateColumn): void {
                $q->selectRaw('1')
                    ->from('purchase_lines as pn_pl')
                    ->join('transactions as pn_pt', 'pn_pt.id', '=', 'pn_pl.transaction_id')
                    ->whereColumn('pn_pl.product_id', $tableAlias . '.id')
                    ->where('pn_pt.business_id', $businessId)
                    ->whereBetween('pn_pt.' . $dateColumn, [$from, $to]);
            };
        }

        if ($this->canUseTransactionLine('transaction_sell_lines')) {
            $dateColumn = $this->transactionDateColumn();
            $subqueries[] = function (Builder $q) use ($from, $to, $tableAlias, $businessId, $dateColumn): void {
                $q->selectRaw('1')
                    ->from('transaction_sell_lines as pn_sl')
                    ->join('transactions as pn_st', 'pn_st.id', '=', 'pn_sl.transaction_id')
                    ->whereColumn('pn_sl.product_id', $tableAlias . '.id')
                    ->where('pn_st.business_id', $businessId)
                    ->whereBetween('pn_st.' . $dateColumn, [$from, $to]);
            };
        }

        if (Schema::hasTable('products_new_inventory_movements')
            && Schema::hasColumn('products_new_inventory_movements', 'product_id')) {
            $movementDate = Schema::hasColumn('products_new_inventory_movements', 'movement_date')
                ? 'movement_date'
                : (Schema::hasColumn('products_new_inventory_movements', 'created_at') ? 'created_at' : null);

            if ($movementDate !== null) {
                $subqueries[] = function (Builder $q) use ($from, $to, $tableAlias, $businessId, $movementDate): void {
                    $q->selectRaw('1')
                        ->from('products_new_inventory_movements as pn_im')
                        ->whereColumn('pn_im.product_id', $tableAlias . '.id')
                        ->whereBetween('pn_im.' . $movementDate, [$from, $to]);

                    if (Schema::hasColumn('products_new_inventory_movements', 'business_id')) {
                        $q->where('pn_im.business_id', $businessId);
                    }
                };
            }
        }

        return $subqueries;
    }

    private function canUseTransactionLine(string $lineTable): bool
    {
        return Schema::hasTable($lineTable)
            && Schema::hasColumn($lineTable, 'product_id')
            && Schema::hasColumn($lineTable, 'transaction_id')
            && Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'id')
            && Schema::hasColumn('transactions', 'business_id')
            && $this->transactionDateColumn() !== null;
    }

    private function transactionDateColumn(): ?string
    {
        if (! Schema::hasTable('transactions')) {
            return null;
        }

        if (Schema::hasColumn('transactions', 'transaction_date')) {
            return 'transaction_date';
        }

        return Schema::hasColumn('transactions', 'created_at') ? 'created_at' : null;
    }

    private function canUseStatusHistory(): bool
    {
        foreach (['product_id', 'business_id', 'to_status', 'created_at'] as $column) {
            if (! Schema::hasTable('products_new_status_transitions')
                || ! Schema::hasColumn('products_new_status_transitions', $column)) {
                return false;
            }
        }

        return true;
    }

    private function recordTransition(int $productId, string $fromStatus, string $toStatus, ?string $note): void
    {
        if (! Schema::hasTable('products_new_status_transitions')) {
            return;
        }

        $payload = $this->schemaPayload('products_new_status_transitions', [
            'business_id' => $this->guard->businessId(),
            'product_id' => $productId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'metadata' => json_encode(['source' => 'products_new_list_action']),
            'created_by' => $this->guard->userId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($payload !== []) {
            DB::table('products_new_status_transitions')->insert($payload);
        }
    }

    private function recordTimeline(int $productId, string $status, ?string $note): void
    {
        if (! Schema::hasTable('products_new_timeline')) {
            return;
        }

        $payload = $this->schemaPayload('products_new_timeline', [
            'business_id' => $this->guard->businessId(),
            'product_id' => $productId,
            'event' => 'product_status_changed',
            'payload' => json_encode([
                'title' => $status === 'active' ? 'Product activated' : 'Product deactivated',
                'status' => $status,
                'note' => $note,
            ]),
            'created_by' => $this->guard->userId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($payload !== []) {
            DB::table('products_new_timeline')->insert($payload);
        }
    }

    private function assertBusiness(ProductsNewProduct $product): void
    {
        if (Schema::hasColumn('products', 'business_id')
            && (int) $product->business_id !== $this->guard->businessId()) {
            abort(404);
        }
    }

    private function productColumns(): array
    {
        return Schema::hasTable('products') ? Schema::getColumnListing('products') : [];
    }

    private function schemaPayload(string $table, array $payload): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_filter(
            $payload,
            static fn ($value, $column): bool => Schema::hasColumn($table, (string) $column),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
