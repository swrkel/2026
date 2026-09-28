<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewApprovalRequest;

class MembershipNewApprovalService
{
    public function requestApproval(array $data): MembershipNewApprovalRequest
    {
        return MembershipNewApprovalRequest::create([
            'business_id' => (int) $data['business_id'],
            'request_type' => $data['request_type'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
            'requested_by' => auth()->id(),
            'note' => $data['note'] ?? null,
        ]);
    }

    public function approve(int $businessId, int $requestId, ?string $note = null): MembershipNewApprovalRequest
    {
        return DB::transaction(function () use ($businessId, $requestId, $note) {
            $request = MembershipNewApprovalRequest::forBusiness($businessId)->findOrFail($requestId);
            $request->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_note' => $note,
            ]);

            return $request->fresh();
        });
    }

    public function reject(int $businessId, int $requestId, ?string $note = null): MembershipNewApprovalRequest
    {
        $request = MembershipNewApprovalRequest::forBusiness($businessId)->findOrFail($requestId);
        $request->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_note' => $note,
        ]);

        return $request->fresh();
    }
}
