<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthConsultation;

class MyHealthConsultationNumberService
{
    public function nextNumber(): string
    {
        do {
            $number = 'MHC' . date('ymd') . random_int(1000, 9999);
        } while (MyHealthConsultation::where('consultation_no', $number)->exists());

        return $number;
    }

    public function nextDoctorCode(): string
    {
        return 'MHD' . date('ym') . random_int(10000, 99999);
    }
}
