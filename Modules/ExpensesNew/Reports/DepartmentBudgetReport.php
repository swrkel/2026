<?php
namespace Modules\ExpensesNew\Reports;
class DepartmentBudgetReport
{
    public function title(): string { return 'DepartmentBudgetReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
