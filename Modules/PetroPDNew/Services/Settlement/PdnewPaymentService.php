<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementPayment;
use Modules\PetroPDNew\Entities\PdnewSettlementPaymentDetail;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewNumberSequenceService;
use Modules\PetroPDNew\Services\PdnewReferenceGuardService;
use RuntimeException;

class PdnewPaymentService
{
    public function __construct(
        private PdnewSettlementService $settlements,
        private PdnewSettlementTotalsService $totals,
        private PdnewReconciliationService $reconciliation,
        private PdnewNumberSequenceService $numbers,
        private PdnewAuditService $audit,
        private PdnewReferenceGuardService $references
    ) {}

    public function create(PdnewSettlement $settlement, array $data, int $userId): PdnewSettlementPayment
    {
        $type = (string) $data['payment_type'];
        if (! in_array($type, (array) config('petropdnew.payment_types', []), true)) {
            throw new RuntimeException('Unsupported Petro PD-New payment type.');
        }

        return DB::transaction(function () use ($settlement, $data, $userId, $type): PdnewSettlementPayment {
            $settlement = $this->settlements->lockForUpdate($settlement);
            $this->settlements->assertEditable($settlement);
            $this->references->assertCustomer(
                (int) $settlement->business_id,
                (int) ($data['customer_id'] ?? 0) ?: null
            );
            $this->assertReferenceAvailable(
                (int) $settlement->business_id,
                $type,
                $data['reference_no'] ?? null
            );

            $number = $this->numbers->next(
                (int) $settlement->business_id,
                $settlement->location_id ? (int) $settlement->location_id : null,
                'payment',
                'PDN-PAY-'
            );

            $payment = PdnewSettlementPayment::query()->create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $settlement->business_id,
                'settlement_id' => $settlement->id,
                'pone_payment_id' => null,
                'payment_number' => $number,
                'payment_type' => $type,
                'gross_amount' => $data['gross_amount'] ?? $data['amount'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'amount' => $data['amount'],
                'customer_id' => (int) ($data['customer_id'] ?? 0) ?: null,
                'reference_no' => $this->normaliseReference($data['reference_no'] ?? null),
                'transaction_at' => $data['transaction_at'] ?? now(),
                'status' => 'active',
                'is_source' => false,
                'note' => $data['note'] ?? null,
                'metadata' => array_merge((array) ($data['metadata'] ?? []), [
                    'included_in_received_total' => ! in_array($type, ['shortage', 'excess'], true),
                    'source_classification' => in_array($type, ['shortage', 'excess'], true)
                        ? 'operational_variance'
                        : 'collection',
                ]),
                'created_by' => $userId,
            ]);

            foreach ((array) ($data['details'] ?? []) as $detail) {
                PdnewSettlementPaymentDetail::query()->create([
                    'business_id' => $settlement->business_id,
                    'settlement_id' => $settlement->id,
                    'payment_id' => $payment->id,
                    'detail_type' => (string) ($detail['detail_type'] ?? $type),
                    'reference_no' => $detail['reference_no'] ?? null,
                    'amount' => $detail['amount'] ?? 0,
                    'detail' => $detail,
                ]);
            }

            $this->totals->recalculate($settlement);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('payment.created', 'pdnew_settlement_payment', $payment->id, null, $payment);

            return $payment->fresh('details');
        }, 3);
    }

    public function update(PdnewSettlementPayment $payment, array $data): PdnewSettlementPayment
    {
        return DB::transaction(function () use ($payment, $data): PdnewSettlementPayment {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $payment->business_id)
                ->whereKey($payment->settlement_id)
                ->lockForUpdate()
                ->firstOrFail();
            $payment = PdnewSettlementPayment::query()
                ->where('business_id', $settlement->business_id)
                ->where('settlement_id', $settlement->id)
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->settlements->assertEditable($settlement);
            if ($payment->is_source) {
                throw new RuntimeException('Pumper Dashboard-New source payments are immutable in Petro PD-New. Refresh the source instead.');
            }
            if ($payment->status !== 'active') {
                throw new RuntimeException('Only active manual payments can be edited.');
            }

            $customerId = array_key_exists('customer_id', $data)
                ? ((int) $data['customer_id'] ?: null)
                : ($payment->customer_id ? (int) $payment->customer_id : null);
            $reference = array_key_exists('reference_no', $data)
                ? $data['reference_no']
                : $payment->reference_no;

            $this->references->assertCustomer((int) $settlement->business_id, $customerId);
            $this->assertReferenceAvailable(
                (int) $settlement->business_id,
                (string) $payment->payment_type,
                $reference,
                (int) $payment->id
            );

            $before = $payment->getAttributes();
            $payment->update([
                'gross_amount' => $data['gross_amount'] ?? $data['amount'] ?? $payment->gross_amount,
                'discount_amount' => $data['discount_amount'] ?? $payment->discount_amount,
                'amount' => $data['amount'] ?? $payment->amount,
                'customer_id' => $customerId,
                'reference_no' => $this->normaliseReference($reference),
                'transaction_at' => $data['transaction_at'] ?? $payment->transaction_at,
                'note' => array_key_exists('note', $data) ? $data['note'] : $payment->note,
                'metadata' => array_merge((array) ($data['metadata'] ?? $payment->metadata ?? []), [
                    'included_in_received_total' => ! in_array((string) $payment->payment_type, ['shortage', 'excess'], true),
                    'source_classification' => in_array((string) $payment->payment_type, ['shortage', 'excess'], true)
                        ? 'operational_variance'
                        : 'collection',
                ]),
            ]);

            $this->totals->recalculate($settlement);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('payment.updated', 'pdnew_settlement_payment', $payment->id, $before, $payment);

            return $payment->fresh('details');
        }, 3);
    }

    public function void(PdnewSettlementPayment $payment, int $userId, string $reason): PdnewSettlementPayment
    {
        return DB::transaction(function () use ($payment, $userId, $reason): PdnewSettlementPayment {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $payment->business_id)
                ->whereKey($payment->settlement_id)
                ->lockForUpdate()
                ->firstOrFail();
            $payment = PdnewSettlementPayment::query()
                ->where('business_id', $settlement->business_id)
                ->where('settlement_id', $settlement->id)
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->settlements->assertEditable($settlement);
            if ($payment->is_source) {
                throw new RuntimeException('A Pumper Dashboard-New source payment cannot be voided in Petro PD-New.');
            }
            if ($payment->status !== 'active') {
                throw new RuntimeException('Only an active manual payment can be voided.');
            }

            $payment->update([
                'status' => 'void',
                'voided_by' => $userId,
                'voided_at' => now(),
                'note' => trim((string) $payment->note . "\nVoid reason: " . $reason),
            ]);

            $this->totals->recalculate($settlement);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('payment.voided', 'pdnew_settlement_payment', $payment->id, null, [
                'reason' => $reason,
            ]);

            return $payment->fresh();
        }, 3);
    }

    public function duplicateReference(
        int $businessId,
        string $paymentType,
        ?string $reference,
        ?int $ignoreId = null
    ): bool {
        $reference = $this->normaliseReference($reference);
        if ($reference === null) {
            return false;
        }

        return PdnewSettlementPayment::query()
            ->where('business_id', $businessId)
            ->where('payment_type', $paymentType)
            ->where('reference_no', $reference)
            ->where('status', 'active')
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists();
    }

    private function assertReferenceAvailable(
        int $businessId,
        string $paymentType,
        ?string $reference,
        ?int $ignoreId = null
    ): void {
        if ($this->duplicateReference($businessId, $paymentType, $reference, $ignoreId)) {
            throw new RuntimeException('This payment reference is already used in Petro PD-New.');
        }
    }

    private function normaliseReference(?string $reference): ?string
    {
        $reference = trim((string) $reference);
        return $reference === '' ? null : $reference;
    }
}
