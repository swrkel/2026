<?php
namespace Modules\ProductsNew\Services\ImportExport;
use Modules\ProductsNew\Entities\ProductsNewDataCleanupTask;
class ProductDataCleanupService
{
    public function createTask(string $type, array $payload = []): ProductsNewDataCleanupTask
    {
        return ProductsNewDataCleanupTask::create([
            'task_type'=>$type,
            'payload_json'=>json_encode($payload),
            'status'=>'pending',
            'created_by'=>auth()->id(),
        ]);
    }
    public function availableTasks(): array
    {
        return [
            'missing_category'=>'Find products without category',
            'missing_barcode'=>'Find products without barcode',
            'missing_price'=>'Find products without selling price',
            'duplicate_sku'=>'Find duplicate SKU records',
            'inactive_with_stock'=>'Find inactive products with stock',
        ];
    }
}
