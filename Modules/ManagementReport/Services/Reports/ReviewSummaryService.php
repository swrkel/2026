<?php
namespace Modules\ManagementReport\Services\Reports;

class ReviewSummaryService
{
    public function build(array $sections)
    {
        $sales = (float) data_get($sections, 'sales.payload.total', 0);
        $stock = (float) data_get($sections, 'stock_value.payload.total', 0);
        $outstanding = (float) data_get($sections, 'outstanding.payload.total', 0);
        $shortage = (float) data_get($sections, 'pump_variance.payload.shortage_total', 0);
        $excess = (float) data_get($sections, 'pump_variance.payload.excess_total', 0);
        $dip = (float) data_get($sections, 'dip_details.payload.difference_total', 0);

        return [
            ['key' => 'sales', 'label' => 'Net Sales', 'value' => $sales, 'status' => $sales >= 0 ? 'ok' : 'attention'],
            ['key' => 'stock', 'label' => 'Stock Value', 'value' => $stock, 'status' => $stock >= 0 ? 'ok' : 'attention'],
            ['key' => 'outstanding', 'label' => 'Outstanding', 'value' => $outstanding, 'status' => $outstanding > 0 ? 'attention' : 'ok'],
            ['key' => 'shortage', 'label' => 'Shortage', 'value' => $shortage, 'status' => $shortage > 0 ? 'attention' : 'ok'],
            ['key' => 'excess', 'label' => 'Excess', 'value' => $excess, 'status' => 'info'],
            ['key' => 'dip_difference', 'label' => 'Dip Difference', 'value' => $dip, 'status' => abs($dip) > 0.001 ? 'attention' : 'ok'],
        ];
    }
}
