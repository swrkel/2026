<?php

namespace Modules\Purchase\Services\Payment;

use Modules\Purchase\Entities\SupplierPayment;

class SupplierPaymentEditService
{
    public function formData($id): array
    {
        return ['payment' => $this->find($id)];
    }

    public function find($id): SupplierPayment
    {
        return SupplierPayment::where('business_id', session('user.business_id'))->findOrFail($id);
    }

    public function update($id, array $data): SupplierPayment
    {
        $payment = $this->find($id);
        $payment->update($data);

        return $payment;
    }
}
