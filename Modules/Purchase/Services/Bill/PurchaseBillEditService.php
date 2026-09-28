<?php

namespace Modules\Purchase\Services\Bill;

use Modules\Purchase\Entities\PurchaseBill;

class PurchaseBillEditService
{
    public function formData($id): array
    {
        return ['bill' => $this->find($id)];
    }

    public function find($id): PurchaseBill
    {
        return PurchaseBill::where('business_id', session('user.business_id'))
            ->where('type', 'purchase')
            ->findOrFail($id);
    }

    public function update($id, array $data): PurchaseBill
    {
        $bill = $this->find($id);
        $bill->update($data);

        return $bill;
    }
}
