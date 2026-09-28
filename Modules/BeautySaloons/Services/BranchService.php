<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyBranch;

class BranchService
{
    public function listForBusiness($businessId)
    {
        return BeautyBranch::where('business_id', $businessId)->orderBy('name')->get();
    }

    public function create(array $data): BeautyBranch
    {
        return BeautyBranch::create($data);
    }

    public function update(BeautyBranch $branch, array $data): BeautyBranch
    {
        $branch->update($data);
        return $branch;
    }
}
