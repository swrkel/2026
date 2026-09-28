<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyPrepaidPackageSale;

class PrepaidPackageUtilizationReport
{
    public function rows()
    {
        return BeautyPrepaidPackageSale::latest()->get();
    }
}
