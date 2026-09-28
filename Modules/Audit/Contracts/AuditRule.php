<?php
namespace Modules\Audit\Contracts;

use Modules\Audit\Services\AuditContext;

interface AuditRule
{
    public function code(): string;
    public function module(): string;
    public function title(): string;
    public function description(): string;
    public function defaultSeverity(): string;
    public function supports(AuditContext $context): bool;

    /**
     * Return a list of findings. Each item may contain:
     * source_table, source_id, title, message, expected, actual,
     * severity, payload.
     */
    public function run(AuditContext $context): array;
}
