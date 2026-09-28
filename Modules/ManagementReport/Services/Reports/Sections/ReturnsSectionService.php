<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Modules\ManagementReport\Support\ReportContext;

class ReturnsSectionService extends BaseSectionService
{
    public function key() { return 'returns'; }
    public function build(ReportContext $context)
    {
        $sales = $this->transactionSum($context, ['sell_return'], 'final_total');
        $purchase = $this->transactionSum($context, ['purchase_return'], 'final_total');
        return [
            'rows' => [
                ['label' => 'Sales Return', 'amount' => $sales],
                ['label' => 'Purchase Return', 'amount' => $purchase],
            ],
            'sales_return' => $sales,
            'purchase_return' => $purchase,
        ];
    }
}
