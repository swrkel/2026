<?php

namespace Modules\Purchase\Services\Payment;

use Modules\Purchase\Entities\SupplierPayment;

class SupplierPaymentCreateService
{
    public function formData(): array
    {
        return ['paid_on' => now()->format('Y-m-d')];
    }

    public function store(array $data): SupplierPayment
    {
        $data['business_id'] = session('user.business_id');
        $data['created_by'] = auth()->id();

        return SupplierPayment::create($data);
    }
}
