<?php

namespace Modules\Suppliers\Services\Communication;

use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;

class SupplierDocumentService
{
    protected function businessId(): ?int
    {
        return \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
    }

    public function list(Supplier $supplier)
    {
        return collect();
    }

    public function store(Supplier $supplier, Request $request)
    {
        return true;
    }

    public function delete(Supplier $supplier, int $id)
    {
        return true;
    }
}
