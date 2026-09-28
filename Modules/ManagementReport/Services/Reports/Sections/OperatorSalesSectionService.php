<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;

class OperatorSalesSectionService extends BaseSectionService
{
    public function key() { return 'operator_sales'; }

    public function build(ReportContext $context)
    {
        $rows = [];
        $totals = $this->emptyAmounts();
        $operatorRows = $this->pumpOperatorRows($context);
        $directRows = $this->directSettlementRows($context);
        // Keep legacy SET-SW support, and also read the new standalone SW
        // module's sw_* settlement tables.
        $swRows = array_merge(
            $this->swSettlementRows($context),
            $this->standaloneSwSettlementRows($context)
        );
        $cashierRows = $this->cashierRows($context);

        // Direct Settlement and Settlement SW save their authoritative payment
        // breakdowns in settlements + settlement_* detail tables.  Some older
        // installations also copied those rows into pump_operator_payments.
        // Prefer the saved settlement source and suppress only the duplicate
        // operator row for the same settlement number.
        $authoritativeSettlementRows = array_merge($directRows, $swRows);
        if ($authoritativeSettlementRows) {
            $savedSettlementNos = [];
            foreach ($authoritativeSettlementRows as $savedRow) {
                $savedSettlementNos[strtolower(trim((string) ($savedRow['settlement_no'] ?? '')))] = true;
            }
            $operatorRows = array_values(array_filter($operatorRows, function ($row) use ($savedSettlementNos) {
                $settlementNo = strtolower(trim((string) ($row['settlement_no'] ?? '')));
                return $settlementNo === '' || !isset($savedSettlementNos[$settlementNo]);
            }));
        }

        foreach (array_merge($operatorRows, $directRows, $swRows, $cashierRows) as $row) {
            // Direct Settlement already stores the authoritative sales total in
            // settlements.total_amount. Customer collections are payments and
            // are deliberately not part of that total.
            if (array_key_exists('_authoritative_total', $row)) {
                $row['total_sale'] = (float) $row['_authoritative_total'];
                unset($row['_authoritative_total']);
            } else {
                $row['total_sale'] = $this->rowTotal($row);
            }

            foreach (array_keys($totals) as $key) {
                $totals[$key] += (float) ($row[$key] ?? 0);
            }
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            return strnatcasecmp($a['operator'] . $a['settlement_no'], $b['operator'] . $b['settlement_no']);
        });

