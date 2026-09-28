<?php

namespace Modules\PetroPDNew\Services\DayEnd;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewDayEnd;
use Modules\PetroPDNew\Entities\PdnewDayEndSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\Integration\PdnewOutboxService;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewNumberSequenceService;
use Modules\PetroPDNew\Services\PdnewReferenceGuardService;
use Modules\PetroPDNew\Services\PdnewSettingsService;
use RuntimeException;

class PdnewDayEndService
{
    public function __construct(
        private PdnewNumberSequenceService $numbers,
        private PdnewSettingsService $settings,
        private PdnewOutboxService $outbox,
        private PdnewAuditService $audit,
        private PdnewReferenceGuardService $references
    ) {}

    public function prepare(int $businessId, ?int $locationId, string $date, int $userId, ?string $note = null): PdnewDayEnd
    {
        $this->references->assertLocation($businessId, $locationId);
        $scopeLocation = $locationId ?: 0;

        return DB::transaction(function () use ($businessId, $scopeLocation, $date, $userId, $note): PdnewDayEnd {
            $existing = PdnewDayEnd::query()
                ->where('business_id', $businessId)
                ->where('location_id', $scopeLocation)
                ->whereDate('day_end_date', $date)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === 'finalized') {
                return $existing->fresh('settlements');
            }

            $scopeSettings = $this->settings->forScope($businessId, $scopeLocation ?: null);
            $dayEnd = $existing ?: PdnewDayEnd::query()->create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $businessId,
                'location_id' => $scopeLocation,
                'day_end_number' => $this->numbers->next(
                    $businessId,
                    $scopeLocation ?: null,
                    'day_end',
                    (string) ($scopeSettings['day_end_prefix'] ?? 'PDN-DE-')
                ),
                'day_end_date' => $date,
                'status' => 'draft',
                'note' => $note,
                'prepared_by' => $userId,
                'prepared_at' => now(),
            ]);

            if ($existing && $note !== null) {
                $dayEnd->update(['note' => $note]);
            }

            return $this->refreshLocked($dayEnd);
        }, 3);
    }

    public function refresh(PdnewDayEnd $dayEnd): PdnewDayEnd
    {
        return DB::transaction(function () use ($dayEnd): PdnewDayEnd {
            $dayEnd = $this->lockDayEnd($dayEnd);
            return $this->refreshLocked($dayEnd);
        }, 3);
    }

    public function finalize(PdnewDayEnd $dayEnd, int $userId, ?string $note = null): PdnewDayEnd
    {
        return DB::transaction(function () use ($dayEnd, $userId, $note): PdnewDayEnd {
            $dayEnd = $this->lockDayEnd($dayEnd);
            $dayEnd = $this->refreshLocked($dayEnd);

            if ((int) $dayEnd->settlement_count < 1) {
                throw new RuntimeException('There are no finalized Petro PD-New settlements for this Day End.');
            }
            if (abs((float) $dayEnd->variance_total) >= 0.00005) {
                throw new RuntimeException('Day End cannot be finalized while a settlement variance remains.');
            }

            $dayEnd->update([
                'status' => 'finalized',
                'note' => $note ?? $dayEnd->note,
                'finalized_by' => $userId,
                'finalized_at' => now(),
            ]);

            $this->outbox->queue(
                (int) $dayEnd->business_id,
                'day_end',
                (int) $dayEnd->id,
                'day_end.finalized',
                ['day_end_number' => $dayEnd->day_end_number]
            );
            $this->audit->log('day_end.finalized', 'pdnew_day_end', $dayEnd->id, null, $dayEnd);

            return $dayEnd->fresh('settlements');
        }, 3);
    }

    private function refreshLocked(PdnewDayEnd $dayEnd): PdnewDayEnd
    {
        if ($dayEnd->status !== 'draft') {
            throw new RuntimeException('Only a draft Petro PD-New Day End can be refreshed.');
        }

        PdnewDayEndSettlement::query()
            ->where('business_id', $dayEnd->business_id)
            ->where('day_end_id', $dayEnd->id)
            ->delete();

        $query = PdnewSettlement::query()
            ->where('business_id', $dayEnd->business_id)
            ->where('status', 'finalized')
            ->whereDate('settlement_date', $dayEnd->day_end_date)
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('pdnew_day_end_settlements as existing_day_end')
                    ->whereColumn('existing_day_end.settlement_id', 'pdnew_settlements.id');
            });

        if ((int) $dayEnd->location_id > 0) {
            $query->where('location_id', $dayEnd->location_id);
        }

        foreach ($query->orderBy('id')->lockForUpdate()->get() as $settlement) {
            PdnewDayEndSettlement::query()->create([
                'business_id' => $dayEnd->business_id,
                'day_end_id' => $dayEnd->id,
                'settlement_id' => $settlement->id,
                'settlement_amount' => $settlement->expected_total,
                'payment_amount' => $settlement->received_total,
                'variance_amount' => $settlement->variance_amount,
            ]);
        }

        $items = PdnewDayEndSettlement::query()
            ->where('business_id', $dayEnd->business_id)
            ->where('day_end_id', $dayEnd->id);
        $dayEnd->update([
            'settlement_count' => (clone $items)->count(),
            'settlements_total' => (clone $items)->sum('settlement_amount'),
            'payments_total' => (clone $items)->sum('payment_amount'),
            'variance_total' => (clone $items)->sum('variance_amount'),
        ]);

        return $dayEnd->fresh('settlements');
    }

    private function lockDayEnd(PdnewDayEnd $dayEnd): PdnewDayEnd
    {
        return PdnewDayEnd::query()
            ->where('business_id', $dayEnd->business_id)
            ->whereKey($dayEnd->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
