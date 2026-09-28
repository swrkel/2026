<?php
namespace Modules\ExpensesNew\Reports;
class MonthlyClosingExpenseReport
{
    public function title(): string { return 'MonthlyClosingExpenseReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
