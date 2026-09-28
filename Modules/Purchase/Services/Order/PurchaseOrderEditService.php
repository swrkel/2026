<?php

namespace Modules\Purchase\Services\Order;

use Modules\Purchase\Entities\PurchaseTransaction;

class PurchaseOrderEditService
{
    public function formData($id): array
    {
        return ['order' => $this->find($id)];
    }

    public function find($id): PurchaseTransaction
    {
        return PurchaseTransaction::where('business_id', session('user.business_id'))
            ->where('type', 'purchase_order')
            ->findOrFail($id);
    }

    public function update($id, array $data): PurchaseTransaction
    {
        $order = $this->find($id);
        $order->update($data);
        return $order;
    }
}
