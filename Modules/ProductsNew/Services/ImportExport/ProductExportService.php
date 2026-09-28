<?php
namespace Modules\ProductsNew\Services\ImportExport;
use Modules\ProductsNew\Entities\ProductsNewProduct;
class ProductExportService
{
    public function csv(array $filters = []): string
    {
        $out = "id,name,sku,barcode,status,created_at\n";
        $query = ProductsNewProduct::query();
        if (!empty($filters['status'])) $query->where('status',$filters['status']);
        foreach ($query->limit(5000)->get() as $p) {
            $out .= implode(',', [
                $p->id,
                $this->clean($p->name ?? $p->product_name ?? ''),
                $this->clean($p->sku ?? ''),
                $this->clean($p->barcode ?? ''),
                $this->clean($p->status ?? ''),
                $p->created_at,
            ])."\n";
        }
        return $out;
    }
    protected function clean($v): string { return '"'.str_replace('"','""',(string)$v).'"'; }
}
