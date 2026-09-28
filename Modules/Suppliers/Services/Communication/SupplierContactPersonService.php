<?php

namespace Modules\Suppliers\Services\Communication;

use Illuminate\Support\Facades\DB;
use Modules\Suppliers\Entities\Supplier;

class SupplierContactPersonService
{
    protected function businessId(): ?int
    {
        return \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
    }

    public function list(Supplier $supplier)
    {
        // Safe placeholder collection until dedicated supplier communication tables are finalized.
        // Kept inside module so legacy main-system contacts/files are not required by the UI.
        return collect();
    }

    public function store(Supplier $supplier, array $data)
    {
        return true;
    }

    public function update(Supplier $supplier, int $id, array $data)
    {
        return true;
    }

    public function delete(Supplier $supplier, int $id)
    {
        return true;
    }
}
