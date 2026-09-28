<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\AiRecommendationLog;
use Modules\StockTransferNew\Entities\AiReplenishmentReview;

class AiReplenishmentReviewService
{
    public function listForBusiness(int $businessId, array $filters = [])
    {
        $query = AiReplenishmentReview::query()
            ->where('business_id', $businessId)
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['risk_level']), fn ($q) => $q->where('risk_level', $filters['risk_level']))
            ->when(!empty($filters['store_id']), fn ($q) => $q->where('store_id', $filters['store_id']))
            ->orderByRaw("FIELD(risk_level, 'critical','high','medium','low')")
            ->orderByDesc('confidence_score')
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function approve(int $businessId, int $reviewId, float $approvedQty, int $userId, ?string $remarks = null): AiReplenishmentReview
    {
        return DB::transaction(function () use ($businessId, $reviewId, $approvedQty, $userId, $remarks) {
            $review = AiReplenishmentReview::where('business_id', $businessId)->lockForUpdate()->findOrFail($reviewId);
            $before = $review->toArray();

            if (!in_array($review->status, ['pending','returned'], true)) {
                throw new \RuntimeException('Only pending or returned AI recommendations can be approved.');
            }

            if ($approvedQty <= 0) {
                throw new \InvalidArgumentException('Approved quantity must be greater than zero.');
            }

            $review->approved_qty = $approvedQty;
            $review->status = 'approved';
            $review->reviewed_by = $userId;
            $review->reviewed_at = Carbon::now();
            $review->remarks = $remarks;
            $review->save();

            $this->log($businessId, 'approve', $review->id, $before, $review->fresh()->toArray(), $userId, $remarks);

            return $review;
        });
    }

    public function reject(int $businessId, int $reviewId, int $userId, ?string $remarks = null): AiReplenishmentReview
    {
        return $this->changeStatus($businessId, $reviewId, 'rejected', $userId, $remarks);
    }

    public function returnForCorrection(int $businessId, int $reviewId, int $userId, ?string $remarks = null): AiReplenishmentReview
    {
        return $this->changeStatus($businessId, $reviewId, 'returned', $userId, $remarks);
    }

    protected function changeStatus(int $businessId, int $reviewId, string $status, int $userId, ?string $remarks): AiReplenishmentReview
    {
        return DB::transaction(function () use ($businessId, $reviewId, $status, $userId, $remarks) {
            $review = AiReplenishmentReview::where('business_id', $businessId)->lockForUpdate()->findOrFail($reviewId);
            $before = $review->toArray();
            $review->status = $status;
            $review->reviewed_by = $userId;
            $review->reviewed_at = Carbon::now();
            $review->remarks = $remarks;
            $review->save();
            $this->log($businessId, $status, $review->id, $before, $review->fresh()->toArray(), $userId, $remarks);
            return $review;
        });
    }

    protected function log(int $businessId, string $action, int $referenceId, array $before, array $after, int $userId, ?string $remarks): void
    {
        AiRecommendationLog::create([
            'business_id' => $businessId,
            'module_area' => 'ai_replenishment_review',
            'reference_type' => AiReplenishmentReview::class,
            'reference_id' => $referenceId,
            'action' => $action,
            'before_payload' => json_encode($before),
            'after_payload' => json_encode($after),
            'performed_by' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 250),
            'remarks' => $remarks,
        ]);
    }
}
