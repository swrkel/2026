<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthLabRequest;

class MyHealthLabNumberService
{
    public function nextLabRequestNo(): string
    {
        do {
            $number = 'MHL' . date('ymd') . random_int(1000, 9999);
        } while (MyHealthLabRequest::where('lab_request_no', $number)->exists());

        return $number;
    }
}
