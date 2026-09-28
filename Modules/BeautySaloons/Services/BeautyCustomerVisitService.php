<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyCustomerVisitNote;

class BeautyCustomerVisitService
{
    public function addNote(array $data): BeautyCustomerVisitNote
    {
        return BeautyCustomerVisitNote::create($data);
    }
}
