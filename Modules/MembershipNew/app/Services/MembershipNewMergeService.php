<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewMergeRequest;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;

class MembershipNewMergeService
{
    public function createRequest(int $primaryId, int $duplicateId, ?string $reason = null): MembershipNewMergeRequest
    {
        if ($primaryId === $duplicateId) {
            throw new \InvalidArgumentException('Primary and duplicate member cannot be the same.');
        }

        return MembershipNewMergeRequest::create([
            'primary_central_member_id' => $primaryId,
            'duplicate_central_member_id' => $duplicateId,
            'reason' => $reason,
            'merge_payload' => [
                'maps_to_move' => MembershipNewMemberBusinessMap::where('central_member_id', $duplicateId)->pluck('id')->all(),
            ],
            'is_approved' => 0,
        ]);
    }

    public function approve(int $requestId): MembershipNewMergeRequest
    {
        $request = MembershipNewMergeRequest::findOrFail($requestId);
        $request->update([
            'is_approved' => 1,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return $request->fresh();
    }

    public function process(int $requestId): MembershipNewMergeRequest
    {
        return DB::transaction(function () use ($requestId) {
            $request = MembershipNewMergeRequest::findOrFail($requestId);

            if (!$request->is_approved) {
                throw new \RuntimeException('Merge request must be approved before processing.');
            }

            MembershipNewMemberBusinessMap::where('central_member_id', $request->duplicate_central_member_id)
                ->update(['central_member_id' => $request->primary_central_member_id]);

            MembershipNewCentralMember::where('id', $request->duplicate_central_member_id)
                ->update(['is_active' => 0]);

            $request->update([
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            return $request->fresh();
        });
    }
}
