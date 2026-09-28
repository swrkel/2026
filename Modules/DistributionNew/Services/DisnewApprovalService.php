<?php
namespace Modules\DistributionNew\Services;

use Modules\DistributionNew\Models\DisnewApprovalRule;
use Modules\DistributionNew\Models\DisnewApprovalRequest;

class DisnewApprovalService
{
    public function requestIfNeeded(array $context): ?DisnewApprovalRequest
    {
        $rule = DisnewApprovalRule::where('business_id', $context['business_id'])
            ->where('rule_for', $context['rule_for'])
            ->where('is_active', 1)
            ->where(function ($q) use ($context) {
                $amount = (float)($context['amount'] ?? 0);
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($q) use ($context) {
                $amount = (float)($context['amount'] ?? 0);
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->first();
        if (! $rule) { return null; }
        return DisnewApprovalRequest::create([
            'business_id' => $context['business_id'],
            'location_id' => $context['location_id'] ?? null,
            'rule_id' => $rule->id,
            'reference_type' => $context['reference_type'],
            'reference_id' => $context['reference_id'],
            'requested_by' => $context['requested_by'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
            'remarks' => $context['remarks'] ?? null,
        ]);
    }

    public function approve(DisnewApprovalRequest $request, int $userId, ?string $remarks = null): DisnewApprovalRequest
    {
        $request->fill(['approved_by' => $userId, 'status' => 'approved', 'approved_at' => now(), 'remarks' => $remarks])->save();
        return $request;
    }
}
