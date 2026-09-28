<?php

namespace Modules\Distribution\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository for Distribution-owned data access.
 *
 * This class is intentionally small and non-invasive. It gives Distribution
 * services/controllers a module-owned place to access data without spreading
 * direct App\... model calls across the module.
 */
abstract class DistributionBaseRepository
{
    /** @var class-string<Model> */
    protected string $modelClass;

    public function query(): Builder
    {
        return $this->modelClass::query();
    }

    public function forBusiness(?int $businessId = null): Builder
    {
        $businessId = $businessId ?: (int) session('user.business_id');

        return $this->query()->where('business_id', $businessId);
    }

    public function find($id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findForBusiness($id, ?int $businessId = null): ?Model
    {
        return $this->forBusiness($businessId)->where('id', $id)->first();
    }

    public function pluckForBusiness(string $value, string $key = 'id', ?int $businessId = null)
    {
        return $this->forBusiness($businessId)->pluck($value, $key);
    }
}
