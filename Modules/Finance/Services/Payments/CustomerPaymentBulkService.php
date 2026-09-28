<?php

namespace Modules\Finance\Services\Payments;

/**
 * Finance module service placeholder for CustomerPaymentBulkService.
 *
 * Existing working controller logic is not changed in FIN-004. Future FIN
 * packages can move one method at a time from the legacy controller into this
 * service, with UAT after each move. This avoids disrupting live accounting.
 */
class CustomerPaymentBulkService
{
    public function businessId(): ?int
    {
        return session('user.business_id') ?: session('business.id');
    }

    public function scopeBusiness($query)
    {
        $businessId = $this->businessId();
        if ($businessId) {
            return $query->where('business_id', $businessId);
        }
        return $query;
    }
}
