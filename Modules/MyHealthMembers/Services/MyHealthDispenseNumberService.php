<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthDispense;

class MyHealthDispenseNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthDispense::max('id');
        return 'DSP' . date('Ymd') . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
