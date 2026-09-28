<?php
namespace Modules\ProductsNew\Services\Serial;

use Illuminate\Support\Facades\Auth;
use Modules\ProductsNew\Entities\ProductsNewSerialNumber;
use Modules\ProductsNew\Entities\ProductsNewSerialMovement;
use Modules\ProductsNew\Services\ProductStatusService;

class SerialNumberService
{
    public function __construct(protected ProductStatusService $status)
    {
    }

    public function query(array $filters = [])
    {
        $query = ProductsNewSerialNumber::query()->where('business_id', session('business.id'));
        if (!empty($filters['product_id'])) { $query->where('product_id', $filters['product_id']); }
        if (!empty($filters['status'])) { $query->where('status', $filters['status']); }
        if (!empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('serial_no', 'like', $term)->orWhere('imei_no', 'like', $term)->orWhere('asset_tag', 'like', $term);
            });
        }
        return $query->latest('id');
    }

    public function create(array $data): ProductsNewSerialNumber
    {
        $this->status->assertActiveProductId((int) $data['product_id']);

        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'available';
        $serial = ProductsNewSerialNumber::create($data);
        $this->movement($serial, 'created', null, $data['location_id'] ?? null, 'Serial registered');
        return $serial;
    }

    public function updateStatus(ProductsNewSerialNumber $serial, string $status, ?int $locationId = null, ?string $note = null): ProductsNewSerialNumber
    {
        $from = $serial->location_id;
        $serial->update(['status' => $status, 'location_id' => $locationId ?: $serial->location_id]);
        $this->movement($serial, $status, $from, $locationId ?: $from, $note);
        return $serial;
    }

    public function movement(ProductsNewSerialNumber $serial, string $type, ?int $fromLocationId = null, ?int $toLocationId = null, ?string $note = null): ProductsNewSerialMovement
    {
        return ProductsNewSerialMovement::create([
            'business_id' => $serial->business_id,
            'serial_id' => $serial->id,
            'product_id' => $serial->product_id,
            'variation_id' => $serial->variation_id,
            'movement_type' => $type,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'note' => $note,
            'created_by' => Auth::id(),
        ]);
    }

    public function movements(int $serialId)
    {
        return ProductsNewSerialMovement::query()->where('business_id', session('business.id'))->where('serial_id', $serialId)->latest('id');
    }
}
