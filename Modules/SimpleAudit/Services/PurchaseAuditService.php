<?php

namespace Modules\SimpleAudit\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SimpleAudit\Support\DateRange;
use RuntimeException;

class PurchaseAuditService
{
    protected $context;
    protected $columnCache = [];

    public function __construct(ContextService $context)
    {
        $this->context = $context;
    }

    public function build($connection, array $filters, $tenantId = null)
    {
        $businessId = (int) ($filters['business_id'] ?? 0);
        if (!$businessId) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.select_business_error'));
        }

        $business = $this->context->businessMeta($connection, $businessId);
        $range = DateRange::normalize($filters['from'] ?? null, $filters['to'] ?? null, $business['time_zone']);
        $filters = array_merge($filters, $range, [
            'business_id' => $businessId,
            'location_id' => !empty($filters['location_id']) ? (int) $filters['location_id'] : null,
            'store_id' => !empty($filters['store_id']) ? (int) $filters['store_id'] : null,
        ]);
        $this->validateScope($connection, $filters);

        $changes = $this->changeMaps($connection, $filters);
        $purchases = $this->purchaseSummary($connection, $filters, $business, $changes);
        $stock = $this->stockSummary($connection, $filters, $business, $purchases, $changes);
        $supplierPayments = $this->supplierPaymentSummary($connection, $filters, $changes);
        $accounts = $this->accountSummary($connection, $filters, $changes);
        $supplierLedgers = $this->supplierLedgerSummary($connection, $filters, $changes);

        $locationName = null;
        if ($filters['location_id']) {
            $locationName = DB::connection($connection)->table('business_locations')
                ->where('id', $filters['location_id'])->value('name');
        }
        $storeName = null;
        if ($filters['store_id'] && Schema::connection($connection)->hasTable('stores')) {
            $storeName = DB::connection($connection)->table('stores')
                ->where('id', $filters['store_id'])->value('name');
        }

        $trackingStartedAt = $this->trackingStartedAt($connection);
        $auditTrackingStartedAt = $this->auditTrackingStartedAt($connection);

