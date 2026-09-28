<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneDailyCollection;

class PoneCollectionService
{
    public function __construct(
        private PoneContextService $context,
        private PoneNumberSequenceService $numbers,
        private PoneShiftTotalsService $totals,
        private PoneOperatorLedgerService $ledger,
        private PoneAuditService $audit
    ) {}

    public function breakdown(): array
    {
        $shift = $this->totals->refresh($this->context->shift());
        $payments = $shift->payments()->where('status', 'confirmed')
            ->selectRaw('payment_type, COALESCE(SUM(amount),0) total')->groupBy('payment_type')->pluck('total', 'payment_type');
        return [
            'expected_amount' => (float) $shift->expected_total,
            'cash_amount' => (float) ($payments['cash'] ?? 0),
            'card_amount' => (float) ($payments['card'] ?? 0),
            'cheque_amount' => (float) ($payments['cheque'] ?? 0),
            'credit_amount' => (float) ($payments['credit'] ?? 0),
            'other_amount' => (float) ($payments['other'] ?? 0),
        ];
    }

    public function create(array $data): PoneDailyCollection
    {
        return DB::transaction(function () use ($data): PoneDailyCollection {
            $shift = $this->totals->refresh($this->context->shift());
            $values = [];
            foreach (['cash_amount', 'card_amount', 'cheque_amount', 'credit_amount', 'other_amount'] as $field) {
                $values[$field] = round((float) ($data[$field] ?? 0), 4);
                if ($values[$field] < 0) throw ValidationException::withMessages([$field => __('pumperdashboardnew::lang.amount_must_not_be_negative')]);
            }
            $declared = round(array_sum($values), 4);
            $expected = round((float) $shift->expected_total, 4);
            $difference = round($expected - $declared, 4);
            $number = trim((string) ($data['collection_number'] ?? '')) ?: $this->numbers->next($shift->business_id, $shift->location_id, 'collection');

            $collection = PoneDailyCollection::query()->create(array_merge($values, [
                'uuid' => Str::uuid()->toString(),
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'collection_number' => $number,
                'collection_at' => $data['collection_at'] ?? now(),
                'expected_amount' => $expected,
                'declared_amount' => $declared,
                'difference_amount' => $difference,
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
                'created_by' => $this->context->userId(),
                'confirmed_by' => $this->context->userId(),
            ]));

            $status = abs($difference) < 0.00005 ? 'balanced' : ($difference > 0 ? 'shortage' : 'excess');
            $shift->update([
                'collection_form_no' => $number,
                'declared_total' => $declared,
                'reconciliation_status' => $status,
                'shortage_amount' => max(0, $difference),
                'excess_amount' => max(0, -$difference),
                'reconciled_at' => now(),
                'reconciled_by' => $this->context->userId(),
            ]);
            $this->ledger->synchronizeShift($shift->fresh());
            $this->audit->log('collection.confirmed', 'pone_daily_collection', $collection->id, null, $collection);
            return $collection->fresh();
        }, 3);
    }

    public function void(int $id, string $reason): PoneDailyCollection
    {
        return DB::transaction(function () use ($id, $reason): PoneDailyCollection {
            $shift = $this->context->shift(true);
            $collection = PoneDailyCollection::query()->whereKey($id)->where('shift_id', $shift->id)->lockForUpdate()->firstOrFail();
            $before = $collection->toArray();
            $collection->update(['status' => 'void', 'note' => trim($reason), 'voided_by' => $this->context->userId(), 'voided_at' => now()]);
            $latest = PoneDailyCollection::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->latest('collection_at')->first();
            $shift->update([
                'collection_form_no' => $latest?->collection_number,
                'declared_total' => $latest?->declared_amount ?? 0,
                'reconciliation_status' => $latest ? (((float) $latest->difference_amount > 0) ? 'shortage' : (((float) $latest->difference_amount < 0) ? 'excess' : 'balanced')) : 'pending',
                'reconciled_at' => $latest?->collection_at,
            ]);
            $this->audit->log('collection.voided', 'pone_daily_collection', $collection->id, $before, $collection);
            return $collection->fresh();
        }, 3);
    }
}
