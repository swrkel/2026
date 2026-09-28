<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\OperationalPayment;

class OperationalPaymentService
{
    public function __construct(
        private ExternalMasterDataService $masters,
        private FinanceIntegrationService $finance,
        private FinanceAccountPostingService $financeAccounts
    ) {}

    public function normalise(
        int $businessId,
        string $context,
        array $input,
        ?int $locationId = null
    ): ?array {
        $method = trim((string)($input['payment_method'] ?? ''));
        $accountId = (int)($input['payment_account_id'] ?? 0);
        $amount = round((float)($input['payment_amount'] ?? 0), 4);
        $note = trim((string)($input['payment_note'] ?? ''));

        $hasAny = $method !== '' || $accountId > 0 || $amount > 0 || $note !== '';
        if (! $hasAny) {
            return null;
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Payment Amount must be greater than zero when Payment details are entered.',
            ]);
        }

        $map = $this->masters->paymentMethodAccounts($businessId, $context);
        if ($method === '' || ! isset($map['methods'][$method])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Please select a Payment Method enabled for this operation.',
            ]);
        }

        $allowed = $locationId
            ? ($map['by_location'][$locationId][$method] ?? [])
            : ($map['accounts'][$method] ?? []);

        if ($accountId <= 0 || ! isset($allowed[$accountId])) {
            throw ValidationException::withMessages([
                'payment_account_id' => 'Please select a Payment Account linked to the selected Payment Method.',
            ]);
        }

        return [
            'context' => strtolower(trim($context)),
            'method' => $method,
            'method_label' => (string)$map['methods'][$method],
            'account_id' => $accountId,
            'account_name' => (string)$allowed[$accountId],
            'amount' => $amount,
            'note' => $note !== '' ? $note : null,
        ];
    }

    public function record(
        int $businessId,
        int $userId,
        string $sourceType,
        int $sourceId,
        string $eventType,
        array $payment,
        array $meta = [],
        bool $postNow = true
    ): OperationalPayment {
        if (! Schema::hasTable('rcm_operational_payments')) {
            throw ValidationException::withMessages([
                'payment_method' => 'Rice Mill operational payment database update is not installed. Import RiceMill_IS22305_22Sep2026.sql or run the Rice Mill migrations.',
            ]);
        }

        $row = OperationalPayment::updateOrCreate(
            [
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                'payment_context' => (string)($payment['context'] ?? ''),
                'payment_method' => (string)($payment['method'] ?? ''),
                'payment_method_label' => (string)($payment['method_label'] ?? ''),
                'payment_account_id' => (int)($payment['account_id'] ?? 0),
                'payment_account_name' => (string)($payment['account_name'] ?? ''),
                'amount' => (float)($payment['amount'] ?? 0),
                'note' => $payment['note'] ?? null,
                'event_type' => $eventType,
                'meta' => $meta,
                'status' => 'pending',
                'finance_outbox_id' => null,
                'posted_at' => null,
                'created_by' => $userId,
            ]
        );

        if ($postNow) {
            return $this->postRecorded($businessId, $sourceType, $sourceId);
        }

        return $row;
    }

    public function postRecorded(int $businessId, string $sourceType, int $sourceId): OperationalPayment
    {
        $row = OperationalPayment::forBusiness($businessId)
            ->where('source_type',$sourceType)
            ->where('source_id',$sourceId)
            ->firstOrFail();

        $payload = array_merge((array)($row->meta ?? []), [
            'source_type' => $row->source_type,
            'source_id' => (int)$row->source_id,
            'payment_context' => $row->payment_context,
            'payment_method' => $row->payment_method,
            'payment_method_label' => $row->payment_method_label,
            'payment_account_id' => (int)$row->payment_account_id,
            'payment_account_name' => $row->payment_account_name,
            'amount' => (float)$row->amount,
            'note' => $row->note,
        ]);

        // Post the selected Payment Account and its accounting counterpart
        // directly to the standard Finance account-book tables. The outbox is
        // still retained for existing integrations/audit consumers.
        $this->financeAccounts->syncOperationalPayment(
            $businessId,$sourceType,$sourceId,$payload,(int)($row->created_by ?: 0)
        );

        if ($row->status === 'posted' && $row->finance_outbox_id) {
            return $row->fresh();
        }

        $outbox = $this->finance->queue(
            $businessId,
            (string)$row->event_type,
            $sourceType,
            $sourceId,
            $payload
        );

        $row->update([
            'status' => 'posted',
            'finance_outbox_id' => (int)$outbox->id,
            'posted_at' => now(),
        ]);

        return $row->fresh();
    }

    public function postIfRecorded(int $businessId, string $sourceType, int $sourceId): ?OperationalPayment
    {
        $exists = OperationalPayment::forBusiness($businessId)
            ->where('source_type',$sourceType)
            ->where('source_id',$sourceId)
            ->exists();

        return $exists ? $this->postRecorded($businessId,$sourceType,$sourceId) : null;
    }
}
