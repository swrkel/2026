<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionTransaction;

/**
 * Distribution-owned repository for DistributionTransaction.
 *
 * This keeps controllers/services depending on Distribution repositories
 * instead of direct main ERP model references. Existing table behaviour is
 * preserved through the Distribution entity wrapper.
 */
class DistributionTransactionRepository
{
    public function query()
    {
        return DistributionTransaction::query();
    }

    public function find($id)
    {
        return DistributionTransaction::find($id);
    }

    public function findOrFail($id)
    {
        return DistributionTransaction::findOrFail($id);
    }

    public function forBusiness($businessId)
    {
        return $this->query()->where('business_id', $businessId);
    }
}
