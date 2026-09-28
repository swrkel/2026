<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collection Summary for one shift.
 *
 * Operators down, payment types across, totals both ways.
 *
 * Everything is read from the daily tabs' own tables, so the summary always
 * agrees with what those tabs show. A stored total would not.
 */
class CollectionSummaryService
{
    /** The columns, in the order they are shown. */
    public const TYPES = ['cash', 'card', 'cheque', 'credit'];

    public function summary(int $shiftId): array
    {
        $operators = $this->operatorsOnShift($shiftId);

        $byType = [
            'cash' => $this->fromTable('sw_daily_cash', $shiftId, 'current_amount'),
            'card' => $this->fromTable('sw_daily_cards', $shiftId, 'amount'),
            'cheque' => $this->fromTable('sw_daily_cheques', $shiftId, 'amount'),
            'credit' => $this->fromTable('sw_daily_credit_sales', $shiftId, 'amount'),
        ];

        /*
         | Every operator who appears anywhere gets a row, not only those
         | assigned to the shift.
         |
         | An entry recorded against an operator who was later unassigned would
         | otherwise vanish from the summary while still counting in the total -
         | and a summary whose rows do not add up to its own total is worse than
         | no summary.
        */
        foreach ($byType as $rows) {
            foreach (array_keys($rows) as $operatorId) {
                if (! isset($operators[$operatorId])) {
                    $operators[$operatorId] = $this->operatorName($operatorId);
                }
            }
        }

        $matrix = [];
        $columnTotals = array_fill_keys(self::TYPES, 0.0);
        $grand = 0.0;

        foreach ($operators as $operatorId => $name) {
            $row = ['operator' => $name, 'amounts' => [], 'total' => 0.0];

            foreach (self::TYPES as $type) {
                $amount = round((float) ($byType[$type][$operatorId] ?? 0), 2);
                $row['amounts'][$type] = $amount;
                $row['total'] += $amount;
                $columnTotals[$type] += $amount;
            }

            $row['total'] = round($row['total'], 2);
            $grand += $row['total'];
            $matrix[] = $row;
        }

        // Largest first: the operator who took the most is the one anyone
        // reconciling looks at first.
        usort($matrix, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'rows' => $matrix,
            'column_totals' => array_map(fn ($v) => round($v, 2), $columnTotals),
            'grand_total' => round($grand, 2),
        ];
    }

    /**
     * One payment type's amounts, keyed by operator.
     *
     * The amount column differs per table - daily cash counts the amount
     * actually handed over, credit sales the invoice total - so it is named
     * rather than assumed.
     */
    protected function fromTable(string $table, int $shiftId, string $amountColumn): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $amountColumn)) {
            return [];
        }

        return DB::table($table)
            ->where('sw_shift_id', $shiftId)
            ->whereNotNull('pump_operator_id')
            ->groupBy('pump_operator_id')
            ->pluck(DB::raw('SUM(' . $amountColumn . ')'), 'pump_operator_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    protected function operatorsOnShift(int $shiftId): array
    {
        return DB::table('sw_shift_operators as so')
            ->join('pump_operators as po', 'po.id', '=', 'so.pump_operator_id')
            ->where('so.sw_shift_id', $shiftId)
            ->orderBy('po.name')
            ->pluck('po.name', 'po.id')
            ->all();
    }

    protected function operatorName(int $operatorId): string
    {
        return (string) (DB::table('pump_operators')->where('id', $operatorId)->value('name')
            ?: '#' . $operatorId);
    }
}
