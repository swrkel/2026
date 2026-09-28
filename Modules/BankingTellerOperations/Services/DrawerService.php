<?php

namespace Modules\BankingTellerOperations\Services;

class DrawerService
{
    public function open(array $data): \Modules\BankingTellerOperations\Entities\TellerDrawer
    {
        $data['status'] = $data['status'] ?? 'open';
        $data['business_date'] = $data['business_date'] ?? now()->toDateString();
        return \Modules\BankingTellerOperations\Entities\TellerDrawer::create($data);
    }

    public function close(\Modules\BankingTellerOperations\Entities\TellerDrawer $drawer, array $data): \Modules\BankingTellerOperations\Entities\TellerDrawer
    {
        $physical = (float)($data['physical_cash'] ?? 0);
        $system = (float)$drawer->system_cash;
        $drawer->fill($data);
        $drawer->cash_difference = $physical - $system;
        $drawer->status = 'closed';
        $drawer->save();
        return $drawer;
    }
}
