<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PriceChangeNew\Entities\PriceChange;

class PriceChangeWorkflowService
{
    public function __construct(
        private PriceChangeSettingsService $settings,
        private PriceChangeAuditService $audits
    ) {
    }

    public function submit(PriceChange $change, int $userId): PriceChange
    {
        $this->assertStatus($change, ['draft'], 'Only a draft may be submitted.');
        $change->loadMissing(['lines', 'scopes', 'scopePrices']);

        if ($change->lines->isEmpty() || $change->scopes->isEmpty()) {
            throw ValidationException::withMessages(['price_change' => 'The draft must contain at least one price line and one location.']);
        }
        if ($change->stock_price_mode !== 'all_stock') {
            throw ValidationException::withMessages(['stock_price_mode' => 'Old-stock/new-stock layering is reserved for the next controlled parcel. Select Apply to all stock.']);
        }
        if ($change->application_scope === 'location_price_groups') {
            $required = $change->lines->count() * $change->scopes->count();
            if ($change->scopePrices->count() !== $required) {
                throw ValidationException::withMessages(['application_scope' => 'One or more selected locations do not have a complete selling-price-group snapshot. Edit and save the draft again.']);
            }
        }

        $approvalRequired = (bool) $this->settings->get($change->business_id, 'approval_required', true);

        return DB::transaction(function () use ($change, $userId, $approvalRequired): PriceChange {
            $locked = PriceChange::query()->whereKey($change->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, ['draft'], 'Only a draft may be submitted.');
            $locked->submitted_by = $userId;
            $locked->submitted_at = now();
            $locked->failure_message = null;

            if ($approvalRequired) {
                $locked->status = 'submitted';
                $locked->save();
                $this->audits->record($locked, 'submitted', 'draft', 'submitted', [], $userId);
            } else {
                $to = $locked->effective_at && $locked->effective_at->isFuture() ? 'scheduled' : 'approved';
                $locked->status = $to;
                $locked->approved_by = $userId;
                $locked->approved_at = now();
                $locked->approval_notes = 'Automatically approved because approval is disabled in Price Change settings.';
                $locked->save();
                $this->audits->record($locked, 'submitted', 'draft', 'submitted', ['approval_required' => false], $userId);
                $this->audits->record($locked, 'auto_approved', 'submitted', $to, ['approval_required' => false], $userId);
            }

            return $locked->fresh();
        }, 3);
    }

    public function approve(PriceChange $change, int $userId, ?string $notes = null): PriceChange
    {
        $this->assertStatus($change, ['submitted'], 'Only a submitted price change may be approved.');
        $allowSelfApproval = (bool) $this->settings->get($change->business_id, 'allow_self_approval', false);
        if (! $allowSelfApproval && (int) $change->submitted_by === $userId) {
            throw ValidationException::withMessages(['approval' => 'The user who submitted this price change cannot approve it.']);
        }

        return DB::transaction(function () use ($change, $userId, $notes): PriceChange {
            $locked = PriceChange::query()->whereKey($change->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, ['submitted'], 'Only a submitted price change may be approved.');
            $to = $locked->effective_at && $locked->effective_at->isFuture() ? 'scheduled' : 'approved';
            $locked->status = $to;
            $locked->approved_by = $userId;
            $locked->approved_at = now();
            $locked->approval_notes = $notes ? trim($notes) : null;
            $locked->rejection_reason = null;
            $locked->failure_message = null;
            $locked->save();
            $this->audits->record($locked, 'approved', 'submitted', $to, ['notes' => $locked->approval_notes], $userId);
            return $locked->fresh();
        }, 3);
    }

    public function reject(PriceChange $change, int $userId, string $reason): PriceChange
    {
        $this->assertStatus($change, ['submitted'], 'Only a submitted price change may be rejected.');
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Enter the reason for rejection.']);
        }

        return DB::transaction(function () use ($change, $userId, $reason): PriceChange {
            $locked = PriceChange::query()->whereKey($change->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, ['submitted'], 'Only a submitted price change may be rejected.');
            $locked->status = 'rejected';
            $locked->approved_by = $userId;
            $locked->approved_at = now();
            $locked->rejection_reason = $reason;
            $locked->save();
            $this->audits->record($locked, 'rejected', 'submitted', 'rejected', ['reason' => $reason], $userId);
            return $locked->fresh();
        }, 3);
    }

    public function cancel(PriceChange $change, int $userId, ?string $reason = null): PriceChange
    {
        $this->assertStatus($change, ['draft', 'submitted', 'approved', 'scheduled', 'failed'], 'This price change can no longer be cancelled.');

        return DB::transaction(function () use ($change, $userId, $reason): PriceChange {
            $locked = PriceChange::query()->whereKey($change->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, ['draft', 'submitted', 'approved', 'scheduled', 'failed'], 'This price change can no longer be cancelled.');
            $from = $locked->status;
            $locked->status = 'cancelled';
            $locked->cancelled_by = $userId;
            $locked->cancelled_at = now();
            $locked->failure_message = $reason ? trim($reason) : null;
            $locked->save();
            $this->audits->record($locked, 'cancelled', $from, 'cancelled', ['reason' => $reason], $userId);
            return $locked->fresh();
        }, 3);
    }

    /** @param array<int, string> $statuses */
    private function assertStatus(PriceChange $change, array $statuses, string $message): void
    {
        abort_unless(in_array($change->status, $statuses, true), 422, $message);
    }
}
