<?php
namespace Modules\ExpensesNew\Reports;
class TaxSummaryReport
{
    public function title(): string { return 'TaxSummaryReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
