<?php

namespace Modules\Leasing\Services;

use Modules\Leasing\Models\LeaseAsset;
use Modules\Leasing\Models\LeaseContract;

class LeasingNumberService
{
    public function nextLeaseContractNo()
    {
        $next = (LeaseContract::max('id') ?: 0) + 1;
        return 'PW-' . date('ym') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextLeaseAssetNo()
    {
        $next = (LeaseAsset::max('id') ?: 0) + 1;
        return 'ART-' . date('ym') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