        return ['rows' => $rows, 'totals' => $totals];
    }

    protected function pumpOperatorRows(ReportContext $context)
    {
        // Pump Operator entries are operational/staging records. They must not
        // enter the Management Daily Report until a settlement has actually
        // been saved.
        if (!$this->schema->table('pump_operator_payments') || !$this->schema->table('settlements')) return [];
        if (!$this->schema->column('settlements', 'status')) return [];

        $operatorColumn = $this->schema->firstColumn('pump_operator_payments', ['pump_operator_id', 'pump_operators_id', 'user_id']);
        $paymentSettlementColumn = $this->schema->firstColumn('pump_operator_payments', ['settlement_no', 'settlement_number']);
        $paymentTypeColumn = $this->schema->firstColumn('pump_operator_payments', ['payment_type', 'type']);
        $settlementNumberColumn = $this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number']);
        $settlementDateColumn = $this->schema->firstColumn('settlements', ['transaction_date', 'date', 'created_at']);

        if (!$paymentSettlementColumn || !$paymentTypeColumn || !$settlementDateColumn) return [];

        $amountColumns = [];
        foreach (['net_amount', 'payment_amount', 'amount', 'paid_amount'] as $candidate) {
            if ($this->schema->column('pump_operator_payments', $candidate)) {
                $amountColumns[] = 'pump_operator_payments.' . $candidate;
            }
        }
        if (!$amountColumns) return [];
        $amountExpression = 'COALESCE(' . implode(', ', $amountColumns) . ', 0)';

        $query = TenantConnection::db()->table('pump_operator_payments')
            ->join('settlements', function ($join) use ($paymentSettlementColumn, $settlementNumberColumn) {
                $join->on('settlements.business_id', '=', 'pump_operator_payments.business_id');
                $referenceMatch = 'CAST(pump_operator_payments.' . $paymentSettlementColumn . ' AS CHAR) = CAST(settlements.id AS CHAR)';
                if ($settlementNumberColumn) {
                    $referenceMatch .= ' OR CAST(pump_operator_payments.' . $paymentSettlementColumn . ' AS CHAR) = CAST(settlements.' . $settlementNumberColumn . ' AS CHAR)';
                }
                $join->whereRaw('(' . $referenceMatch . ')');
            })
            ->where('pump_operator_payments.business_id', $context->businessId)
            ->where('settlements.status', 0);

        $query->whereDate('settlements.' . $settlementDateColumn, '>=', $context->startDate)
            ->whereDate('settlements.' . $settlementDateColumn, '<=', $context->endDate);

        if ($context->locationId) {
            if ($this->schema->column('settlements', 'location_id')) {
                $query->where('settlements.location_id', $context->locationId);
            } elseif ($this->schema->column('pump_operator_payments', 'location_id')) {
                $query->where('pump_operator_payments.location_id', $context->locationId);
            }
        }
        if ($context->storeId) {
            if ($this->schema->column('settlements', 'store_id')) {
                $query->where('settlements.store_id', $context->storeId);
            } elseif ($this->schema->column('pump_operator_payments', 'store_id')) {
                $query->where('pump_operator_payments.store_id', $context->storeId);
            }
        }
        if ($context->shiftId && $this->schema->column('pump_operator_payments', 'shift_id')) {
            $query->where('pump_operator_payments.shift_id', $context->shiftId);
        }

        $settlementDisplayExpression = $settlementNumberColumn
            ? "COALESCE(NULLIF(settlements." . $settlementNumberColumn . ", ''), CAST(settlements.id AS CHAR))"
            : 'CAST(settlements.id AS CHAR)';

        $query->select([
            $operatorColumn
                ? DB::raw('pump_operator_payments.' . $operatorColumn . ' AS operator_id')
                : DB::raw('0 AS operator_id'),
            DB::raw($settlementDisplayExpression . ' AS settlement_no'),
            DB::raw('pump_operator_payments.' . $paymentTypeColumn . ' AS payment_type'),
            DB::raw('SUM(' . $amountExpression . ') AS amount'),
        ]);

        if ($operatorColumn) $query->groupBy('pump_operator_payments.' . $operatorColumn);
        $query->groupBy('settlements.id');
        if ($settlementNumberColumn) $query->groupBy('settlements.' . $settlementNumberColumn);
        $query->groupBy('pump_operator_payments.' . $paymentTypeColumn);

        $data = $query->get();
        $names = $this->pumpOperatorNames($data->pluck('operator_id')->filter()->unique()->all());
        $grouped = [];

        foreach ($data as $item) {
            $id = (int) $item->operator_id;
            $settlement = (string) ($item->settlement_no ?: '-');
            $key = 'operator:' . $id . ':' . $settlement;
            if (!isset($grouped[$key])) {
                $grouped[$key] = array_merge([
                    'operator' => $names[$id] ?? ('Pump Operator ' . ($id ?: 'N/A')),
                    'settlement_no' => $settlement,
                    'source' => 'pump_operator',
                ], $this->emptyAmounts());
            }
            $amountKey = $this->normalisePaymentType($item->payment_type);
            if ($amountKey) $grouped[$key][$amountKey] += (float) $item->amount;
        }

        return array_values($grouped);
    }

    /**
     * Direct Settlement does not use pump_operator_payments as its authoritative
     * payment source. It saves into the shared settlements table plus the
     * settlement_* detail tables. Read those saved rows only after status = 0.
     */
    protected function directSettlementRows(ReportContext $context)
    {
        return $this->savedSettlementRows(
            $context,
            'ST%',
            ['SET-SW%', 'PDST%'],
            'direct_settlement',
            false
        );
    }

    /**
     * Settlement SW uses the same shared settlements + settlement_* detail
     * tables, but its settlement numbers are SET-SW*.  It must be read directly
     * because the SW finalization/payment data is not guaranteed to be copied
     * into pump_operator_payments.
     */
    protected function swSettlementRows(ReportContext $context)
    {
        return $this->savedSettlementRows(
            $context,
            'SET-SW%',
            [],
            'settlement_sw',
            true
        );
    }


    /**
     * New standalone SW settlements are stored in sw_settlements /
     * sw_collections instead of the legacy shared settlements tables.  Status 2
     * is the settled/final state.  The header's total_sales is authoritative;
     * collection/allocation rows only provide the column breakdown.
     */
    protected function standaloneSwSettlementRows(ReportContext $context)
    {
        if (!$this->schema->table('sw_settlements')
            || !$this->schema->column('sw_settlements', 'id')
            || !$this->schema->column('sw_settlements', 'business_id')
            || !$this->schema->column('sw_settlements', 'status')) {
            return [];
        }

        $numberColumn = $this->schema->firstColumn('sw_settlements', ['settlement_no', 'settlement_number']);
        $dateColumn = $this->schema->firstColumn('sw_settlements', ['transaction_date', 'date', 'created_at']);
        $operatorColumn = $this->schema->firstColumn('sw_settlements', ['pump_operator_id', 'operator_id']);
        $totalColumn = $this->schema->firstColumn('sw_settlements', ['total_sales', 'total_amount', 'final_total']);
        if (!$numberColumn || !$dateColumn) return [];

        $query = TenantConnection::db()->table('sw_settlements')
            ->where('sw_settlements.business_id', $context->businessId)
            ->where('sw_settlements.status', 2)
            ->whereDate('sw_settlements.' . $dateColumn, '>=', $context->startDate)
            ->whereDate('sw_settlements.' . $dateColumn, '<=', $context->endDate);

        if ($this->schema->column('sw_settlements', 'deleted_at')) {
            $query->whereNull('sw_settlements.deleted_at');
        }
        if ($context->locationId && $this->schema->column('sw_settlements', 'location_id')) {
            $query->where('sw_settlements.location_id', $context->locationId);
        }

        $settlements = $query->select([
            'sw_settlements.id',
            DB::raw('sw_settlements.' . $numberColumn . ' AS settlement_no'),
            $operatorColumn
                ? DB::raw('sw_settlements.' . $operatorColumn . ' AS operator_id')
                : DB::raw('0 AS operator_id'),
            $totalColumn
                ? DB::raw('sw_settlements.' . $totalColumn . ' AS settlement_total')
                : DB::raw('0 AS settlement_total'),
        ])->get();

        if ($settlements->isEmpty()) return [];

        $ids = $settlements->pluck('id')->map(function ($id) { return (int) $id; })->all();
        $names = $this->pumpOperatorNames($settlements->pluck('operator_id')->filter()->unique()->all());

        $rowsById = [];
        foreach ($settlements as $settlement) {
            $id = (int) $settlement->id;
            $operatorId = (int) ($settlement->operator_id ?? 0);
            $rowsById[$id] = array_merge([
                'operator' => $names[$operatorId] ?? ('Pump Operator ' . ($operatorId ?: 'N/A')),
                'settlement_no' => (string) ($settlement->settlement_no ?: $settlement->id),
                'source' => 'sw_standalone',
                '_authoritative_total' => (float) ($settlement->settlement_total ?? 0),
            ], $this->emptyAmounts());
        }

        /*
         * IS2219: line tables are the source of truth. The SW header total is a
         * cache and older/newly-upgraded tenant schemas can have that cache at
         * zero even while the settlement rows were saved correctly. Rebuild the
         * total from meter + other sale + other income + credit sale and use it
         * whenever it is present. This guarantees the SW settlement appears with
         * its actual Total Sale instead of a misleading zero.
         */
        foreach ($this->standaloneSwSalesTotalsBySettlement($ids) as $id => $value) {
            if (isset($rowsById[(int) $id]) && abs((float) $value) > 0.0000001) {
                $rowsById[(int) $id]['_authoritative_total'] = (float) $value;
            }
        }

        // Credit sales are a separate settlement section in the standalone SW
        // module.  Read them before collections so an optional credit_sale
        // collection copy can be ignored instead of counted twice.
        $creditBySettlement = [];
        if ($this->schema->table('sw_settlement_credit_sales')
            && $this->schema->column('sw_settlement_credit_sales', 'settlement_id')) {
            $creditAmount = $this->schema->firstColumn('sw_settlement_credit_sales', ['amount', 'sub_total', 'total_amount']);
            if ($creditAmount) {
                $creditQuery = TenantConnection::db()->table('sw_settlement_credit_sales')
                    ->whereIn('sw_settlement_credit_sales.settlement_id', $ids);
                if ($this->schema->column('sw_settlement_credit_sales', 'deleted_at')) {
                    $creditQuery->whereNull('sw_settlement_credit_sales.deleted_at');
                }

                foreach ($creditQuery->select([
                    'sw_settlement_credit_sales.settlement_id',
                    DB::raw('SUM(ABS(COALESCE(sw_settlement_credit_sales.' . $creditAmount . ',0))) AS amount'),
                ])->groupBy('sw_settlement_credit_sales.settlement_id')->get() as $item) {
                    $id = (int) $item->settlement_id;
                    if (!isset($rowsById[$id])) continue;
                    $value = (float) $item->amount;
                    $rowsById[$id]['credit'] += $value;
                    $creditBySettlement[$id] = abs($value) > 0.0000001;
                }
            }
        }

        if ($this->schema->table('sw_collections')
            && $this->schema->column('sw_collections', 'settlement_id')) {
            $methodColumn = $this->schema->firstColumn('sw_collections', ['payment_method', 'method', 'type']);
            $amountColumn = $this->schema->firstColumn('sw_collections', ['amount', 'payment_amount', 'sub_total']);

            if ($methodColumn && $amountColumn) {
                $collectionQuery = TenantConnection::db()->table('sw_collections')
                    ->whereIn('sw_collections.settlement_id', $ids);
                if ($this->schema->column('sw_collections', 'deleted_at')) {
                    $collectionQuery->whereNull('sw_collections.deleted_at');
                }

                $collections = $collectionQuery->select([
                    'sw_collections.settlement_id',
                    DB::raw('sw_collections.' . $methodColumn . ' AS payment_type'),
                    DB::raw('SUM(ABS(COALESCE(sw_collections.' . $amountColumn . ',0))) AS amount'),
                ])->groupBy('sw_collections.settlement_id', 'sw_collections.' . $methodColumn)->get();

                foreach ($collections as $item) {
                    $id = (int) $item->settlement_id;
                    if (!isset($rowsById[$id])) continue;

                    $amountKey = $this->normalisePaymentType($item->payment_type);
                    if (!$amountKey) continue;

                    // New SW credit sales live in sw_settlement_credit_sales.
                    // If that authoritative section exists for this settlement,
                    // do not also count a display/preload credit_sale collection.
                    if ($amountKey === 'credit' && !empty($creditBySettlement[$id])) {
                        continue;
                    }

                    $rowsById[$id][$amountKey] += (float) $item->amount;
                }
            }
        }

        return array_values($rowsById);
    }

    /** Sum the standalone SW sales detail tables by settlement. */
    protected function standaloneSwSalesTotalsBySettlement(array $settlementIds)
    {
        $totals = [];
        foreach ($settlementIds as $id) {
            $totals[(int) $id] = 0.0;
        }

        foreach (['sw_settlement_lines', 'sw_other_sales', 'sw_other_income', 'sw_settlement_credit_sales'] as $table) {
            if (!$this->schema->table($table)
                || !$this->schema->column($table, 'settlement_id')) {
                continue;
            }

            $amountColumn = $this->schema->firstColumn($table, ['amount', 'sub_total', 'total_amount']);
            if (!$amountColumn) continue;

            $query = TenantConnection::db()->table($table)
                ->whereIn($table . '.settlement_id', $settlementIds);

            if ($this->schema->column($table, 'deleted_at')) {
                $query->whereNull($table . '.deleted_at');
            }

            $items = $query->select([
                $table . '.settlement_id',
                DB::raw('SUM(COALESCE(' . $table . '.' . $amountColumn . ',0)) AS amount'),
            ])->groupBy($table . '.settlement_id')->get();

            foreach ($items as $item) {
                $id = (int) $item->settlement_id;
                if (!array_key_exists($id, $totals)) continue;
                $totals[$id] += (float) $item->amount;
            }
        }

        return $totals;
    }

    /**
     * Shared saved-settlement reader.  Keeping Direct and SW on the same reader
     * prevents the two report paths from drifting apart when payment types or
     * legacy settlement reference formats change.
     */
    protected function savedSettlementRows(ReportContext $context, $includePattern, array $excludePatterns, $source, $useSourceSalesTotal)
    {
        if (!$this->schema->table('settlements') || !$this->schema->column('settlements', 'status')) return [];

        $numberColumn = $this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number']);
        $dateColumn = $this->schema->firstColumn('settlements', ['transaction_date', 'date', 'created_at']);
        $operatorColumn = $this->schema->firstColumn('settlements', ['pump_operator_id', 'operator_id']);
        $totalColumn = $this->schema->firstColumn('settlements', ['total_amount', 'final_total']);
        if (!$numberColumn || !$dateColumn) return [];

        $query = TenantConnection::db()->table('settlements')
            ->where('settlements.business_id', $context->businessId)
            ->where('settlements.status', 0)
            ->where('settlements.' . $numberColumn, 'LIKE', $includePattern)
            ->whereDate('settlements.' . $dateColumn, '>=', $context->startDate)
            ->whereDate('settlements.' . $dateColumn, '<=', $context->endDate);

        foreach ($excludePatterns as $pattern) {
            $query->where('settlements.' . $numberColumn, 'NOT LIKE', $pattern);
        }

        if ($context->locationId && $this->schema->column('settlements', 'location_id')) {
            $query->where('settlements.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('settlements', 'store_id')) {
            $query->where('settlements.store_id', $context->storeId);
        }

        $select = [
            'settlements.id',
            DB::raw('settlements.' . $numberColumn . ' AS settlement_no'),
            $operatorColumn ? DB::raw('settlements.' . $operatorColumn . ' AS operator_id') : DB::raw('0 AS operator_id'),
            $totalColumn ? DB::raw('settlements.' . $totalColumn . ' AS settlement_total') : DB::raw('NULL AS settlement_total'),
        ];
        $settlements = $query->select($select)->get();
        if ($settlements->isEmpty()) return [];

        $ids = $settlements->pluck('id')->map(function ($id) { return (string) $id; })->all();
        $visibleNos = $settlements->pluck('settlement_no')->filter()->map(function ($no) { return (string) $no; })->all();
        $references = array_values(array_unique(array_merge($ids, $visibleNos)));
        $referenceToId = [];
        foreach ($settlements as $settlement) {
            $referenceToId[(string) $settlement->id] = (int) $settlement->id;
            if (!empty($settlement->settlement_no)) {
                $referenceToId[(string) $settlement->settlement_no] = (int) $settlement->id;
            }
        }

        $names = $this->pumpOperatorNames($settlements->pluck('operator_id')->filter()->unique()->all());
        $rowsById = [];
        foreach ($settlements as $settlement) {
            $id = (int) $settlement->id;
            $operatorId = (int) ($settlement->operator_id ?? 0);
            $rowsById[$id] = array_merge([
                'operator' => $names[$operatorId] ?? ('Pump Operator ' . ($operatorId ?: 'N/A')),
                'settlement_no' => (string) ($settlement->settlement_no ?: $settlement->id),
                'source' => $source,
                '_authoritative_total' => $settlement->settlement_total !== null ? (float) $settlement->settlement_total : 0.0,
            ], $this->emptyAmounts());
        }

        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_cash_payments', 'cash', true, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_cheque_payments', 'cheque', true, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_card_payments', 'card', true, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_cash_deposits', 'bank', false, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_credit_sale_payments', 'credit', false, true, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_loan_payments', 'loans', false, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_customer_loans', 'loans', false, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_drawing_payments', 'drawings', false, false, $context->businessId);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_shortage_payments', 'shortage', false, false, $context->businessId, true);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_excess_payments', 'excess', false, false, $context->businessId, true);
        $this->applySettlementDetailTotals($rowsById, $referenceToId, $references, 'settlement_expense_payments', 'expenses', false, false, $context->businessId);

        // Settlement SW's list itself defines sale total as meter sales + other
        // sales + other incomes.  Use those saved rows where available instead
        // of relying on a payment-oriented settlements.total_amount value.
        if ($useSourceSalesTotal) {
            $this->applySettlementSourceSalesTotals($rowsById, $referenceToId, $references, $context->businessId);
        }

        return array_values($rowsById);
    }

    protected function applySettlementSourceSalesTotals(array &$rowsById, array $referenceToId, array $references, $businessId)
    {
        if (!$references) return;

        $totalsById = [];
        foreach (['meter_sales', 'other_sales', 'other_incomes'] as $table) {
            if (!$this->schema->table($table)) continue;

            $settlementColumn = $this->schema->firstColumn($table, ['settlement_no', 'settlement_number', 'settlement_id']);
            $amountColumn = $this->schema->firstColumn($table, ['sub_total', 'amount', 'total_amount']);
            if (!$settlementColumn || !$amountColumn) continue;

            $query = TenantConnection::db()->table($table)
                ->whereIn($table . '.' . $settlementColumn, $references);
            if ($this->schema->column($table, 'business_id')) {
                $query->where($table . '.business_id', (int) $businessId);
            }
            if ($this->schema->column($table, 'deleted_at')) {
                $query->whereNull($table . '.deleted_at');
            }

            $items = $query->select([
                DB::raw($table . '.' . $settlementColumn . ' AS settlement_ref'),
                DB::raw('SUM(' . $table . '.' . $amountColumn . ') AS sales_total'),
            ])->groupBy($table . '.' . $settlementColumn)->get();

            foreach ($items as $item) {
                $ref = (string) $item->settlement_ref;
                if (!isset($referenceToId[$ref])) continue;
                $id = $referenceToId[$ref];
                if (!isset($totalsById[$id])) $totalsById[$id] = 0.0;
                $totalsById[$id] += (float) $item->sales_total;
            }
        }

        foreach ($totalsById as $id => $total) {
            if (isset($rowsById[$id])) {
                $rowsById[$id]['_authoritative_total'] = (float) $total;
            }
        }
    }

    protected function applySettlementDetailTotals(array &$rowsById, array $referenceToId, array $references, $table, $amountKey, $excludeCustomerPayments = false, $netCreditSales = false, $businessId = null, $absoluteAmount = false)
    {
        if (!$references || !$this->schema->table($table)) return;
        $settlementColumn = $this->schema->firstColumn($table, ['settlement_no', 'settlement_number', 'settlement_id']);
        $amountColumn = $this->schema->firstColumn($table, ['amount', 'payment_amount', 'sub_total']);
        if (!$settlementColumn || !$amountColumn) return;

        $query = TenantConnection::db()->table($table)
            ->whereIn($table . '.' . $settlementColumn, $references);
        if ($businessId !== null && $this->schema->column($table, 'business_id')) {
            // Protect legacy visible settlement numbers that may repeat across businesses.
            $query->where($table . '.business_id', (int) $businessId);
        }
        if ($excludeCustomerPayments && $this->schema->column($table, 'customer_payment_id')) {
            $this->excludeTrueCustomerPaymentRows($query, $table, $settlementColumn, $businessId);
        }
        if ($this->schema->column($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        $discountColumn = $netCreditSales && $this->schema->column($table, 'total_discount') ? 'total_discount' : null;
        $amountExpression = $absoluteAmount
            ? 'ABS(' . $table . '.' . $amountColumn . ')'
            : $table . '.' . $amountColumn;
        $select = [
            DB::raw($table . '.' . $settlementColumn . ' AS settlement_ref'),
            DB::raw('SUM(' . $amountExpression . ') AS amount_total'),
        ];
        if ($discountColumn) {
            $select[] = DB::raw('SUM(COALESCE(' . $table . '.' . $discountColumn . ',0)) AS discount_total');
        }
        $items = $query->select($select)->groupBy($table . '.' . $settlementColumn)->get();

        foreach ($items as $item) {
            $ref = (string) $item->settlement_ref;
            if (!isset($referenceToId[$ref])) continue;
            $settlementId = $referenceToId[$ref];
            if (!isset($rowsById[$settlementId])) continue;
            $value = (float) $item->amount_total;
            if ($discountColumn) $value -= (float) ($item->discount_total ?? 0);
            $rowsById[$settlementId][$amountKey] += $value;
        }
    }

    /**
     * A non-null customer_payment_id is not enough to identify a Customer
     * Payment-tab row. Older Petro/Direct code also used that column as a link
     * to pump_operator_payments, which is why valid Direct Settlement cheque
     * and card amounts disappeared from the Management Report.
     *
     * Exclude a row only when customer_payment_id resolves to a real
     * customer_payments record for the same saved settlement. This keeps actual
     * Customer Payment collections out of sales while preserving legacy Direct
     * Settlement Cash/Cheque/Card rows.
     */
    protected function excludeTrueCustomerPaymentRows($query, $table, $settlementColumn, $businessId = null)
    {
        if (!$this->schema->table('customer_payments') || !$this->schema->column('customer_payments', 'id')) {
            // With no Customer Payment source table we cannot safely classify a
            // legacy non-null id, so do not throw away a valid settlement sale.
            return;
        }

        $customerPaymentSettlement = $this->schema->firstColumn('customer_payments', ['settlement_no', 'settlement_id']);
        $settlementNumber = $this->schema->table('settlements')
            ? $this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number'])
            : null;

        $query->where(function ($salesOnly) use ($table, $settlementColumn, $businessId, $customerPaymentSettlement, $settlementNumber) {
            $salesOnly->whereNull($table . '.customer_payment_id')
                ->orWhereNotExists(function ($customerPaymentQuery) use ($table, $settlementColumn, $businessId, $customerPaymentSettlement, $settlementNumber) {
                    $customerPaymentQuery->select(DB::raw(1))
                        ->from('customer_payments as mgmt_customer_payment')
                        ->whereColumn('mgmt_customer_payment.id', $table . '.customer_payment_id');

                    if ($businessId !== null && $this->schema->column('customer_payments', 'business_id')) {
                        $customerPaymentQuery->where('mgmt_customer_payment.business_id', (int) $businessId);
                    }

                    if ($this->schema->column('customer_payments', 'deleted_at')) {
                        $customerPaymentQuery->whereNull('mgmt_customer_payment.deleted_at');
                    }

                    // Resolve both the customer-payment reference and the detail
                    // reference through the same settlements row. This avoids
                    // numeric-id collisions between old pump payment ids and
                    // genuine customer payment ids.
                    if ($customerPaymentSettlement && $this->schema->table('settlements')) {
                        $customerPaymentQuery->join('settlements as mgmt_customer_settlement', function ($join) use ($customerPaymentSettlement, $settlementNumber) {
                            if ($this->schema->column('customer_payments', 'business_id') && $this->schema->column('settlements', 'business_id')) {
                                $join->on('mgmt_customer_settlement.business_id', '=', 'mgmt_customer_payment.business_id');
                            }

                            $customerReference = 'CAST(mgmt_customer_payment.' . $customerPaymentSettlement . ' AS CHAR) = CAST(mgmt_customer_settlement.id AS CHAR)';
                            if ($settlementNumber) {
                                $customerReference .= ' OR CAST(mgmt_customer_payment.' . $customerPaymentSettlement . ' AS CHAR) = CAST(mgmt_customer_settlement.' . $settlementNumber . ' AS CHAR)';
                            }
                            $join->whereRaw('(' . $customerReference . ')');
                        });

                        $detailReference = 'CAST(' . $table . '.' . $settlementColumn . ' AS CHAR) = CAST(mgmt_customer_settlement.id AS CHAR)';
                        if ($settlementNumber) {
                            $detailReference .= ' OR CAST(' . $table . '.' . $settlementColumn . ' AS CHAR) = CAST(mgmt_customer_settlement.' . $settlementNumber . ' AS CHAR)';
                        }
                        $customerPaymentQuery->whereRaw('(' . $detailReference . ')');
                    } elseif ($this->schema->column('customer_payments', 'customer_id') && $this->schema->column($table, 'customer_id')) {
                        // Last-resort legacy discriminator if settlement linking
                        // columns are not available in an older tenant schema.
                        $customerPaymentQuery->whereColumn('mgmt_customer_payment.customer_id', $table . '.customer_id');
                    }
                });
        });
    }

    protected function cashierRows(ReportContext $context)
    {
        if (!$this->schema->table('transactions') || !$this->schema->table('transaction_payments')) return [];
        if (!$this->schema->column('transactions', 'created_by')) return [];

        $method = $this->schema->firstColumn('transaction_payments', ['method', 'payment_method']);
        $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
        if (!$method || !$amount) return [];

        $query = TenantConnection::db()->table('transactions')
            ->join('transaction_payments', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereIn('transactions.type', ['sell', 'pos'])
            ->whereBetween('transactions.transaction_date', [$context->startDate, $context->endDate])
            ->select(
                DB::raw('transactions.created_by AS cashier_id'),
                DB::raw('transaction_payments.' . $method . ' AS payment_type'),
                DB::raw('SUM(transaction_payments.' . $amount . ') AS amount')
            )
            ->groupBy('transactions.created_by', 'transaction_payments.' . $method);

        // Settlement finalization creates accounting/sales transactions with
        // created_by = the saving user. Those are not cashier POS sales and
        // previously produced misleading rows such as "Cashier: Operator - 1".
        if ($this->schema->column('transactions', 'is_settlement')) {
            $query->where(function ($q) {
                $q->whereNull('transactions.is_settlement')->orWhere('transactions.is_settlement', '!=', 1);
            });
        } elseif ($this->schema->column('transactions', 'petro_settlement_id')) {
            $query->whereNull('transactions.petro_settlement_id');
        }

        // Do not treat later customer receipts allocated to an old invoice as
        // cashier sales for the report date.
        if ($this->schema->column('transaction_payments', 'parent_id')) {
            $query->whereNull('transaction_payments.parent_id');
        }
        if ($this->schema->column('transaction_payments', 'paid_in_type')) {
            $query->where(function ($q) {
                $q->whereNull('transaction_payments.paid_in_type')
                    ->orWhereNotIn('transaction_payments.paid_in_type', ['customer_page', 'all_sale_page']);
            });
        }
        if ($this->schema->column('transaction_payments', 'deleted_at')) {
            $query->whereNull('transaction_payments.deleted_at');
        }

        if ($this->schema->column('transactions', 'status')) $query->where('transactions.status', 'final');
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) $query->where('transactions.location_id', $context->locationId);
        if ($context->storeId && $this->schema->column('transactions', 'store_id')) $query->where('transactions.store_id', $context->storeId);

        $data = $query->get();
        $names = $this->userNames($data->pluck('cashier_id')->filter()->unique()->all());
        $grouped = [];
        foreach ($data as $item) {
            $id = (int) $item->cashier_id;
            $key = 'cashier:' . $id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = array_merge([
                    'operator' => 'Cashier: ' . ($names[$id] ?? ('User ' . ($id ?: 'N/A'))),
                    'settlement_no' => 'Sales',
                    'source' => 'cashier',
                ], $this->emptyAmounts());
            }
            $amountKey = $this->normalisePaymentType($item->payment_type);
            if ($amountKey) $grouped[$key][$amountKey] += (float) $item->amount;
        }

        return array_values($grouped);
    }

    protected function emptyAmounts()
    {
        return [
            'cash' => 0.0,
            'cheque' => 0.0,
            'bank' => 0.0,
            'card' => 0.0,
            'credit' => 0.0,
            'loans' => 0.0,
            'drawings' => 0.0,
            'shortage' => 0.0,
            'excess' => 0.0,
            'commission' => 0.0,
            'expenses' => 0.0,
            'total_sale' => 0.0,
        ];
    }

    protected function rowTotal(array $row)
    {
        return (float) $row['cash'] + $row['cheque'] + $row['bank'] + $row['card'] + $row['credit']
            + $row['loans'] + $row['drawings'] + $row['shortage'] + $row['commission'] + $row['expenses']
            - $row['excess'];
    }

    protected function normalisePaymentType($type)
    {
        $type = strtolower(trim((string) $type));
        $map = [
            'cash' => 'cash',
            'cheque' => 'cheque',
            'check' => 'cheque',
            'bank' => 'bank',
            'bank_transfer' => 'bank',
            'bank transfer' => 'bank',
            'cash_deposit' => 'bank',
            'cash deposit' => 'bank',
            'deposit' => 'bank',
            'card' => 'card',
            'credit_card' => 'card',
            'credit' => 'credit',
            'multiple_credit' => 'credit',
            'credit_sale' => 'credit',
            'credit sale' => 'credit',
            'loan' => 'loans',
            'loans' => 'loans',
            'loan_payment' => 'loans',
            'loan payment' => 'loans',
            'loan_to_customer' => 'loans',
            'loan to customer' => 'loans',
            'drawing' => 'drawings',
            'drawings' => 'drawings',
            'owners_drawing' => 'drawings',
            'owner_drawing' => 'drawings',
            'owner drawing' => 'drawings',
            'shortage' => 'shortage',
            'excess' => 'excess',
            'commission' => 'commission',
            'expense' => 'expenses',
            'expenses' => 'expenses',
        ];
        return $map[$type] ?? (strpos($type, 'bank') !== false ? 'bank' : (strpos($type, 'card') !== false ? 'card' : null));
    }

    protected function pumpOperatorNames(array $ids)
    {
        if (!$ids || !$this->schema->table('pump_operators')) return [];
        $name = $this->schema->firstColumn('pump_operators', ['name', 'operator_name']);
        if (!$name) return [];
        return TenantConnection::db()->table('pump_operators')->whereIn('id', $ids)->pluck($name, 'id')->all();
    }

    protected function userNames(array $ids)
    {
        if (!$ids || !$this->schema->table('users')) return [];
        $firstName = $this->schema->column('users', 'first_name');
        $lastName = $this->schema->column('users', 'last_name');
        $username = $this->schema->column('users', 'username');

        if ($firstName || $lastName) {
            $first = $firstName ? "COALESCE(first_name,'')" : "''";
            $last = $lastName ? "COALESCE(last_name,'')" : "''";
            $fallback = $username ? "COALESCE(username,'')" : "''";
            return TenantConnection::db()->table('users')->whereIn('id', $ids)
                ->select('id', DB::raw("COALESCE(NULLIF(TRIM(CONCAT({$first},' ',{$last})),''),{$fallback}) AS full_name"))
                ->pluck('full_name', 'id')->all();
        }

        if ($username) {
            return TenantConnection::db()->table('users')->whereIn('id', $ids)->pluck('username', 'id')->all();
        }
        return [];
    }
}
