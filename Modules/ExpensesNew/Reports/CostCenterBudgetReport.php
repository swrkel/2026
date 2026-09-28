<?php
namespace Modules\ExpensesNew\Reports;
class CostCenterBudgetReport
{
    public function title(): string { return 'CostCenterBudgetReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
