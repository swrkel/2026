<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyChair;
use Modules\BeautySaloons\Entities\BeautyResource;

class ResourceService
{
    public function branchChairs($branchId)
    {
        return BeautyChair::where('beauty_branch_id', $branchId)->orderBy('chair_code')->get();
    }

    public function branchResources($branchId)
    {
        return BeautyResource::where('beauty_branch_id', $branchId)->orderBy('resource_code')->get();
    }
}
