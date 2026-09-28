<?php

namespace Modules\PetroPDNew\Services\Source;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Read-only Pumper Dashboard-New source contract.
 *
 * Only operational fields are included in the immutable snapshot. Legacy
 * compatibility columns, integration status/error fields and row timestamps
 * are deliberately excluded so a harmless retry or maintenance update cannot
 * invalidate an otherwise unchanged closed shift.
 */
class PoneSourceReader
{
    public function __construct(private PoneSourceContract $contract)
    {
    }

    public function requiredColumns(): array
    {
        return $this->contract->requiredColumns();
    }

    

    public function assertAvailable(): void
    {
        $missingTables = array_values(array_filter(
            (array) config('petropdnew.source.required_tables', []),
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missingTables !== []) {
            throw new RuntimeException(
                'Pumper Dashboard-New source schema is incomplete: ' . implode(', ', $missingTables)
            );
        }

        $missingColumns = [];
        foreach ($this->requiredColumns() as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missingColumns[] = $table . '.' . $column;
                }
            }
        }

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Pumper Dashboard-New source contract is incomplete: ' . implode(', ', $missingColumns)
            );
        }
    }



    public function closedShiftQuery(int $businessId, ?int $locationId = null): Builder
    {
        $this->assertAvailable();

        $query = DB::table('pone_shifts as s')
            ->leftJoin('pone_pd_operators as o', function ($join): void {
                $join->on('o.id', '=', 's.operator_profile_id')
                    ->on('o.business_id', '=', 's.business_id');
            })
            ->leftJoin('pdnew_source_imports as i', function ($join): void {
                $join->on('i.pone_shift_id', '=', 's.id')
                    ->on('i.business_id', '=', 's.business_id');
            })
            ->leftJoin('pone_shift_settlement_references as r', function ($join): void {
                $join->on('r.shift_id', '=', 's.id')
                    ->on('r.business_id', '=', 's.business_id');
            })
            ->where('s.business_id', $businessId)
            ->whereIn('s.status', (array) config('petropdnew.source.closed_statuses', ['closed']))
            ->select([
                's.id', 's.uuid', 's.business_id', 's.location_id',
                's.operator_profile_id', 's.pd_operator_id', 's.shift_number',
                's.opened_at', 's.closed_at', 's.meter_sales_total',
                's.other_sales_total', 's.payments_total', 's.expected_total',
                's.declared_total', 's.shortage_amount', 's.excess_amount',
                's.reconciliation_status',
                'o.display_name as operator_name', 'i.id as source_import_id',
                'i.import_status', 'i.settlement_id',
                'r.id as settlement_reference_id',
                'r.settlement_no as pone_settlement_no',
            ]);

        if ($locationId) {
            $query->where('s.location_id', $locationId);
        }

        return $query;
    }


    public function settlementReference(int $businessId, int $shiftId): ?object
    {
        $this->assertAvailable();

        return DB::table('pone_shift_settlement_references')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->first();
    }

    public function closedShift(int $businessId, int $shiftId): array
    {
        $this->assertAvailable();

        $shift = DB::table('pone_shifts')
            ->select($this->existingColumns('pone_shifts'))
            ->where('business_id', $businessId)
            ->where('id', $shiftId)
            ->whereIn('status', (array) config('petropdnew.source.closed_statuses', ['closed']))
            ->first();

        if (! $shift) {
            throw new RuntimeException(
                'The selected Pumper Dashboard-New shift is not closed or is outside the active business.'
            );
        }

        return (array) $shift;
    }

    public function snapshot(int $businessId, int $shiftId): array
    {
        $this->assertAvailable();
        $shift = $this->closedShift($businessId, $shiftId);

        $operator = DB::table('pone_pd_operators')
            ->select($this->existingColumns('pone_pd_operators'))
            ->where('business_id', $businessId)
            ->where('id', (int) $shift['operator_profile_id'])
            ->first();

        $assignments = $this->rows('pone_pump_assignments', $businessId, 'shift_id', $shiftId);
        $readings = $this->rows('pone_meter_readings', $businessId, 'shift_id', $shiftId);
        $payments = $this->rows('pone_payments', $businessId, 'shift_id', $shiftId);
        $paymentIds = array_column($payments, 'id');

        $cashDenominations = $this->rowsByIds(
            'pone_payment_cash_denominations',
            'payment_id',
            $paymentIds
        );
        $cardLines = $this->rowsByIds(
            'pone_payment_card_lines',
            'payment_id',
            $paymentIds
        );
        $creditSales = $this->rowsByIds(
            'pone_credit_sales',
            'payment_id',
            $paymentIds,
            $businessId
        );
        $creditSaleIds = array_column($creditSales, 'id');
        $creditLines = $this->rowsByIds(
            'pone_credit_sale_lines',
            'credit_sale_id',
            $creditSaleIds
        );

        $otherSales = $this->rows('pone_other_sales', $businessId, 'shift_id', $shiftId);
        $otherSaleLines = $this->rowsByIds(
            'pone_other_sale_lines',
            'other_sale_id',
            array_column($otherSales, 'id')
        );

        $unloads = $this->rows('pone_unload_stocks', $businessId, 'shift_id', $shiftId);
        $unloadLines = $this->rowsByIds(
            'pone_unload_stock_lines',
            'unload_stock_id',
            array_column($unloads, 'id')
        );

        $payload = [
            'contract' => $this->contract->version(),
            'shift' => $shift,
            'operator' => $operator ? (array) $operator : null,
            'assignments' => $assignments,
            'meter_readings' => $readings,
            'payments' => $this->nestPayments(
                $payments,
                $cashDenominations,
                $cardLines,
                $creditSales,
                $creditLines
            ),
            'other_sales' => $this->nest(
                $otherSales,
                $otherSaleLines,
                'id',
                'other_sale_id',
                'lines'
            ),
            'unload_stocks' => $this->nest(
                $unloads,
                $unloadLines,
                'id',
                'unload_stock_id',
                'lines'
            ),
            'day_entries' => $this->rows(
                'pone_day_entries',
                $businessId,
                'shift_id',
                $shiftId
            ),
            'collections' => $this->rows(
                'pone_daily_collections',
                $businessId,
                'shift_id',
                $shiftId
            ),
            'shortage_recoveries' => $this->rows(
                'pone_shortage_recoveries',
                $businessId,
                'shift_id',
                $shiftId
            ),
            'excess_commissions' => $this->rows(
                'pone_excess_commissions',
                $businessId,
                'shift_id',
                $shiftId
            ),
            'ledger_entries' => $this->rows(
                'pone_operator_ledger_entries',
                $businessId,
                'shift_id',
                $shiftId
            ),
        ];

        $payload = $this->canonicalise($payload);
        $payload['source_hash'] = $this->hash($payload);

        return $payload;
    }

    public function hash(array $snapshot): string
    {
        unset($snapshot['source_hash']);

        return hash('sha256', json_encode(
            $this->canonicalise($snapshot),
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
        ) ?: '');
    }

    private function rows(
        string $table,
        int $businessId,
        string $foreignKey,
        int $foreignId
    ): array {
        return DB::table($table)
            ->select($this->existingColumns($table))
            ->where('business_id', $businessId)
            ->where($foreignKey, $foreignId)
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function rowsByIds(
        string $table,
        string $foreignKey,
        array $ids,
        ?int $businessId = null
    ): array {
        if ($ids === []) {
            return [];
        }

        $query = DB::table($table)
            ->select($this->existingColumns($table))
            ->whereIn($foreignKey, array_map('intval', $ids));

        if ($businessId !== null && Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function existingColumns(string $table): array
    {
        $configured = $this->contract->sourceColumns($table);
        $columns = array_values(array_filter(
            $configured,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($columns === []) {
            throw new RuntimeException(
                'No supported Pumper Dashboard-New source columns were found for ' . $table . '.'
            );
        }

        return $columns;
    }

    private function nest(
        array $parents,
        array $children,
        string $parentKey,
        string $childForeignKey,
        string $relationName
    ): array {
        $grouped = [];
        foreach ($children as $child) {
            $grouped[(int) $child[$childForeignKey]][] = $child;
        }

        foreach ($parents as &$parent) {
            $parent[$relationName] = $grouped[(int) $parent[$parentKey]] ?? [];
        }
        unset($parent);

        return $parents;
    }

    private function nestPayments(
        array $payments,
        array $cashDenominations,
        array $cardLines,
        array $creditSales,
        array $creditLines
    ): array {
        $cash = $this->groupBy($cashDenominations, 'payment_id');
        $cards = $this->groupBy($cardLines, 'payment_id');
        $credits = $this->groupBy($creditSales, 'payment_id');
        $lines = $this->groupBy($creditLines, 'credit_sale_id');

        foreach ($payments as &$payment) {
            $id = (int) $payment['id'];
            $payment['cash_denominations'] = $cash[$id] ?? [];
            $payment['card_lines'] = $cards[$id] ?? [];
            $payment['credit_sale'] = null;

            if (! empty($credits[$id])) {
                $credit = $credits[$id][0];
                $credit['lines'] = $lines[(int) $credit['id']] ?? [];
                $payment['credit_sale'] = $credit;
            }
        }
        unset($payment);

        return $payments;
    }

    private function groupBy(array $rows, string $key): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row[$key]][] = $row;
        }

        return $grouped;
    }

    private function canonicalise(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($this->isList($value)) {
            usort($value, static function ($a, $b): int {
                if (is_array($a) && is_array($b) && isset($a['id'], $b['id'])) {
                    return (int) $a['id'] <=> (int) $b['id'];
                }

                return strcmp(json_encode($a) ?: '', json_encode($b) ?: '');
            });

            return array_map(fn ($item) => $this->canonicalise($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalise($item);
        }

        return $value;
    }
    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

}
