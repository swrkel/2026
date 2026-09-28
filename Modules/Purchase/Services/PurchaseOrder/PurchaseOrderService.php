<?php

namespace Modules\Purchase\Services\PurchaseOrder;

use Modules\Purchase\Entities\PurchaseOrder;

class PurchaseOrderService
{
    public function list()
    {
        return PurchaseOrder::query()
            ->where('business_id', session('user.business_id'))
            ->latest('id')
            ->paginate(25);
    }

    public function find($id): PurchaseOrder
    {
        return PurchaseOrder::where('business_id', session('user.business_id'))->findOrFail($id);
    }

    public function store(array $data): PurchaseOrder
    {
        $data['business_id'] = session('user.business_id');
        $data['created_by'] = auth()->id();

        return PurchaseOrder::create($data);
    }

    public function update($id, array $data): PurchaseOrder
    {
        $row = $this->find($id);
        $row->update($data);

        return $row;
    }

    public function delete($id): void
    {
        $this->find($id)->delete();
    }
}
