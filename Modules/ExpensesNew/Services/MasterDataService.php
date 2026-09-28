<?php

namespace Modules\ExpensesNew\Services;

use Modules\ExpensesNew\Entities\CostCenter;
use Modules\ExpensesNew\Entities\Department;
use Modules\ExpensesNew\Entities\Project;

class MasterDataService
{
    public function departments(int $businessId)
    {
        return Department::where('business_id', $businessId)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
    }

    public function costCenters(int $businessId)
    {
        return CostCenter::where('business_id', $businessId)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
    }

    public function projects(int $businessId)
    {
        return Project::where('business_id', $businessId)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
    }
}