        return [
            'meta' => [
                'tenant_id' => $tenantId ?: ($business['tenant_id'] ?? null),
                'business_id' => $businessId,
                'business_name' => $business['name'],
                'location_id' => $filters['location_id'],
                'location_name' => $locationName ?: __('simpleaudit::simpleaudit.all_locations'),
                'store_id' => $filters['store_id'],
                'store_name' => $storeName ?: __('simpleaudit::simpleaudit.all_stores'),
                'from' => $range['from'],
                'to' => $range['to'],
                'generated_at' => now($business['time_zone'])->format('Y-m-d H:i:s'),
                'stock_tracking_started_at' => $trackingStartedAt,
                'audit_tracking_started_at' => $auditTrackingStartedAt,
                'purchase_return_tracking_complete' => (bool) ($auditTrackingStartedAt && $range['start'] >= $auditTrackingStartedAt),
                'time_zone' => $business['time_zone'],
            ],
            'precision' => [
                'currency' => $business['currency_precision'],
                'quantity' => $business['quantity_precision'],
            ],
            'sections' => [
                'purchases' => $purchases,
                'stock_movements' => $stock,
                'supplier_payments' => $supplierPayments,
                'accounts' => $accounts,
                'supplier_ledgers' => $supplierLedgers,
            ],
            'audit_changes' => [
                'total' => $changes['total'],
            ],
        ];
    }

    public function details($connection, array $filters, $section, $key, $column = null)
    {
        $businessId = (int) ($filters['business_id'] ?? 0);
        if (!$businessId) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.select_business_error'));
        }
        $business = $this->context->businessMeta($connection, $businessId);
        $range = DateRange::normalize($filters['from'] ?? null, $filters['to'] ?? null, $business['time_zone']);
        $filters = array_merge($filters, $range, [
            'business_id' => $businessId,
            'location_id' => !empty($filters['location_id']) ? (int) $filters['location_id'] : null,
            'store_id' => !empty($filters['store_id']) ? (int) $filters['store_id'] : null,
        ]);

        switch ($section) {
            case 'purchases':
                return $this->purchaseDetails($connection, $filters, $key);
            case 'stock_movements':
                return $this->stockDetails($connection, $filters, $key, $column);
            case 'supplier_payments':
                return $this->supplierPaymentDetails($connection, $filters, (int) $key);
            case 'accounts':
                return $this->accountDetails($connection, $filters, (int) $key);
            case 'supplier_ledgers':
                return $this->supplierLedgerDetails($connection, $filters, (int) $key);
            default:
                throw new RuntimeException(__('simpleaudit::simpleaudit.unknown_audit_section'));
        }
    }

    protected function validateScope($connection, array $filters)
    {
        if ($filters['location_id']) {
            $ok = DB::connection($connection)->table('business_locations')
                ->where('id', $filters['location_id'])
                ->where('business_id', $filters['business_id'])
                ->exists();
            if (!$ok) {
                throw new RuntimeException(__('simpleaudit::simpleaudit.location_scope_error'));
            }
        }

        if ($filters['store_id'] && Schema::connection($connection)->hasTable('stores')) {
            $q = DB::connection($connection)->table('stores')
                ->where('id', $filters['store_id'])
                ->where('business_id', $filters['business_id']);
            if ($filters['location_id']) {
                $q->where('location_id', $filters['location_id']);
            }
            if (!$q->exists()) {
                throw new RuntimeException(__('simpleaudit::simpleaudit.store_scope_error'));
            }
        }
    }

    protected function purchaseSummary($connection, array $filters, array $business, array $changes)
    {
        $q = DB::connection($connection)->table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('t.business_id', $filters['business_id'])
            ->where('t.type', 'purchase')
            ->whereIn('t.status', config('simpleaudit.purchase_statuses', ['received', 'final', 'outright_purchase']))
            ->whereNull('t.deleted_at')
            ->whereNull('t.new_deleted_at')
            ->whereNull('pl.deleted_at')
            ->whereNull('pl.new_deleted_at')
            ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']]);

        $this->applyTransactionLocationStore($q, 't', $filters);

        $rows = $q->groupBy('pl.product_id', 'pl.variation_id', 'p.name', 'p.sku', 'v.name', 'c.name', 'c.category_type')
            ->selectRaw("pl.product_id, pl.variation_id, p.name AS product_name, p.sku, COALESCE(v.name,'') AS variation_name,
                c.name AS category_name, c.category_type,
                SUM(pl.quantity) AS qty,
                CASE WHEN SUM(pl.quantity)=0 THEN 0 ELSE SUM(pl.quantity * pl.purchase_price) / SUM(pl.quantity) END AS unit_cost,
                SUM(pl.quantity * pl.purchase_price_inc_tax) AS total,
                SUM(pl.quantity * pl.discount_amount) AS discount,
                SUM(pl.quantity * pl.item_tax) AS tax,
                COUNT(DISTINCT t.id) AS transaction_count")
            ->orderBy('p.name')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $key = $this->productKey($row->product_id, $row->variation_id);
            $mapped[$key] = [
                'key' => $key,
                'product_id' => (int) $row->product_id,
                'variation_id' => (int) $row->variation_id,
                'product' => $this->productLabel($row->product_name, $row->variation_name),
                'sku' => $row->sku,
                'qty' => (float) $row->qty,
                'unit_cost' => (float) $row->unit_cost,
                'total' => (float) $row->total,
                'discount' => (float) $row->discount,
                'tax' => (float) $row->tax,
                'transaction_count' => (int) $row->transaction_count,
                'change_count' => (int) ($changes['purchase_products'][$key] ?? 0),
                'qty_precision' => $this->isFuelCategory($row->category_name, $row->category_type) ? 3 : $business['quantity_precision'],
            ];
        }

        // Preserve changed/deleted purchase-line products even when their current
        // source row no longer appears in the live aggregate.
        $missingKeys = array_diff(array_keys($changes['purchase_products']), array_keys($mapped));
        foreach ($this->productMetaForKeys($connection, $missingKeys, $business['quantity_precision']) as $key => $meta) {
            $mapped[$key] = array_merge($meta, [
                'qty' => 0.0, 'unit_cost' => 0.0, 'total' => 0.0, 'discount' => 0.0, 'tax' => 0.0,
                'transaction_count' => 0,
                'change_count' => (int) ($changes['purchase_products'][$key] ?? 0),
            ]);
        }

        uasort($mapped, function ($a, $b) {
            return strcasecmp($a['product'], $b['product']);
        });

        $totals = ['qty' => 0.0, 'unit_cost' => 0.0, 'total' => 0.0, 'discount' => 0.0, 'tax' => 0.0];
        $weightedNumerator = 0.0;
        foreach ($mapped as $row) {
            $totals['qty'] += $row['qty'];
            $totals['total'] += $row['total'];
            $totals['discount'] += $row['discount'];
            $totals['tax'] += $row['tax'];
            $weightedNumerator += $row['qty'] * $row['unit_cost'];
        }
        $totals['unit_cost'] = $totals['qty'] != 0 ? $weightedNumerator / $totals['qty'] : 0.0;

        return ['rows' => array_values($mapped), 'totals' => $totals];
    }

    protected function stockSummary($connection, array $filters, array $business, array $purchases, array $changes)
    {
        $purchaseMap = [];
        foreach ($purchases['rows'] as $row) {
            $purchaseMap[$row['key']] = $row;
        }

        $returns = $this->purchaseReturnMap($connection, $filters);
        $adjustments = $this->stockAdjustmentMap($connection, $filters);

        $keys = array_values(array_unique(array_merge(
            array_keys($purchaseMap),
            array_keys($returns),
            array_keys($adjustments),
            array_keys($changes['stock_products'])
        )));

        $meta = $this->productMetaForKeys($connection, $keys, $business['quantity_precision']);
        $locationIds = $this->locationIds($connection, $filters);
        // The supplied ERP schema stores physical stock in variation_location_details
        // at location level (there is no store_id column). When a specific store is
        // selected we keep movement columns store-scoped, but do not mix them with
        // a location-wide Before/After balance.
        if ($filters['store_id']) {
            $opening = [];
            $closing = [];
        } else {
            $opening = $this->stockBalanceMap($connection, $filters['business_id'], $locationIds, $keys, $filters['start']);
            $closing = $this->stockBalanceMap($connection, $filters['business_id'], $locationIds, $keys, $filters['end']);
        }

        $rows = [];
        foreach ($keys as $key) {
            $m = $meta[$key] ?? null;
            if (!$m) {
                continue;
            }
            $before = array_key_exists($key, $opening) ? $opening[$key] : null;
            $after = array_key_exists($key, $closing) ? $closing[$key] : null;
            $purchaseQty = isset($purchaseMap[$key]) ? (float) $purchaseMap[$key]['qty'] : 0.0;
            $returnQty = isset($returns[$key]) ? (float) $returns[$key]['qty'] : 0.0;
            $adjustQty = isset($adjustments[$key]) ? (float) $adjustments[$key]['qty'] : 0.0;
            $expected = $before === null ? null : $before + $purchaseQty - $returnQty + $adjustQty;
            $difference = ($after === null || $expected === null) ? null : $after - $expected;

            $rows[] = array_merge($m, [
                'before' => $before,
                'purchases' => $purchaseQty,
                'purchase_return' => $returnQty,
                'stock_adjustment' => $adjustQty,
                'after' => $after,
                'difference' => $difference,
                'change_count' => (int) ($changes['stock_products'][$key] ?? 0),
                'snapshot_available' => $before !== null && $after !== null,
            ]);
        }

        usort($rows, function ($a, $b) {
            return strcasecmp($a['product'], $b['product']);
        });

        $totals = [
            'before' => 0.0, 'purchases' => 0.0, 'purchase_return' => 0.0,
            'stock_adjustment' => 0.0, 'after' => 0.0, 'difference' => 0.0,
            'snapshot_complete' => true,
        ];
        foreach ($rows as $row) {
            if ($row['before'] === null || $row['after'] === null || $row['difference'] === null) {
                $totals['snapshot_complete'] = false;
            } else {
                $totals['before'] += $row['before'];
                $totals['after'] += $row['after'];
                $totals['difference'] += $row['difference'];
            }
            $totals['purchases'] += $row['purchases'];
            $totals['purchase_return'] += $row['purchase_return'];
            $totals['stock_adjustment'] += $row['stock_adjustment'];
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    protected function purchaseReturnMap($connection, array $filters)
    {
        // quantity_returned on purchase_lines is cumulative. For periods fully
        // covered by Simple Audit tracking, use captured deltas so a return from
        // an earlier period is not counted again in a later period.
        $trackingStarted = $this->auditTrackingStartedAt($connection);
        if ($trackingStarted && $filters['start'] >= $trackingStarted && Schema::connection($connection)->hasTable('sau_change_events')) {
            $events = DB::connection($connection)->table('sau_change_events')
                ->where('business_id', $filters['business_id'])
                ->where('source_table', 'purchase_lines')
                ->whereBetween('occurred_at', [$filters['start'], $filters['end']])
                ->whereNotNull('product_id');
            if ($filters['location_id']) {
                $events->where('location_id', $filters['location_id']);
            }
            if ($filters['store_id']) {
                $events->where('store_id', $filters['store_id']);
            }

            $map = [];
            foreach ($events->get(['product_id','variation_id','old_data','new_data']) as $event) {
                $old = $this->decodeJson($event->old_data);
                $new = $this->decodeJson($event->new_data);
                $oldQty = is_array($old) ? (float) ($old['quantity_returned'] ?? 0) : 0.0;
                $newQty = is_array($new) ? (float) ($new['quantity_returned'] ?? 0) : 0.0;
                $delta = $newQty - $oldQty;
                if (abs($delta) < 0.0000001) {
                    continue;
                }
                $key = $this->productKey($event->product_id, $event->variation_id ?: 0);
                $map[$key] = ['qty' => (float) (($map[$key]['qty'] ?? 0.0) + $delta)];
            }
            return $map;
        }

        // Historical fallback for periods before SAU tracking was installed.
        // The legacy schema stores only a cumulative quantity_returned on the
        // original purchase line, so this is the best source available there.
        $q = DB::connection($connection)->table('purchase_lines as pl')
            ->join('transactions as parent', 'parent.id', '=', 'pl.transaction_id')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->where('parent.business_id', $filters['business_id'])
            ->where('parent.type', 'purchase')
            ->whereNull('parent.deleted_at')
            ->whereNull('pl.deleted_at')
            ->where('pl.quantity_returned', '>', 0)
            ->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('transactions as pr')
                    ->whereColumn('pr.return_parent_id', 'parent.id')
                    ->where('pr.type', 'purchase_return')
                    ->whereNull('pr.deleted_at')
                    ->whereBetween('pr.transaction_date', [$filters['start'], $filters['end']]);
                if ($filters['location_id']) {
                    $sub->where('pr.location_id', $filters['location_id']);
                }
                if ($filters['store_id']) {
                    $sub->where('pr.store_id', $filters['store_id']);
                }
            });

        if ($filters['location_id']) {
            $q->where('parent.location_id', $filters['location_id']);
        }
        if ($filters['store_id']) {
            $q->where('parent.store_id', $filters['store_id']);
        }

        $rows = $q->groupBy('pl.product_id', 'pl.variation_id')
            ->selectRaw('pl.product_id, pl.variation_id, SUM(pl.quantity_returned) AS qty')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$this->productKey($row->product_id, $row->variation_id)] = ['qty' => (float) $row->qty];
        }
        return $map;
    }

    protected function stockAdjustmentMap($connection, array $filters)
    {
        if (!Schema::connection($connection)->hasTable('stock_adjustment_lines')) {
            return [];
        }

        $keywords = config('simpleaudit.positive_adjustment_keywords', ['add','increase','in','positive','excess']);
        $positiveChecks = [];
        foreach ($keywords as $keyword) {
            $safe = str_replace("'", "''", strtolower($keyword));
            $positiveChecks[] = "LOWER(COALESCE(sal.type,'')) = '{$safe}'";
            $positiveChecks[] = "LOWER(COALESCE(sal.stock_adjustment_type,'')) LIKE '%{$safe}%'";
            $positiveChecks[] = "LOWER(COALESCE(t.stock_adjustment_type,'')) LIKE '%{$safe}%'";
        }
        $condition = implode(' OR ', $positiveChecks);
        $signed = "CASE WHEN ({$condition}) THEN sal.quantity ELSE -sal.quantity END";

        $q = DB::connection($connection)->table('stock_adjustment_lines as sal')
            ->join('transactions as t', 't.id', '=', 'sal.transaction_id')
            ->where('t.business_id', $filters['business_id'])
            ->where('t.type', 'stock_adjustment')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']]);
        $this->applyTransactionLocationStore($q, 't', $filters);

        $rows = $q->groupBy('sal.product_id', 'sal.variation_id')
            ->selectRaw("sal.product_id, sal.variation_id, SUM({$signed}) AS qty")
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[$this->productKey($row->product_id, $row->variation_id)] = ['qty' => (float) $row->qty];
        }
        return $map;
    }

    protected function supplierPaymentSummary($connection, array $filters, array $changes)
    {
        $inRange = $this->supplierPaymentAggregate($connection, $filters, 'range');
        $supplierIds = array_values(array_unique(array_merge(
            array_keys($inRange),
            array_keys($changes['supplier_payments'])
        )));
        if (!$supplierIds) {
            return ['rows' => [], 'totals' => ['before' => 0.0, 'after' => 0.0, 'difference' => 0.0]];
        }

        $before = $this->supplierPaymentAggregate($connection, $filters, 'before', $supplierIds);
        $after = $this->supplierPaymentAggregate($connection, $filters, 'through', $supplierIds);
        $names = $this->supplierNames($connection, $supplierIds);

        $rows = [];
        foreach ($supplierIds as $supplierId) {
            $b = (float) ($before[$supplierId]['amount'] ?? 0);
            $a = (float) ($after[$supplierId]['amount'] ?? 0);
            $rows[] = [
                'key' => (string) $supplierId,
                'supplier_id' => (int) $supplierId,
                'supplier' => $names[$supplierId] ?? __('simpleaudit::simpleaudit.supplier_number', ['id' => $supplierId]),
                'before' => $b,
                'after' => $a,
                'difference' => $a - $b,
                'payment_count' => (int) ($inRange[$supplierId]['count'] ?? 0),
                'change_count' => (int) ($changes['supplier_payments'][$supplierId] ?? 0),
            ];
        }
        usort($rows, fn($a, $b) => strcasecmp($a['supplier'], $b['supplier']));

        return ['rows' => $rows, 'totals' => $this->threeColumnTotals($rows)];
    }

    protected function supplierPaymentAggregate($connection, array $filters, $mode, array $restrictSupplierIds = [])
    {
        $q = DB::connection($connection)->table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->leftJoin('contacts as c1', 'c1.id', '=', 'tp.payment_for')
            ->leftJoin('contacts as c2', 'c2.id', '=', 't.contact_id')
            ->whereRaw('COALESCE(tp.business_id,t.business_id,c1.business_id,c2.business_id) = ?', [$filters['business_id']])
            ->whereNull('tp.deleted_at')
            ->whereRaw("COALESCE(c1.type,c2.type) IN ('supplier','both')");

        $dateExpr = 'COALESCE(tp.paid_on,tp.created_at)';
        if ($mode === 'range') {
            $q->whereRaw("{$dateExpr} BETWEEN ? AND ?", [$filters['start'], $filters['end']]);
        } elseif ($mode === 'before') {
            $q->whereRaw("{$dateExpr} < ?", [$filters['start']]);
        } elseif ($mode === 'through') {
            $q->whereRaw("{$dateExpr} <= ?", [$filters['end']]);
        }

        if ($restrictSupplierIds) {
            $marks = implode(',', array_fill(0, count($restrictSupplierIds), '?'));
            $q->whereRaw("COALESCE(c1.id,c2.id) IN ({$marks})", array_values($restrictSupplierIds));
        }

        $this->applyPaymentLocationStore($q, $filters);

        $rows = $q->groupBy(DB::raw('COALESCE(c1.id,c2.id)'))
            ->selectRaw('COALESCE(c1.id,c2.id) AS supplier_id, SUM(tp.amount) AS amount, COUNT(tp.id) AS row_count')
            ->get();
        $map = [];
        foreach ($rows as $row) {
            if ($row->supplier_id === null) {
                continue;
            }
            $map[(int) $row->supplier_id] = ['amount' => (float) $row->amount, 'count' => (int) $row->row_count];
        }
        return $map;
    }

    protected function accountSummary($connection, array $filters, array $changes)
    {
        $activity = $this->affectedAccountActivity($connection, $filters);
        $accountIds = array_values(array_unique(array_merge(array_keys($activity), array_keys($changes['accounts']))));
        if (!$accountIds) {
            return ['rows' => [], 'totals' => ['before' => 0.0, 'after' => 0.0, 'difference' => 0.0]];
        }

        $before = $this->accountBalanceMap($connection, $filters, $accountIds, 'before');
        $after = $this->accountBalanceMap($connection, $filters, $accountIds, 'through');
        $accounts = DB::connection($connection)->table('accounts')
            ->whereIn('id', $accountIds)
            ->get(['id','name','account_number'])
            ->keyBy('id');

        $rows = [];
        foreach ($accountIds as $id) {
            $a = $accounts->get($id);
            $b = (float) ($before[$id] ?? 0);
            $e = (float) ($after[$id] ?? 0);
            $rows[] = [
                'key' => (string) $id,
                'account_id' => (int) $id,
                'account' => $a ? $a->name : __('simpleaudit::simpleaudit.account_number_fallback', ['id' => $id]),
                'account_number' => $a ? $a->account_number : '',
                'before' => $b,
                'after' => $e,
                'difference' => $e - $b,
                'activity_count' => (int) ($activity[$id] ?? 0),
                'change_count' => (int) ($changes['accounts'][$id] ?? 0),
            ];
        }
        usort($rows, fn($a, $b) => strcasecmp(($a['account_number'] . ' ' . $a['account']), ($b['account_number'] . ' ' . $b['account'])));
        return ['rows' => $rows, 'totals' => $this->threeColumnTotals($rows)];
    }

    protected function affectedAccountActivity($connection, array $filters)
    {
        $q = DB::connection($connection)->table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $filters['business_id'])
            ->whereBetween('at.operation_date', [$filters['start'], $filters['end']])
            ->whereNull('at.deleted_at')
            ->whereNull('at.new_deleted_at')
            ->where('at.reversed', 0)
            ->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('transactions as tx')
                        ->whereColumn('tx.id', 'at.transaction_id')
                        ->whereIn('tx.type', ['purchase','purchase_return','stock_adjustment']);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_payments as tp2')
                        ->leftJoin('transactions as tx2', 'tx2.id', '=', 'tp2.transaction_id')
                        ->join('contacts as cs2', function ($join) {
                            $join->on('cs2.id', '=', DB::raw('COALESCE(tp2.payment_for,tx2.contact_id)'));
                        })
                        ->whereColumn('tp2.id', 'at.transaction_payment_id')
                        ->whereIn('cs2.type', ['supplier','both']);
                });
            });

        $this->applyAccountLocationStore($q, $filters);

        $rows = $q->groupBy('at.account_id')->selectRaw('at.account_id, COUNT(at.id) AS row_count')->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->account_id] = (int) $row->row_count;
        }
        return $map;
    }

    protected function accountBalanceMap($connection, array $filters, array $accountIds, $mode)
    {
        $q = DB::connection($connection)->table('account_transactions as at')
            ->whereIn('at.account_id', $accountIds)
            ->whereNull('at.deleted_at')
            ->whereNull('at.new_deleted_at')
            ->where('at.reversed', 0);
        if ($mode === 'before') {
            $q->where('at.operation_date', '<', $filters['start']);
        } else {
            $q->where('at.operation_date', '<=', $filters['end']);
        }
        $this->applyAccountLocationStore($q, $filters);
        $rows = $q->groupBy('at.account_id')
            ->selectRaw("at.account_id, SUM(CASE WHEN at.type='debit' THEN at.amount ELSE -at.amount END) AS balance")
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->account_id] = (float) $row->balance;
        }
        return $map;
    }

    protected function supplierLedgerSummary($connection, array $filters, array $changes)
    {
        if (!Schema::connection($connection)->hasTable('contact_ledgers')) {
            return ['rows' => [], 'totals' => ['before' => 0.0, 'after' => 0.0, 'difference' => 0.0]];
        }

        // Only suppliers whose ledger was affected by Purchases, Purchase Returns,
        // Stock Adjustments or Supplier Payments belong in this audit section.
        // The Before/After balance below still uses the full supplier ledger so the
        // displayed balance remains an actual ledger balance rather than a partial one.
        $affected = $this->affectedSupplierLedgerActivity($connection, $filters);
        $supplierIds = array_values(array_unique(array_merge(array_keys($affected), array_keys($changes['supplier_ledgers']))));
        if (!$supplierIds) {
            return ['rows' => [], 'totals' => ['before' => 0.0, 'after' => 0.0, 'difference' => 0.0]];
        }
        $before = $this->supplierLedgerAggregate($connection, $filters, 'before', $supplierIds);
        $after = $this->supplierLedgerAggregate($connection, $filters, 'through', $supplierIds);
        $names = $this->supplierNames($connection, $supplierIds);

        $rows = [];
        foreach ($supplierIds as $id) {
            $b = (float) ($before[$id]['balance'] ?? 0);
            $a = (float) ($after[$id]['balance'] ?? 0);
            $rows[] = [
                'key' => (string) $id,
                'supplier_id' => (int) $id,
                'supplier' => $names[$id] ?? __('simpleaudit::simpleaudit.supplier_number', ['id' => $id]),
                'before' => $b,
                'after' => $a,
                'difference' => $a - $b,
                'ledger_count' => (int) ($affected[$id]['count'] ?? 0),
                'change_count' => (int) ($changes['supplier_ledgers'][$id] ?? 0),
            ];
        }
        usort($rows, fn($a, $b) => strcasecmp($a['supplier'], $b['supplier']));
        return ['rows' => $rows, 'totals' => $this->threeColumnTotals($rows)];
    }

    protected function affectedSupplierLedgerActivity($connection, array $filters)
    {
        $q = DB::connection($connection)->table('contact_ledgers as cl')
            ->join('contacts as c', 'c.id', '=', 'cl.contact_id')
            ->where('c.business_id', $filters['business_id'])
            ->whereIn('c.type', ['supplier','both'])
            ->whereNull('cl.deleted_at')
            ->where('cl.reversed', 0)
            ->whereBetween('cl.operation_date', [$filters['start'], $filters['end']])
            ->where(function ($x) {
                $x->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('transactions as slt')
                        ->whereColumn('slt.id', 'cl.transaction_id')
                        ->whereIn('slt.type', ['purchase','purchase_return','stock_adjustment']);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('transaction_payments as slp')
                        ->whereColumn('slp.id', 'cl.transaction_payment_id')
                        ->whereNull('slp.deleted_at');
                });
            });
        $this->applyContactLedgerLocationStore($q, $filters);

        $rows = $q->groupBy('cl.contact_id')
            ->selectRaw('cl.contact_id, COUNT(cl.id) AS row_count')
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->contact_id] = ['count' => (int) $row->row_count];
        }
        return $map;
    }

    protected function supplierLedgerAggregate($connection, array $filters, $mode, array $supplierIds = [])
    {
        $q = DB::connection($connection)->table('contact_ledgers as cl')
            ->join('contacts as c', 'c.id', '=', 'cl.contact_id')
            ->where('c.business_id', $filters['business_id'])
            ->whereIn('c.type', ['supplier','both'])
            ->whereNull('cl.deleted_at')
            ->where('cl.reversed', 0);
        if ($mode === 'range') {
            $q->whereBetween('cl.operation_date', [$filters['start'], $filters['end']]);
        } elseif ($mode === 'before') {
            $q->where('cl.operation_date', '<', $filters['start']);
        } else {
            $q->where('cl.operation_date', '<=', $filters['end']);
        }
        if ($supplierIds) {
            $q->whereIn('cl.contact_id', $supplierIds);
        }
        $this->applyContactLedgerLocationStore($q, $filters);

        $rows = $q->groupBy('cl.contact_id')
            ->selectRaw("cl.contact_id, SUM(CASE WHEN cl.type='credit' THEN cl.amount ELSE -cl.amount END) AS balance, COUNT(cl.id) AS row_count")
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->contact_id] = ['balance' => (float) $row->balance, 'count' => (int) $row->row_count];
        }
        return $map;
    }

    protected function changeMaps($connection, array $filters)
    {
        $empty = [
            'total' => 0,
            'purchase_products' => [],
            'stock_products' => [],
            'supplier_payments' => [],
            'accounts' => [],
            'supplier_ledgers' => [],
        ];
        if (!Schema::connection($connection)->hasTable('sau_change_events')) {
            return $empty;
        }

        $q = DB::connection($connection)->table('sau_change_events')
            ->where('business_id', $filters['business_id'])
            ->whereBetween('occurred_at', [$filters['start'], $filters['end']]);
        if ($filters['location_id']) {
            $q->where(function ($x) use ($filters) {
                $x->where('location_id', $filters['location_id'])->orWhereNull('location_id');
            });
        }
        if ($filters['store_id']) {
            $q->where(function ($x) use ($filters) {
                $x->where('store_id', $filters['store_id'])->orWhereNull('store_id');
            });
        }
        $rows = $q->get(['source_table','product_id','variation_id','contact_id','account_id']);
        $empty['total'] = $rows->count();

        foreach ($rows as $row) {
            if ($row->product_id) {
                $key = $this->productKey($row->product_id, $row->variation_id ?: 0);
                if (in_array($row->source_table, ['purchase_lines'], true)) {
                    $empty['purchase_products'][$key] = ($empty['purchase_products'][$key] ?? 0) + 1;
                }
                if (in_array($row->source_table, ['purchase_lines','stock_adjustment_lines'], true)) {
                    $empty['stock_products'][$key] = ($empty['stock_products'][$key] ?? 0) + 1;
                }
            }
            if ($row->contact_id) {
                if ($row->source_table === 'transaction_payments') {
                    $empty['supplier_payments'][(int) $row->contact_id] = ($empty['supplier_payments'][(int) $row->contact_id] ?? 0) + 1;
                }
                if ($row->source_table === 'contact_ledgers') {
                    $empty['supplier_ledgers'][(int) $row->contact_id] = ($empty['supplier_ledgers'][(int) $row->contact_id] ?? 0) + 1;
                }
            }
            if ($row->account_id && $row->source_table === 'account_transactions') {
                $empty['accounts'][(int) $row->account_id] = ($empty['accounts'][(int) $row->account_id] ?? 0) + 1;
            }
        }
        return $empty;
    }

    protected function stockBalanceMap($connection, $businessId, array $locationIds, array $keys, $cutoff)
    {
        if (!$keys || !$locationIds || !Schema::connection($connection)->hasTable('sau_stock_snapshots')) {
            return [];
        }
        $productIds = [];
        foreach ($keys as $key) {
            [$p] = $this->parseProductKey($key);
            $productIds[] = $p;
        }
        $productIds = array_values(array_unique($productIds));

        // One grouped query for all selected locations avoids an N+1 query per
        // branch and keeps the report responsive for businesses with many locations.
        $latest = DB::connection($connection)->table('sau_stock_snapshots')
            ->selectRaw('location_id, product_id, variation_id, MAX(id) AS max_id')
            ->where('business_id', $businessId)
            ->whereIn('location_id', $locationIds)
            ->whereIn('product_id', $productIds)
            ->where('changed_at', '<=', $cutoff)
            ->groupBy('location_id', 'product_id', 'variation_id');

        $rows = DB::connection($connection)->table('sau_stock_snapshots as s')
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('s.id', '=', 'latest.max_id');
            })
            ->get(['s.product_id','s.variation_id','s.qty_after']);

        $totals = [];
        foreach ($rows as $row) {
            $key = $this->productKey($row->product_id, $row->variation_id);
            $totals[$key] = ($totals[$key] ?? 0.0) + (float) $row->qty_after;
        }
        return $totals;
    }

    protected function locationIds($connection, array $filters)
    {
        if ($filters['location_id']) {
            return [(int) $filters['location_id']];
        }
        return DB::connection($connection)->table('business_locations')
            ->where('business_id', $filters['business_id'])
            ->whereNull('deleted_at')
            ->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    protected function productMetaForKeys($connection, array $keys, $defaultQtyPrecision)
    {
        if (!$keys) {
            return [];
        }
        $productIds = [];
        $variationIds = [];
        foreach ($keys as $key) {
            [$p, $v] = $this->parseProductKey($key);
            $productIds[] = $p;
            if ($v) {
                $variationIds[] = $v;
            }
        }
        $q = DB::connection($connection)->table('products as p')
            ->leftJoin('variations as v', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->whereIn('p.id', array_unique($productIds));
        if ($variationIds) {
            // Retain product rows with requested variations; products with variation 0
            // are resolved below using the product metadata.
            $q->where(function ($x) use ($variationIds) {
                $x->whereIn('v.id', array_unique($variationIds))->orWhereNull('v.id');
            });
        }
        $rows = $q->get(['p.id as product_id','p.name as product_name','p.sku','v.id as variation_id','v.name as variation_name','c.name as category_name','c.category_type']);

        $result = [];
        foreach ($rows as $row) {
            $key = $this->productKey($row->product_id, $row->variation_id ?: 0);
            if (!in_array($key, $keys, true) && !in_array($this->productKey($row->product_id, 0), $keys, true)) {
                continue;
            }
            $result[$key] = [
                'key' => $key,
                'product_id' => (int) $row->product_id,
                'variation_id' => (int) ($row->variation_id ?: 0),
                'product' => $this->productLabel($row->product_name, $row->variation_name),
                'sku' => $row->sku,
                'qty_precision' => $this->isFuelCategory($row->category_name, $row->category_type) ? 3 : $defaultQtyPrecision,
            ];
        }

        // Handle exact variation keys not returned due to unusual legacy variation rows.
        foreach ($keys as $key) {
            if (isset($result[$key])) {
                continue;
            }
            [$p, $v] = $this->parseProductKey($key);
            $row = DB::connection($connection)->table('products as p')
                ->leftJoin('variations as vv', function ($join) use ($v) {
                    $join->on('vv.product_id', '=', 'p.id')->where('vv.id', '=', $v);
                })
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->where('p.id', $p)
                ->first(['p.id as product_id','p.name as product_name','p.sku','vv.id as variation_id','vv.name as variation_name','c.name as category_name','c.category_type']);
            if ($row) {
                $result[$key] = [
                    'key' => $key,
                    'product_id' => (int) $p,
                    'variation_id' => (int) $v,
                    'product' => $this->productLabel($row->product_name, $row->variation_name),
                    'sku' => $row->sku,
                    'qty_precision' => $this->isFuelCategory($row->category_name, $row->category_type) ? 3 : $defaultQtyPrecision,
                ];
            }
        }
        return $result;
    }

    protected function supplierNames($connection, array $ids)
    {
        if (!$ids) {
            return [];
        }
        return DB::connection($connection)->table('contacts')
            ->whereIn('id', $ids)
            ->get(['id','name','supplier_business_name'])
            ->mapWithKeys(function ($row) {
                $name = trim((string) ($row->supplier_business_name ?: $row->name));
                return [(int) $row->id => $name];
            })->all();
    }

    protected function applyTransactionLocationStore(Builder $q, $alias, array $filters)
    {
        if ($filters['location_id']) {
            $q->where($alias . '.location_id', $filters['location_id']);
        }
        if ($filters['store_id']) {
            $q->where($alias . '.store_id', $filters['store_id']);
        }
    }

    protected function applyPaymentLocationStore(Builder $q, array $filters)
    {
        if ($filters['location_id']) {
            $locationId = (int) $filters['location_id'];
            $hasAtLocation = $this->hasColumn($q->getConnection()->getName(), 'account_transactions', 'location_id');

            $q->where(function ($x) use ($locationId, $hasAtLocation) {
                // A normal transaction payment inherits the transaction location.
                $x->where('t.location_id', $locationId);

                if ($hasAtLocation) {
                    $x->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))->from('account_transactions as atl')
                            ->whereColumn('atl.transaction_payment_id', 'tp.id')
                            ->where('atl.location_id', $locationId);
                    });
                } else {
                    // Older tenant schemas do not have account_transactions.location_id.
                    // Derive the location from the linked source transaction instead.
                    $x->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))
                            ->from('account_transactions as atl')
                            ->join('transactions as atlt', 'atlt.id', '=', 'atl.transaction_id')
                            ->whereColumn('atl.transaction_payment_id', 'tp.id')
                            ->where('atlt.location_id', $locationId);
                    })->orWhereExists(function ($sub) use ($locationId) {
                        // Some standalone payments only carry an account link.
                        $sub->select(DB::raw(1))
                            ->from('account_transactions as atl2')
                            ->join('accounts as atla', 'atla.id', '=', 'atl2.account_id')
                            ->whereColumn('atl2.transaction_payment_id', 'tp.id')
                            ->where('atla.location_id', $locationId);
                    });
                }
            });
        }
        if ($filters['store_id']) {
            $q->where('t.store_id', $filters['store_id']);
        }
    }

    protected function applyAccountLocationStore(Builder $q, array $filters)
    {
        if ($filters['location_id']) {
            $locationId = (int) $filters['location_id'];
            $connection = $q->getConnection()->getName();
            $hasAtLocation = $this->hasColumn($connection, 'account_transactions', 'location_id');

            $q->where(function ($x) use ($locationId, $hasAtLocation) {
                if ($hasAtLocation) {
                    $x->where('at.location_id', $locationId)
                        ->orWhereExists(function ($sub) use ($locationId) {
                            $sub->select(DB::raw(1))->from('transactions as txl')
                                ->whereColumn('txl.id', 'at.transaction_id')
                                ->where('txl.location_id', $locationId);
                        });
                } else {
                    // Compatibility for older tenant DBs: account_transactions has
                    // no location_id. Never reference the missing column; derive the
                    // location from its transaction, then from the account itself.
                    $x->whereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))->from('transactions as txl')
                            ->whereColumn('txl.id', 'at.transaction_id')
                            ->where('txl.location_id', $locationId);
                    })->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))->from('accounts as axl')
                            ->whereColumn('axl.id', 'at.account_id')
                            ->where('axl.location_id', $locationId);
                    });
                }
            });
        }
        if ($filters['store_id']) {
            $q->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))->from('transactions as txs')
                    ->whereColumn('txs.id', 'at.transaction_id')
                    ->where('txs.store_id', $filters['store_id']);
            });
        }
    }

    protected function applyContactLedgerLocationStore(Builder $q, array $filters)
    {
        if ($filters['location_id']) {
            $locationId = (int) $filters['location_id'];
            $connection = $q->getConnection()->getName();
            $hasAtLocation = $this->hasColumn($connection, 'account_transactions', 'location_id');

            $q->where(function ($x) use ($locationId, $hasAtLocation) {
                $x->whereExists(function ($sub) use ($locationId) {
                    $sub->select(DB::raw(1))->from('transactions as ctl')
                        ->whereColumn('ctl.id', 'cl.transaction_id')
                        ->where('ctl.location_id', $locationId);
                });

                if ($hasAtLocation) {
                    $x->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))->from('account_transactions as cal')
                            ->whereColumn('cal.transaction_payment_id', 'cl.transaction_payment_id')
                            ->where('cal.location_id', $locationId);
                    });
                } else {
                    $x->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))
                            ->from('account_transactions as cal')
                            ->join('transactions as calt', 'calt.id', '=', 'cal.transaction_id')
                            ->whereColumn('cal.transaction_payment_id', 'cl.transaction_payment_id')
                            ->where('calt.location_id', $locationId);
                    })->orWhereExists(function ($sub) use ($locationId) {
                        $sub->select(DB::raw(1))
                            ->from('account_transactions as cal2')
                            ->join('accounts as cala', 'cala.id', '=', 'cal2.account_id')
                            ->whereColumn('cal2.transaction_payment_id', 'cl.transaction_payment_id')
                            ->where('cala.location_id', $locationId);
                    });
                }
            });
        }
        if ($filters['store_id']) {
            $q->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))->from('transactions as cts')
                    ->whereColumn('cts.id', 'cl.transaction_id')
                    ->where('cts.store_id', $filters['store_id']);
            });
        }
    }

    protected function hasColumn($connection, $table, $column)
    {
        $key = $connection . '|' . $table . '|' . $column;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::connection($connection)->hasColumn($table, $column);
        }
        return $this->columnCache[$key];
    }

    protected function trackingStartedAt($connection)
    {
        return $this->settingStartedAt($connection, 'stock_tracking_started_at');
    }

    protected function auditTrackingStartedAt($connection)
    {
        return $this->settingStartedAt($connection, 'audit_tracking_started_at');
    }

    protected function settingStartedAt($connection, $key)
    {
        if (!Schema::connection($connection)->hasTable('sau_settings')) {
            return null;
        }
        $raw = DB::connection($connection)->table('sau_settings')
            ->where('business_id', 0)->where('key_name', $key)->value('value_json');
        $data = $raw ? json_decode($raw, true) : null;
        return is_array($data) ? ($data['started_at'] ?? null) : null;
    }

    protected function threeColumnTotals(array $rows)
    {
        $totals = ['before' => 0.0, 'after' => 0.0, 'difference' => 0.0];
        foreach ($rows as $row) {
            $totals['before'] += (float) $row['before'];
            $totals['after'] += (float) $row['after'];
            $totals['difference'] += (float) $row['difference'];
        }
        return $totals;
    }

    protected function productKey($productId, $variationId)
    {
        return (int) $productId . ':' . (int) $variationId;
    }

    protected function parseProductKey($key)
    {
        $parts = explode(':', (string) $key, 2);
        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }

    protected function productLabel($product, $variation)
    {
        $variation = trim((string) $variation);
        if ($variation === '' || strtoupper($variation) === 'DUMMY') {
            return (string) $product;
        }
        return $product . ' — ' . $variation;
    }

    protected function isFuelCategory($name, $type)
    {
        $haystack = strtolower(trim((string) $name) . ' ' . trim((string) $type));
        return strpos($haystack, 'fuel') !== false || strpos($haystack, 'petro') !== false;
    }

    // ------------------------- Detail popups -------------------------

    protected function purchaseDetails($connection, array $filters, $key)
    {
        [$productId, $variationId] = $this->parseProductKey($key);
        $q = DB::connection($connection)->table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('users as u', 'u.id', '=', 't.created_by')
            ->where('t.business_id', $filters['business_id'])
            ->where('t.type', 'purchase')
            ->where('pl.product_id', $productId)
            ->where('pl.variation_id', $variationId)
            ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']]);
        $this->applyTransactionLocationStore($q, 't', $filters);
        $rows = $q->orderByDesc('t.transaction_date')->limit(500)->get([
            't.id','t.transaction_date','t.purchase_entry_no','t.ref_no','t.status','c.name as supplier','c.supplier_business_name',
            'pl.quantity','pl.purchase_price','pl.purchase_price_inc_tax','pl.discount_amount','pl.item_tax','pl.quantity_returned',
            'u.first_name','u.last_name'
        ])->map(function ($r) {
            return [
                'Date' => $r->transaction_date,
                'Purchase' => $r->purchase_entry_no ?: ($r->ref_no ?: ('#' . $r->id)),
                'Supplier' => $r->supplier_business_name ?: $r->supplier,
                'Qty' => (float) $r->quantity,
                'Unit Cost' => (float) $r->purchase_price,
                'Total Inc Tax' => (float) $r->quantity * (float) $r->purchase_price_inc_tax,
                'Discount' => (float) $r->quantity * (float) $r->discount_amount,
                'Tax' => (float) $r->quantity * (float) $r->item_tax,
                'Returned Qty' => (float) $r->quantity_returned,
                'Status' => $r->status,
                'User' => trim(($r->first_name ?: '') . ' ' . ($r->last_name ?: '')),
            ];
        })->all();
        return $this->detailPayload($connection, $filters, 'purchase_lines', $productId, $variationId, $rows);
    }

    protected function stockDetails($connection, array $filters, $key, $column)
    {
        [$productId, $variationId] = $this->parseProductKey($key);
        $rows = [];
        $column = $column ?: 'all';

        if (in_array($column, ['all','purchases'], true)) {
            $purchase = $this->purchaseDetails($connection, $filters, $key);
            foreach ($purchase['rows'] as $r) {
                $r = ['Movement' => 'Purchase'] + $r;
                $rows[] = $r;
            }
        }

        if (in_array($column, ['all','purchase_return'], true)) {
            $q = DB::connection($connection)->table('purchase_lines as pl')
                ->join('transactions as parent', 'parent.id', '=', 'pl.transaction_id')
                ->join('transactions as pr', function ($join) {
                    $join->on('pr.return_parent_id', '=', 'parent.id')->where('pr.type', '=', 'purchase_return');
                })
                ->leftJoin('contacts as c', 'c.id', '=', 'parent.contact_id')
                ->where('parent.business_id', $filters['business_id'])
                ->where('pl.product_id', $productId)->where('pl.variation_id', $variationId)
                ->where('pl.quantity_returned', '>', 0)
                ->whereBetween('pr.transaction_date', [$filters['start'], $filters['end']]);
            if ($filters['location_id']) $q->where('pr.location_id', $filters['location_id']);
            if ($filters['store_id']) $q->where('pr.store_id', $filters['store_id']);
            foreach ($q->orderByDesc('pr.transaction_date')->limit(500)->get([
                'pr.transaction_date','pr.ref_no','pr.id','parent.purchase_entry_no','pl.quantity_returned','c.name as supplier','c.supplier_business_name'
            ]) as $r) {
                $rows[] = [
                    'Movement' => 'Purchase Return', 'Date' => $r->transaction_date,
                    'Reference' => $r->ref_no ?: ('#'.$r->id), 'Purchase' => $r->purchase_entry_no,
                    'Supplier' => $r->supplier_business_name ?: $r->supplier, 'Qty' => (float) $r->quantity_returned,
                ];
            }
        }

        if (in_array($column, ['all','stock_adjustment'], true) && Schema::connection($connection)->hasTable('stock_adjustment_lines')) {
            $q = DB::connection($connection)->table('stock_adjustment_lines as sal')
                ->join('transactions as t', 't.id', '=', 'sal.transaction_id')
                ->where('t.business_id', $filters['business_id'])
                ->where('t.type', 'stock_adjustment')
                ->where('sal.product_id', $productId)->where('sal.variation_id', $variationId)
                ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']]);
            $this->applyTransactionLocationStore($q, 't', $filters);
            foreach ($q->orderByDesc('t.transaction_date')->limit(500)->get([
                't.transaction_date','t.ref_no','t.id','t.adjustment_type','t.stock_adjustment_type','sal.quantity','sal.type','sal.stock_adjustment_type as line_type','sal.unit_price'
            ]) as $r) {
                $rows[] = [
                    'Movement' => 'Stock Adjustment', 'Date' => $r->transaction_date,
                    'Reference' => $r->ref_no ?: ('#'.$r->id), 'Qty' => (float) $r->quantity,
                    'Type' => $r->line_type ?: ($r->type ?: ($r->stock_adjustment_type ?: $r->adjustment_type)),
                    'Unit Cost' => (float) $r->unit_price,
                ];
            }
        }

        if (in_array($column, ['all','before','after'], true) && Schema::connection($connection)->hasTable('sau_stock_snapshots')) {
            $snap = DB::connection($connection)->table('sau_stock_snapshots')
                ->where('business_id', $filters['business_id'])
                ->where('product_id', $productId)->where('variation_id', $variationId)
                ->whereBetween('changed_at', [$filters['start'], $filters['end']]);
            if ($filters['location_id']) $snap->where('location_id', $filters['location_id']);
            foreach ($snap->orderByDesc('changed_at')->limit(500)->get() as $r) {
                $rows[] = [
                    'Movement' => 'Stock Snapshot', 'Date' => $r->changed_at,
                    'Location ID' => $r->location_id, 'Before Qty' => $r->qty_before !== null ? (float) $r->qty_before : null,
                    'After Qty' => $r->qty_after !== null ? (float) $r->qty_after : null, 'Source' => $r->source,
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return strcmp((string)($b['Date'] ?? ''), (string)($a['Date'] ?? ''));
        });
        return $this->detailPayload($connection, $filters, 'stock', $productId, $variationId, array_slice($rows, 0, 500));
    }

    protected function supplierPaymentDetails($connection, array $filters, $supplierId)
    {
        $q = DB::connection($connection)->table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'tp.account_id')
            ->whereRaw('COALESCE(tp.payment_for,t.contact_id) = ?', [$supplierId])
            ->whereRaw('COALESCE(tp.business_id,t.business_id) = ?', [$filters['business_id']])
            ->whereRaw('COALESCE(tp.paid_on,tp.created_at) BETWEEN ? AND ?', [$filters['start'], $filters['end']]);
        $this->applyPaymentLocationStore($q, $filters);
        $rows = $q->orderByDesc(DB::raw('COALESCE(tp.paid_on,tp.created_at)'))->limit(500)->get([
            'tp.id','tp.paid_on','tp.created_at','tp.amount','tp.method','tp.payment_ref_no','tp.reference_no','tp.cheque_number','tp.bank_name',
            't.purchase_entry_no','t.ref_no as purchase_ref','a.name as account_name','a.account_number'
        ])->map(function ($r) {
            return [
                'Date' => $r->paid_on ?: $r->created_at,
                'Payment Ref' => $r->payment_ref_no ?: ($r->reference_no ?: ('#'.$r->id)),
                'Purchase' => $r->purchase_entry_no ?: $r->purchase_ref,
                'Method' => $r->method,
                'Amount' => (float) $r->amount,
                'Account' => trim(($r->account_number ?: '') . ' ' . ($r->account_name ?: '')),
                'Cheque No' => $r->cheque_number,
                'Bank' => $r->bank_name,
            ];
        })->all();
        return $this->detailPayload($connection, $filters, 'transaction_payments', null, null, $rows, $supplierId);
    }

    protected function accountDetails($connection, array $filters, $accountId)
    {
        $q = DB::connection($connection)->table('account_transactions as at')
            ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id')
            ->leftJoin('transaction_payments as tp', 'tp.id', '=', 'at.transaction_payment_id')
            ->where('at.account_id', $accountId)
            ->whereBetween('at.operation_date', [$filters['start'], $filters['end']])
            ->where(function ($q) {
                $q->whereIn('t.type', ['purchase','purchase_return','stock_adjustment'])
                  ->orWhereNotNull('tp.id');
            });
        $this->applyAccountLocationStore($q, $filters);
        $rows = $q->orderByDesc('at.operation_date')->limit(500)->get([
            'at.id','at.operation_date','at.type','at.amount','at.reff_no','at.txnType','at.note','t.type as transaction_type','t.purchase_entry_no','t.ref_no as transaction_ref','tp.payment_ref_no'
        ])->map(function ($r) {
            return [
                'Date' => $r->operation_date,
                'Reference' => $r->reff_no ?: ($r->payment_ref_no ?: ($r->purchase_entry_no ?: $r->transaction_ref)),
                'Source' => $r->transaction_type ?: $r->txnType,
                'Entry' => ucfirst((string)$r->type),
                'Amount' => (float) $r->amount,
                'Note' => $r->note,
            ];
        })->all();
        return $this->detailPayload($connection, $filters, 'account_transactions', null, null, $rows, null, $accountId);
    }

    protected function supplierLedgerDetails($connection, array $filters, $supplierId)
    {
        $q = DB::connection($connection)->table('contact_ledgers as cl')
            ->where('cl.contact_id', $supplierId)
            ->whereBetween('cl.operation_date', [$filters['start'], $filters['end']])
            ->where(function ($x) {
                $x->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('transactions as dlt')
                        ->whereColumn('dlt.id', 'cl.transaction_id')
                        ->whereIn('dlt.type', ['purchase','purchase_return','stock_adjustment']);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('transaction_payments as dlp')
                        ->whereColumn('dlp.id', 'cl.transaction_payment_id')
                        ->whereNull('dlp.deleted_at');
                });
            });
        $this->applyContactLedgerLocationStore($q, $filters);
        $rows = $q->orderByDesc('cl.operation_date')->limit(500)->get([
            'cl.id','cl.operation_date','cl.type','cl.sub_type','cl.amount','cl.reff_no','cl.note','cl.page','cl.transaction_id','cl.transaction_payment_id'
        ])->map(function ($r) {
            return [
                'Date' => $r->operation_date,
                'Reference' => $r->reff_no ?: ('#'.$r->id),
                'Type' => ucfirst((string)$r->type),
                'Sub Type' => $r->sub_type,
                'Amount' => (float) $r->amount,
                'Note' => $r->note,
                'Page' => $r->page,
            ];
        })->all();
        return $this->detailPayload($connection, $filters, 'contact_ledgers', null, null, $rows, $supplierId);
    }

    protected function detailPayload($connection, array $filters, $sourceTable, $productId, $variationId, array $rows, $contactId = null, $accountId = null)
    {
        $changes = [];
        if (Schema::connection($connection)->hasTable('sau_change_events')) {
            $q = DB::connection($connection)->table('sau_change_events')
                ->where('business_id', $filters['business_id'])
                ->whereBetween('occurred_at', [$filters['start'], $filters['end']]);
            if ($sourceTable && $sourceTable !== 'stock') {
                $q->where('source_table', $sourceTable);
            }
            if ($productId) $q->where('product_id', $productId);
            if ($variationId !== null && $productId) $q->where('variation_id', $variationId);
            if ($contactId) $q->where('contact_id', $contactId);
            if ($accountId) $q->where('account_id', $accountId);
            if ($filters['location_id']) {
                $q->where(function ($x) use ($filters) { $x->where('location_id',$filters['location_id'])->orWhereNull('location_id'); });
            }
            $changes = $q->orderByDesc('occurred_at')->limit(200)->get()->map(function ($e) {
                return [
                    'date' => $e->occurred_at,
                    'event' => $e->event_type,
                    'source' => $e->source_table,
                    'source_id' => $e->source_id,
                    'old' => $this->decodeJson($e->old_data),
                    'new' => $this->decodeJson($e->new_data),
                ];
            })->all();
        }
        $business = DB::connection($connection)->table('business')
            ->where('id', $filters['business_id'])
            ->first(['currency_precision','quantity_precision']);
        $currencyPrecision = max(0, min(6, (int) (($business->currency_precision ?? null) !== null ? $business->currency_precision : 2)));
        $quantityPrecision = max(0, min(6, (int) (($business->quantity_precision ?? null) !== null ? $business->quantity_precision : 2)));
        if ($productId) {
            $productCategory = DB::connection($connection)->table('products as dp')
                ->leftJoin('categories as dc', 'dc.id', '=', 'dp.category_id')
                ->where('dp.id', $productId)
                ->first(['dc.name','dc.category_type']);
            if ($productCategory && $this->isFuelCategory($productCategory->name, $productCategory->category_type)) {
                $quantityPrecision = 3;
            }
        }
        return [
            'rows' => $rows,
            'changes' => $changes,
            'precision' => ['currency' => $currencyPrecision, 'quantity' => $quantityPrecision],
        ];
    }

    protected function decodeJson($value)
    {
        if (!$value) return null;
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
