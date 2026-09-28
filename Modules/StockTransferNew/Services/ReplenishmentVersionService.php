<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\StockTransferNew\Entities\ReplenishmentRecommendationVersion;
use Modules\StockTransferNew\Entities\ReplenishmentConversionLog;

class ReplenishmentVersionService
{
    public function listForBusiness(int $businessId, array $filters = [])
    {
        $query = ReplenishmentRecommendationVersion::query()
            ->where('business_id', $businessId)
            ->orderByDesc('id');

        foreach (['status','risk_level','location_id','store_id','product_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->paginate((int)($filters['per_page'] ?? 25));
    }

    public function createNewVersion(int $businessId, array $data, int $userId): ReplenishmentRecommendationVersion
    {
        return DB::transaction(function () use ($businessId, $data, $userId) {
            $last = ReplenishmentRecommendationVersion::where('business_id', $businessId)
                ->where('location_id', $data['location_id'])
                ->where('store_id', $data['store_id'])
                ->where('product_id', $data['product_id'])
                ->max('version_no');

            $row = ReplenishmentRecommendationVersion::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'],
                'store_id' => $data['store_id'],
                'product_id' => $data['product_id'],
                'recommendation_source' => $data['recommendation_source'] ?? 'manual_review',
                'version_no' => ((int)$last) + 1,
                'recommended_qty' => $data['recommended_qty'],
                'approved_qty' => $data['approved_qty'] ?? null,
                'confidence_score' => $data['confidence_score'] ?? 0,
                'risk_level' => $data['risk_level'] ?? 'medium',
                'status' => 'draft',
                'version_reason' => $data['version_reason'] ?? null,
                'created_by' => $userId,
                'meta_json' => $data['meta_json'] ?? [],
            ]);

            $this->log($businessId, $row->id, null, 'version_created', null, 'draft', 0, (float)$row->recommended_qty, $userId, $data['version_reason'] ?? null);
            return $row;
        });
    }

    public function approveVersion(int $businessId, int $id, float $approvedQty, int $userId, ?string $remarks = null): ReplenishmentRecommendationVersion
    {
        return DB::transaction(function () use ($businessId, $id, $approvedQty, $userId, $remarks) {
            $row = $this->findOpen($businessId, $id);
            if ($approvedQty <= 0) {
                throw ValidationException::withMessages(['approved_qty' => 'Approved quantity must be greater than zero.']);
            }

            $beforeStatus = $row->status;
            $beforeQty = (float)($row->approved_qty ?: $row->recommended_qty);
            $row->update([
                'approved_qty' => $approvedQty,
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'locked_at' => now(),
            ]);

            $this->log($businessId, $row->id, null, 'version_approved', $beforeStatus, 'approved', $beforeQty, $approvedQty, $userId, $remarks);
            return $row->fresh();
        });
    }

    public function convertToTransferRequest(int $businessId, int $id, int $userId, ?string $remarks = null): ReplenishmentRecommendationVersion
    {
        return DB::transaction(function () use ($businessId, $id, $userId, $remarks) {
            $row = ReplenishmentRecommendationVersion::where('business_id', $businessId)->lockForUpdate()->findOrFail($id);
            if ($row->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Only approved recommendation versions can be converted.']);
            }
            if ($row->converted_transfer_id) {
                throw ValidationException::withMessages(['converted_transfer_id' => 'This recommendation is already converted to a transfer request.']);
            }

            // Safe bridge: create a placeholder row in the module conversion register only.
            // The real transfer request is created by the stock-transfer request workflow service in deployments where it is enabled.
            $conversionId = DB::table('stn_replenishment_transfer_placeholders')->insertGetId([
                'business_id' => $businessId,
                'recommendation_version_id' => $row->id,
                'location_id' => $row->location_id,
                'store_id' => $row->store_id,
                'product_id' => $row->product_id,
                'qty' => $row->approved_qty ?: $row->recommended_qty,
                'status' => 'pending_transfer_creation',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row->update([
                'status' => 'converted',
                'converted_transfer_id' => $conversionId,
                'converted_at' => now(),
            ]);

            $this->log($businessId, $row->id, $conversionId, 'converted_to_transfer_request', 'approved', 'converted', (float)$row->approved_qty, (float)$row->approved_qty, $userId, $remarks);
            return $row->fresh();
        });
    }

    public function cancelVersion(int $businessId, int $id, int $userId, ?string $remarks = null): ReplenishmentRecommendationVersion
    {
        return DB::transaction(function () use ($businessId, $id, $userId, $remarks) {
            $row = $this->findOpen($businessId, $id);
            $before = $row->status;
            $qty = (float)($row->approved_qty ?: $row->recommended_qty);
            $row->update(['status' => 'cancelled']);
            $this->log($businessId, $row->id, null, 'version_cancelled', $before, 'cancelled', $qty, $qty, $userId, $remarks);
            return $row->fresh();
        });
    }

    protected function findOpen(int $businessId, int $id): ReplenishmentRecommendationVersion
    {
        $row = ReplenishmentRecommendationVersion::where('business_id', $businessId)->lockForUpdate()->findOrFail($id);
        if (in_array($row->status, ['converted','cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Converted or cancelled recommendation versions cannot be changed.']);
        }
        return $row;
    }

    protected function log(int $businessId, int $versionId, ?int $transferId, string $action, ?string $beforeStatus, ?string $afterStatus, float $beforeQty, float $afterQty, int $userId, ?string $remarks): void
    {
        ReplenishmentConversionLog::create([
            'business_id' => $businessId,
            'recommendation_version_id' => $versionId,
            'transfer_id' => $transferId,
            'action' => $action,
            'status_before' => $beforeStatus,
            'status_after' => $afterStatus,
            'qty_before' => $beforeQty,
            'qty_after' => $afterQty,
            'performed_by' => $userId,
            'remarks' => $remarks,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
