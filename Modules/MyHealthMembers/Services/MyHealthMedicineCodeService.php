<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthMedicine;

class MyHealthMedicineCodeService
{
    public function nextCode(): string
    {
        $lastId = (int) MyHealthMedicine::max('id');
        return 'MED' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }
}
