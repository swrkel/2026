<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;
use Modules\MembershipNew\app\Models\MembershipNewDuplicateCandidate;

class MembershipNewDuplicateService
{
    public function scan(): int
    {
        $count = 0;

        MembershipNewCentralMember::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($members) use (&$count) {
                foreach ($members as $member) {
                    $matches = MembershipNewCentralMember::query()
                        ->where('id', '!=', $member->id)
                        ->where(function ($q) use ($member) {
                            if ($member->nic) {
                                $q->orWhere('nic', $member->nic);
                            }
                            if ($member->mobile) {
                                $q->orWhere('mobile', $member->mobile);
                            }
                            if ($member->email) {
                                $q->orWhere('email', $member->email);
                            }
                        })
                        ->limit(10)
                        ->get();

                    foreach ($matches as $match) {
                        $fields = [];
                        if ($member->nic && $member->nic === $match->nic) {
                            $fields[] = 'nic';
                        }
                        if ($member->mobile && $member->mobile === $match->mobile) {
                            $fields[] = 'mobile';
                        }
                        if ($member->email && $member->email === $match->email) {
                            $fields[] = 'email';
                        }

                        if (!$fields) {
                            continue;
                        }

                        $score = count($fields) / 3;

                        MembershipNewDuplicateCandidate::updateOrCreate(
                            [
                                'primary_central_member_id' => min($member->id, $match->id),
                                'duplicate_central_member_id' => max($member->id, $match->id),
                            ],
                            [
                                'match_fields' => $fields,
                                'confidence_score' => $score,
                                'is_resolved' => 0,
                            ]
                        );
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function markResolved(int $candidateId, ?string $note = null): MembershipNewDuplicateCandidate
    {
        $candidate = MembershipNewDuplicateCandidate::findOrFail($candidateId);
        $candidate->update([
            'is_resolved' => 1,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_note' => $note,
        ]);

        return $candidate->fresh();
    }
}
