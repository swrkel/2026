<?php

namespace Modules\BankingTellerOperations\Services;

class CashMovementService
{
    public function record(array $data): \Modules\BankingTellerOperations\Entities\CashMovement
    {
        return \Modules\BankingTellerOperations\Entities\CashMovement::create($data);
    }
}
