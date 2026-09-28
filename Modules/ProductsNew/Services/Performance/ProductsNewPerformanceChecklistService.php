<?php
namespace Modules\ProductsNew\Services\Performance;

class ProductsNewPerformanceChecklistService
{
    public function checklist(): array
    {
        return [
            ['item'=>'Product list', 'action'=>'Use server-side filtering, pagination, and selected columns only.', 'status'=>'applied'],
            ['item'=>'Dashboard KPIs', 'action'=>'Snapshot table introduced to avoid expensive repeated counts.', 'status'=>'applied'],
            ['item'=>'Reports', 'action'=>'Each report has a separate service and filter service for targeted queries.', 'status'=>'applied'],
            ['item'=>'Stock centre', 'action'=>'Movement summaries and branch filters are isolated in StockCenterService.', 'status'=>'applied'],
            ['item'=>'Imports', 'action'=>'Import lines are staged and validated before commit.', 'status'=>'applied'],
            ['item'=>'Barcode/media', 'action'=>'Queue/template tables avoid blocking page load.', 'status'=>'applied'],
        ];
    }
}
