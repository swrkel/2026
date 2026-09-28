<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Modules\ManagementReport\Support\ReportContext;

class FinancialStatusTwoSectionService extends BaseSectionService
{
    protected $financial;
    protected $outstanding;
    protected $stock;

    public function __construct(
        \Modules\ManagementReport\Support\SchemaGuard $schema,
        FinancialStatusSectionService $financial,
        OutstandingSectionService $outstanding,
        StockValueSectionService $stock
    ) {
        parent::__construct($schema);
        $this->financial = $financial;
        $this->outstanding = $outstanding;
        $this->stock = $stock;
    }

    public function key() { return 'financial_status_two'; }

    public function build(ReportContext $context)
    {
        $financial = $this->financial->build($context);
        $outstanding = $this->outstanding->build($context);
        $stock = $this->stock->build($context);

        $balances = [];
        foreach ($financial['rows'] as $row) {
            $balances[$row['key']] = (float) $row['balance'];
        }
        $outstandings = [];
        foreach ($outstanding['rows'] as $row) {
            $outstandings[$row['key']] = (float) $row['balance'];
        }

        $liquidAssets = ($balances['cash'] ?? 0) + ($balances['customer_cheques'] ?? 0) + ($balances['banks'] ?? 0) + ($balances['card'] ?? 0);
        $customerOutstanding = $outstandings['customer'] ?? 0;
        $stockValue = (float) ($stock['total'] ?? 0);
        $supplierOutstanding = $outstandings['supplier'] ?? 0;
        $accountPayable = $balances['account_payable'] ?? 0;
        $totalAssets = $liquidAssets + $customerOutstanding + $stockValue;
        $totalLiabilities = $supplierOutstanding + $accountPayable;

        return [
            'rows' => [
                ['label' => 'Liquid Assets', 'type' => 'asset', 'amount' => $liquidAssets],
                ['label' => 'Customer Outstanding', 'type' => 'asset', 'amount' => $customerOutstanding],
                ['label' => 'Stock Value as at Period End', 'type' => 'asset', 'amount' => $stockValue],
                ['label' => 'Supplier Outstanding', 'type' => 'liability', 'amount' => $supplierOutstanding],
                ['label' => 'Account Payable', 'type' => 'liability', 'amount' => $accountPayable],
            ],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'net_working_position' => $totalAssets - $totalLiabilities,
        ];
    }
}
