<?php

namespace Modules\Purchase\Services\Bill;

use Modules\Purchase\Entities\PurchaseBill;
use Modules\Purchase\Utils\PurchaseBillNumberGenerator;

class PurchaseBillCreateService
{
    public function formData(): array
    {
        return ['transaction_date' => now()->format('Y-m-d')];
    }

    public function store(array $data): PurchaseBill
    {
        $data['business_id'] = session('user.business_id');
        $data['created_by'] = auth()->id();
        $data['type'] = 'purchase';
        $data['ref_no'] = $data['ref_no'] ?? app(PurchaseBillNumberGenerator::class)->next((int) $data['business_id']);

        return PurchaseBill::create($data);
    }
}
