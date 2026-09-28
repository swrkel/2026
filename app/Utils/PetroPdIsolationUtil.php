<?php

namespace App\Utils;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * One shared eligibility rule for records reserved exclusively for Petro PD.
 *
 * Petro PD may use all shared operators and pumps. Every other settlement
 * module must pass its queries and submitted IDs through this utility.
 */
class PetroPdIsolationUtil
{
    private static array $columnCache = [];

    public static function supportsOperators(): bool
    {
        return self::hasColumn('pump_operators', 'is_petro_pd_only');
    }

    public static function supportsPumps(): bool
    {
        return self::hasColumn('pumps', 'is_petro_pd_only');
    }

    public static function excludeOperators($query, string $qualifiedColumn = 'pump_operators.is_petro_pd_only')
    {
        if (self::supportsOperators()) {
            $query->where(function ($allowed) use ($qualifiedColumn) {
                $allowed->whereNull($qualifiedColumn)->orWhere($qualifiedColumn, 0);
            });
        }

        return $query;
    }

    public static function excludePumps($query, string $qualifiedColumn = 'pumps.is_petro_pd_only')
    {
        if (self::supportsPumps()) {
            $query->where(function ($allowed) use ($qualifiedColumn) {
                $allowed->whereNull($qualifiedColumn)->orWhere($qualifiedColumn, 0);
            });
        }

        return $query;
    }

    public static function excludeByOperatorId($query, string $operatorIdColumn)
    {
        if (self::supportsOperators()) {
            $query->whereNotExists(function (QueryBuilder $blocked) use ($operatorIdColumn) {
                $blocked->selectRaw('1')
                    ->from('pump_operators as petro_pd_only_operators')
                    ->whereColumn('petro_pd_only_operators.id', $operatorIdColumn)
                    ->where('petro_pd_only_operators.is_petro_pd_only', 1);
            });
        }

        return $query;
    }

    public static function excludeByPumpId($query, string $pumpIdColumn)
    {
        if (self::supportsPumps()) {
            $query->whereNotExists(function (QueryBuilder $blocked) use ($pumpIdColumn) {
                $blocked->selectRaw('1')
                    ->from('pumps as petro_pd_only_pumps')
                    ->whereColumn('petro_pd_only_pumps.id', $pumpIdColumn)
                    ->where('petro_pd_only_pumps.is_petro_pd_only', 1);
            });
        }

        return $query;
    }

    public static function excludeSettlements(
        $query,
        string $settlementIdColumn = 'settlements.id',
        string $operatorIdColumn = 'settlements.pump_operator_id',
        string $settlementNoColumn = 'settlements.settlement_no'
    )
    {
        self::excludeByOperatorId($query, $operatorIdColumn);

        if (self::supportsPumps()) {
            $query->whereNotExists(function (QueryBuilder $blocked) use ($settlementIdColumn) {
                $blocked->selectRaw('1')
                    ->from('pump_operator_assignments as petro_pd_only_assignments')
                    ->join('pumps as petro_pd_only_settlement_pumps', 'petro_pd_only_settlement_pumps.id', '=', 'petro_pd_only_assignments.pump_id')
                    ->whereColumn('petro_pd_only_assignments.settlement_id', $settlementIdColumn)
                    ->where('petro_pd_only_settlement_pumps.is_petro_pd_only', 1);
            });

            $query->whereNotExists(function (QueryBuilder $blocked) use ($settlementIdColumn, $settlementNoColumn) {
                $blocked->selectRaw('1')
                    ->from('meter_sales as petro_pd_only_meter_sales')
                    ->join('pumps as petro_pd_only_meter_pumps', 'petro_pd_only_meter_pumps.id', '=', 'petro_pd_only_meter_sales.pump_id')
                    ->where(function ($linked) use ($settlementIdColumn, $settlementNoColumn) {
                        $linked->whereColumn('petro_pd_only_meter_sales.settlement_no', $settlementIdColumn)
                            ->orWhereColumn('petro_pd_only_meter_sales.settlement_no', $settlementNoColumn);
                    })
                    ->where('petro_pd_only_meter_pumps.is_petro_pd_only', 1);
            });
        }

        return $query;
    }

    public static function assertAllowedOutsidePetroPd(int $businessId, $operatorId = null, array $pumpIds = []): void
    {
        if ($operatorId && self::supportsOperators()) {
            $blockedOperator = DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->where('id', $operatorId)
                ->where('is_petro_pd_only', 1)
                ->exists();

            if ($blockedOperator) {
                throw ValidationException::withMessages([
                    'pump_operator_id' => 'This pump operator is reserved for Petro PD and cannot be used in this settlement.',
                ]);
            }
        }

        $pumpIds = array_values(array_unique(array_filter(array_map('intval', $pumpIds))));
        if ($pumpIds && self::supportsPumps()) {
            $blockedPump = DB::table('pumps')
                ->where('business_id', $businessId)
                ->whereIn('id', $pumpIds)
                ->where('is_petro_pd_only', 1)
                ->exists();

            if ($blockedPump) {
                throw ValidationException::withMessages([
                    'pump_id' => 'One or more selected pumps are reserved for Petro PD and cannot be used in this settlement.',
                ]);
            }
        }
    }

    private static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }
}
