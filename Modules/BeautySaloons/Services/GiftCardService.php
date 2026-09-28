<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyGiftCard;

class GiftCardService
{
    public function create(array $data): BeautyGiftCard
    {
        $data['status'] = $data['status'] ?? 'active';
        $data['balance_amount'] = $data['balance_amount'] ?? $data['original_amount'];
        return BeautyGiftCard::create($data);
    }
}
