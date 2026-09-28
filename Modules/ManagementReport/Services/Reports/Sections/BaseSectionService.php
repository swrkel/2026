<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Carbon\Carbon;
use Modules\ManagementReport\Services\Reports\Contracts\SectionService;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\SchemaGuard;

abstract class BaseSectionService implements SectionService
{
    protected $schema;

    public function __construct(SchemaGuard $schema)
    {
        $this->schema = $schema;
    }

    protected function zero()
    {
        return 0.0;
    }

    protected function amount($value)
    {
        return round((float) $value, 4);
    }

    protected function dateColumn($table)
    {
        return $this->schema->firstColumn($table, ['transaction_date', 'date', 'created_at', 'date_and_time']);
    }

    protected function applyDate($query, $table, ReportContext $context, $column = null)
    {
        $column = $column ?: $this->dateColumn($table);
        if ($column) {
            $query->whereBetween($table . '.' . $column, [$context->startDate, $context->endDate]);
        }
        return $query;
    }

    protected function applyBusinessScope($query, $table, ReportContext $context)
    {
        if ($this->schema->column($table, 'business_id')) {
            $query->where($table . '.business_id', $context->businessId);
        }
        if ($context->locationId && $this->schema->column($table, 'location_id')) {
            $query->where($table . '.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column($table, 'store_id')) {
            $query->where($table . '.store_id', $context->storeId);
        }
        if ($context->shiftId && $this->schema->column($table, 'shift_id')) {
            $query->where($table . '.shift_id', $context->shiftId);
        }
        return $query;
    }

    protected function sum($table, $column, ReportContext $context, callable $callback = null, $dateColumn = null)
    {
        if (!$this->schema->table($table) || !$this->schema->column($table, $column)) {
            return 0.0;
        }
        $query = TenantConnection::db()->table($table);
        $this->applyBusinessScope($query, $table, $context);
        $this->applyDate($query, $table, $context, $dateColumn);
        if ($callback) {
            $callback($query);
        }
        return $this->amount($query->sum($table . '.' . $column));
    }

    protected function transactionSum(ReportContext $context, array $types, $column = 'final_total')
    {
        if (!$this->schema->table('transactions') || !$this->schema->column('transactions', $column)) {
            return 0.0;
        }
        $query = TenantConnection::db()->table('transactions');
        $this->applyBusinessScope($query, 'transactions', $context);
        $this->applyDate($query, 'transactions', $context, 'transaction_date');
        if ($this->schema->column('transactions', 'type')) {
            $query->whereIn('transactions.type', $types);
        }
        if ($this->schema->column('transactions', 'status')) {
            $query->where('transactions.status', 'final');
        }
        return $this->amount($query->sum('transactions.' . $column));
    }

    protected function label($key, $fallback)
    {
        return trans('managementreport::lang.' . $key) === 'managementreport::lang.' . $key
            ? $fallback
            : trans('managementreport::lang.' . $key);
    }
}
