<?php
namespace Modules\ProductsNew\Services\Serial;

use Illuminate\Support\Facades\Auth;
use Modules\ProductsNew\Entities\ProductsNewWarrantyRegistration;
use Modules\ProductsNew\Entities\ProductsNewWarrantyClaim;
use Modules\ProductsNew\Services\ProductStatusService;

class WarrantyService
{
    public function __construct(protected ProductStatusService $status)
    {
    }

    public function registrations(array $filters = [])
    {
        $query = ProductsNewWarrantyRegistration::query()->where('business_id', session('business.id'));
        if (!empty($filters['product_id'])) { $query->where('product_id', $filters['product_id']); }
        if (!empty($filters['serial_id'])) { $query->where('serial_id', $filters['serial_id']); }
        if (!empty($filters['status'])) { $query->where('status', $filters['status']); }
        return $query->latest('id');
    }

    public function register(array $data): ProductsNewWarrantyRegistration
    {
        $this->status->assertActiveProductId((int) $data['product_id']);

        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'active';
        return ProductsNewWarrantyRegistration::create($data);
    }

    public function claims(array $filters = [])
    {
        $query = ProductsNewWarrantyClaim::query()->where('business_id', session('business.id'));
        if (!empty($filters['registration_id'])) { $query->where('registration_id', $filters['registration_id']); }
        if (!empty($filters['claim_status'])) { $query->where('claim_status', $filters['claim_status']); }
        return $query->latest('id');
    }

    public function claim(array $data): ProductsNewWarrantyClaim
    {
        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = Auth::id();
        $data['claim_status'] = $data['claim_status'] ?? 'open';
        return ProductsNewWarrantyClaim::create($data);
    }

    public function updateClaim(ProductsNewWarrantyClaim $claim, array $data): ProductsNewWarrantyClaim
    {
        $claim->update($data + ['updated_by' => Auth::id()]);
        return $claim;
    }
}
