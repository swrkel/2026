<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Modules\ManagementReport\Support\ReportContext;

class TotalAddSectionService extends AddLessSectionService
{
    public function key() { return 'total_add'; }

    public function build(ReportContext $context)
    {
        $rows = [
            ['label' => 'Customer Payments Received', 'amount' => $this->customerPaymentsReceived($context)],
            ['label' => 'Shortage Recovered', 'amount' => $this->shortageRecoveredTotal($context)],
            ['label' => 'Withdraw Cash From Bank', 'amount' => $this->bankWithdrawalTotal($context)],
            ['label' => 'Purchase Returned', 'amount' => $this->transactionDocumentTotal($context, ['purchase_return'])],
        ];

        return [
            'rows' => $rows,
            'total' => $this->amount(array_sum(array_column($rows, 'amount'))),
        ];
    }
}
