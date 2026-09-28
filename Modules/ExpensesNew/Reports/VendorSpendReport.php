<?php
namespace Modules\ExpensesNew\Reports;
class VendorSpendReport
{
    public function title(): string { return 'VendorSpendReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
