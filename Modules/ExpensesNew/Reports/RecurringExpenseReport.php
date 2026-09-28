<?php
namespace Modules\ExpensesNew\Reports;
class RecurringExpenseReport
{
    public function title(): string { return 'RecurringExpenseReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
