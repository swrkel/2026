<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Modules\ManagementReport\Support\ReportContext;

class OutSectionService extends AddLessSectionService
{
    public function key() { return 'out'; }

    public function build(ReportContext $context)
    {
        $rows = [
            ['label' => 'Expenses', 'amount' => $this->expenseTotal($context)],
            ['label' => 'Excess and commission paid', 'amount' => $this->excessCommissionPaidTotal($context)],
            ['label' => 'Sales returned', 'amount' => $this->transactionDocumentTotal($context, ['sell_return'])],
            ['label' => 'Supplier payments', 'amount' => $this->paymentTotal($context, ['purchase'])],
            ['label' => 'Purchases', 'amount' => $this->transactionDocumentTotal($context, ['purchase'])],
        ];

        return [
            'rows' => $rows,
            // Used by the Total Out footer and review/export integrations.
            'total' => $this->amount(array_sum(array_column($rows, 'amount'))),
        ];
    }
}
