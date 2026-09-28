<?php
namespace Modules\Audit\Services\Adapters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Services\AuditContext;

abstract class AbstractTableAdapter
{
    protected function firstTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            try { if (Schema::hasTable($table)) return $table; } catch (\Throwable $e) {}
        }
        return null;
    }

    public function hasColumn(?string $table, string $column): bool
    {
        if (!$table) return false;
        try { return Schema::hasColumn($table, $column); } catch (\Throwable $e) { return false; }
    }

    public function scopeContext($query, ?string $table, AuditContext $context, ?string $qualifier = null)
    {
        // `$table` is the physical table used for schema inspection. `$qualifier` is
        // the SQL table name/alias used by the current query. They are deliberately
        // separate because MySQL no longer accepts the original table name after
        // `FROM table AS alias`. Browser runs normally include a business filter, so
        // using the physical table name against an aliased query caused safe rule
        // failures such as `Unknown column transactions.business_id in WHERE`.
        $qualifier = $qualifier ?: $table;

        if ($context->businessId && $this->hasColumn($table, 'business_id')) {
            $query->where($qualifier.'.business_id', $context->businessId);
        }
        if ($context->locationId && $this->hasColumn($table, 'location_id')) {
            $query->where($qualifier.'.location_id', $context->locationId);
        }

        return $query;
    }
}
