<?php
namespace Modules\ExpensesNew\Reports;
class DuplicateExpenseReport
{
    public function title(): string { return 'DuplicateExpenseReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
