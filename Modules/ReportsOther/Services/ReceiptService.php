<?php

namespace Modules\ReportsOther\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Support\CurrentScope;
use RuntimeException;

class ReceiptService
{
    public function __construct(
        private readonly CurrentScope $scope,
        private readonly ReceiptSourceDataGateway $sourceData,
        private readonly SequenceService $sequence,
        private readonly BusinessSettingsGateway $businessSettings,
        private readonly AmountWordsService $amountWords,
    ) {
    }

    public function create(Source $source, string $receiptDate, ?string $manualMembershipNo): Receipt
    {
        $this->guardSource($source);
        $date = Carbon::parse($receiptDate)->startOfDay();
        $snapshot = $this->sourceData->snapshot($source, $date);
        if (!$snapshot['available']) {
            throw new RuntimeException((string) $snapshot['message']);
        }
        if ((int) ($snapshot['matched_transactions'] ?? 0) < 1) {
            throw new RuntimeException('No source transactions were found for the selected Source and date.');
        }

        $autoMembership = trim((string) ($snapshot['membership_no'] ?? ''));
        $manualMembership = trim((string) $manualMembershipNo);
        $membershipIsManual = $autoMembership === '';
        $membershipNo = $membershipIsManual ? $manualMembership : $autoMembership;
        if ($membershipIsManual && $membershipNo === '') {
            throw new RuntimeException('Membership No is not available automatically. Enter it manually before saving.');
        }

        return DB::transaction(function () use ($source, $date, $snapshot, $membershipNo, $membershipIsManual) {
            $existing = Receipt::query()
                ->where('scope_key', $this->scope->key())
                ->whereDate('receipt_date', $date->toDateString())
                ->where('source_id', $source->id)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                throw new RuntimeException('A Receipt already exists for this Source on the selected date: '.$existing->receipt_no);
            }

            $receiptNo = $this->sequence->consume(config('reportsother.receipt_document_key'));
            $precision = $this->businessSettings->currencyPrecision($this->scope->businessId());
            $total = (float) $snapshot['total'];

            $receipt = Receipt::query()->create([
                'business_id' => $this->scope->businessId(),
                'location_id' => $this->scope->locationId(),
                'store_id' => $this->scope->storeId(),
                'scope_key' => $this->scope->key(),
                'receipt_date' => $date->toDateString(),
                'receipt_no' => $receiptNo,
                'source_id' => $source->id,
                'source_name' => $source->source_name,
                'membership_no' => $membershipNo,
                'membership_is_manual' => $membershipIsManual,
                'total_amount' => $total,
                'amount_in_words' => $this->amountWords->convert($total, $precision),
                'entered_by' => $this->scope->userId(),
                'entered_by_name' => $this->scope->userName(),
            ]);

            $receipt->details()->createMany(array_map(fn ($detail) => [
                'item_type' => $detail['item_type'],
                'item_id' => $detail['item_id'],
                'source_detail' => $detail['source_detail'],
                'amount' => $detail['amount'],
                'sort_order' => $detail['sort_order'],
                'created_at' => now(),
            ], $snapshot['details']));

            if (!empty($snapshot['cheques'])) {
                $receipt->cheques()->createMany(array_map(fn ($cheque) => [
                    'external_payment_id' => $cheque['external_payment_id'] ?? null,
                    'cheque_number' => $cheque['cheque_number'] ?? null,
                    'bank_name' => $cheque['bank_name'] ?? null,
                    'cheque_date' => $cheque['cheque_date'] ?? null,
                    'created_at' => now(),
                ], $snapshot['cheques']));
            }

            return $receipt->load(['details', 'cheques', 'audits']);
        }, 3);
    }

    public function updateManualFields(Receipt $receipt, array $values): Receipt
    {
        $this->guardReceipt($receipt);

        return DB::transaction(function () use ($receipt, $values) {
            $changes = [];

            if ($receipt->membership_is_manual) {
                $newValue = trim((string) ($values['membership_no'] ?? ''));
                if ($newValue === '') {
                    throw new RuntimeException('Membership No cannot be blank.');
                }
                $oldValue = (string) ($receipt->membership_no ?? '');
                if ($newValue !== $oldValue) {
                    $changes[] = [
                        'field_name' => 'membership_no',
                        'field_label' => 'Membership No',
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                    ];
                    $receipt->membership_no = $newValue;
                }
            }

            if (!$changes) {
                return $receipt->load(['details', 'cheques', 'audits']);
            }

            $receipt->save();
            foreach ($changes as $change) {
                $receipt->audits()->create($change + [
                    'edited_by' => $this->scope->userId(),
                    'edited_by_name' => $this->scope->userName(),
                    'edited_at' => now(),
                ]);
            }

            return $receipt->load(['details', 'cheques', 'audits']);
        }, 3);
    }

    public function guardReceipt(Receipt $receipt): void
    {
        if (!hash_equals((string) $this->scope->key(), (string) $receipt->scope_key)) {
            abort(403);
        }
    }

    private function guardSource(Source $source): void
    {
        if (!hash_equals((string) $this->scope->key(), (string) $source->scope_key)) {
            abort(403);
        }
    }
}
