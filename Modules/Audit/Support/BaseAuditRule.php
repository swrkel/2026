<?php
namespace Modules\Audit\Support;

use Illuminate\Support\Facades\Schema;
use Modules\Audit\Contracts\AuditRule;
use Modules\Audit\Services\AuditContext;

abstract class BaseAuditRule implements AuditRule
{
    protected $module = 'System';
    protected $severity = 'warning';
    protected $description = '';

    public function module(): string { return $this->module; }
    public function defaultSeverity(): string { return $this->severity; }
    public function description(): string { return $this->description; }
    public function supports(AuditContext $context): bool { return true; }

    protected function tableExists(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function hasColumns(string $table, array $columns): bool
    {
        if (!$this->tableExists($table)) return false;
        foreach ($columns as $column) {
            try { if (!Schema::hasColumn($table, $column)) return false; }
            catch (\Throwable $e) { return false; }
        }
        return true;
    }

    protected function scopeFinding(array $finding, $businessId = null, $locationId = null): array
    {
        if ($businessId !== null && $businessId !== '') {
            $finding['business_id'] = $businessId;
        }
        if ($locationId !== null && $locationId !== '') {
            $finding['location_id'] = $locationId;
        }
        return $finding;
    }

    protected function finding($sourceTable, $sourceId, $title, $message, $expected = null, $actual = null, array $payload = [], $severity = null): array
    {
        return [
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'title' => $title,
            'message' => $message,
            'expected' => $expected,
            'actual' => $actual,
            'payload' => $payload,
            'severity' => $severity ?: $this->defaultSeverity(),
        ];
    }
}
