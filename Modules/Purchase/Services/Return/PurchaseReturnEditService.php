<?php

namespace Modules\Purchase\Services\Return;

use Modules\Purchase\Entities\PurchaseReturn;

class PurchaseReturnEditService
{
    public function formData($id): array { return ['return' => $this->find($id)]; }

    public function find($id): PurchaseReturn
    {
        return PurchaseReturn::where('business_id', session('user.business_id'))->where('type', 'purchase_return')->findOrFail($id);
    }

    public function update($id, array $data): PurchaseReturn
    {
        $row = $this->find($id);
        $row->update($data);
        return $row;
    }
}
