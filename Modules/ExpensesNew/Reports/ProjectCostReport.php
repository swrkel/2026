<?php
namespace Modules\ExpensesNew\Reports;
class ProjectCostReport
{
    public function title(): string { return 'ProjectCostReport'; }
    public function columns(): array { return ['Date','Business','Location','Category','Amount','Status']; }
    public function data(array $filters = []): array { return []; }
}
