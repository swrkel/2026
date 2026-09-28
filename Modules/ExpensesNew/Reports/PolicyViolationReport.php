<?php
namespace Modules\ExpensesNew\Reports;
class PolicyViolationReport
{
    public function title(): string { return 'PolicyViolationReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
