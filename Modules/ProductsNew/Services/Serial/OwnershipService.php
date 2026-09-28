<?php
namespace Modules\ProductsNew\Services\Serial;

use Illuminate\Support\Facades\Auth;
use Modules\ProductsNew\Entities\ProductsNewOwnershipHistory;

class OwnershipService
{
    public function query(array $filters = [])
    {
        $query = ProductsNewOwnershipHistory::query()->where('business_id', session('business.id'));
        if (!empty($filters['serial_id'])) { $query->where('serial_id', $filters['serial_id']); }
        if (!empty($filters['contact_id'])) { $query->where('contact_id', $filters['contact_id']); }
        return $query->latest('id');
    }

    public function record(array $data): ProductsNewOwnershipHistory
    {
        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = Auth::id();
        return ProductsNewOwnershipHistory::create($data);
    }
}
