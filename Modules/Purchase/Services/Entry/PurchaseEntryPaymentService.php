<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryPaymentService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema,
        protected PurchaseEntryAccountingService $accounting,
        protected SupplierPaymentReferenceService $paymentReferences
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $submittedPayments
     * @return array{payments: array<int, array<string, mixed>>, paid_total: float, credit_requested: float, status: string}
     */
    public function save(
        int $transactionId,
        int $supplierId,
        float $finalTotal,
        array $submittedPayments,
        float $exchangeRate = 1.0,
        string $systemPrefix = 'APEP',
        array $allowedPreservedReferences = []
    ): array {
        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        $exchangeRate = max(0.000001, $exchangeRate);
        $locationId = Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'location_id')
            ? (int) DB::table('transactions')->where('id', $transactionId)->value('location_id')
            : 0;
        $saved = [];
        $paidTotal = 0.0;
        $creditRequested = 0.0;
        $reservedByAccount = [];

        foreach ($submittedPayments as $index => $payment) {
            $method = (string) ($payment['method'] ?? '');
            $enteredAmount = max(0, $this->numbers->number($payment['amount'] ?? 0));
            if ($method === '' || $enteredAmount <= 0) {
                continue;
            }

            $amount = round($enteredAmount * $exchangeRate, 6);

            // IS2112: Credit Purchase is not a real payment. It represents the
            // residual supplier due and must never make a valid purchase fail because
            // the browser-calculated displayed total differs from the server total by
            // fractions of a cent. Ignore the submitted credit amount for validation;
            // the exact residual due is derived after all actual payments are saved.
            if ($method === 'credit_purchase') {
                continue;
            }

            $remaining = max(0.0, round($finalTotal - $paidTotal, 6));
            $proposedTotal = round($paidTotal + $amount, 6);
            if ($proposedTotal > round($finalTotal, 6) + 0.01) {
                throw new \InvalidArgumentException('Actual payments cannot exceed the purchase total.');
            }

            // If the only excess is within the accepted one-cent rounding tolerance,
            // cap the last real payment to the exact remaining amount so ledgers and
            // account books can never be over-posted by a rounding fraction.
            if ($amount > $remaining) {
                $amount = $remaining;
            }
            if ($amount <= 0.000001) {
                continue;
            }

            if (! Schema::hasTable('transaction_payments')) {
                throw new \RuntimeException('The transaction_payments table is required to save purchase payments.');
            }

            $accountId = (int) ($payment['account_id'] ?? 0);
            $requiredFromAccount = $amount + (float) ($reservedByAccount[$accountId] ?? 0);
            $this->validateAccount($businessId, $locationId, $method, $accountId, $requiredFromAccount, $index);
            $reservedByAccount[$accountId] = $requiredFromAccount;

            $paidOn = $this->numbers->dateTime($payment['paid_on'] ?? now())->format('Y-m-d H:i:s');
            $externalReference = trim((string) ($payment['reference_no'] ?? '')) ?: null;
            $preservedReference = trim((string) ($payment['payment_ref_no'] ?? ''));
            $canPreserve = $this->paymentReferences->isSystemReference($preservedReference)
                && in_array($preservedReference, $allowedPreservedReferences, true);
            $paymentReference = $canPreserve
                ? $preservedReference
                : $this->paymentReferences->next($systemPrefix, $businessId, $paidOn);
            $payload = $this->schema->filter('transaction_payments', [
                'transaction_id' => $transactionId,
                'business_id' => $businessId,
                'amount' => $amount,
                'method' => $method,
                'transaction_no' => $externalReference,
                'reference_no' => $externalReference,
                'cheque_number' => trim((string) ($payment['cheque_number'] ?? '')) ?: null,
                'cheque_date' => $this->numbers->date($payment['cheque_date'] ?? null),
                'bank_name' => trim((string) ($payment['bank_name'] ?? '')) ?: null,
                'bank_account_number' => trim((string) ($payment['bank_account_number'] ?? '')) ?: null,
                'transfer_date' => $this->numbers->date($payment['transfer_date'] ?? null),
                'card_transaction_number' => trim((string) ($payment['card_transaction_number'] ?? '')) ?: null,
                'card_number' => trim((string) ($payment['card_number'] ?? '')) ?: null,
                'card_type' => trim((string) ($payment['card_type'] ?? '')) ?: null,
                'card_holder_name' => trim((string) ($payment['card_holder_name'] ?? '')) ?: null,
                'paid_on' => $paidOn,
                'created_by' => $userId,
                'payment_for' => $supplierId,
                'note' => trim((string) ($payment['note'] ?? '')) ?: null,
                'payment_ref_no' => $paymentReference,
                'account_id' => $accountId,
                'is_advance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paymentId = (int) DB::table('transaction_payments')->insertGetId($payload);
            $paidTotal += $amount;
            $saved[] = [
                'payment_id' => $paymentId,
                'account_id' => $accountId,
                'amount' => $amount,
                'method' => $method,
                'payment_ref_no' => $paymentReference,
            ];
        }

        // The unpaid balance itself is the credit purchase amount. Deriving it
        // here keeps the server authoritative and prevents stale/formatted UI values
        // from blocking Save Purchase Entry.
        $creditRequested = max(0.0, round($finalTotal - $paidTotal, 6));

        $status = $paidTotal <= 0.000001
            ? 'due'
            : ($paidTotal + 0.01 >= $finalTotal ? 'paid' : 'partial');

        return [
            'payments' => $saved,
            'paid_total' => round($paidTotal, 6),
            'credit_requested' => round($creditRequested, 6),
            'status' => $status,
        ];
    }

    protected function validateAccount(
        int $businessId,
        int $locationId,
        string $method,
        int $accountId,
        float $requiredAmount,
        int $index
    ): void {
        if ($accountId <= 0 || ! Schema::hasTable('accounts')) {
            throw new \InvalidArgumentException('Select a valid payment account for payment row ' . ($index + 1) . '.');
        }

        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('id', $accountId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id'];
        if (Schema::hasColumn('accounts', 'asset_type')) {
            $columns[] = 'asset_type';
        }
        $account = $query->first($columns);
        if (! $account) {
            throw new \InvalidArgumentException('The selected payment account is not available for this business.');
        }

        $linkedIds = $this->linkedPaymentIds($businessId, $locationId, $method);
        if ($linkedIds === []) {
            throw new \InvalidArgumentException(sprintf(
                'No payment account is linked to %s for the selected business location.',
                ucwords(str_replace('_', ' ', $method))
            ));
        }

        $groupId = isset($account->asset_type) ? (int) $account->asset_type : 0;
        if (! in_array((int) $account->id, $linkedIds, true)
            && ($groupId <= 0 || ! in_array($groupId, $linkedIds, true))) {
            throw new \InvalidArgumentException(sprintf(
                'The selected payment account is not linked to %s for the selected business location.',
                ucwords(str_replace('_', ' ', $method))
            ));
        }

        if ($this->accounting->isCashAccount($businessId, $accountId)) {
            $balance = $this->accounting->accountBalance($businessId, $accountId);
            if ($balance + 0.01 < $requiredAmount) {
                throw new \InvalidArgumentException(sprintf(
                    'Insufficient cash account balance. Available: %.2f, required: %.2f.',
                    $balance,
                    $requiredAmount
                ));
            }
        }
    }

    /** @return array<int, int> */
    protected function linkedPaymentIds(int $businessId, int $locationId, string $method): array
    {
        if ($locationId <= 0
            || ! Schema::hasTable('business_locations')
            || ! Schema::hasColumn('business_locations', 'default_payment_accounts')) {
            return [];
        }

        $json = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->where('id', $locationId)
            ->value('default_payment_accounts');
        $settings = json_decode((string) $json, true);
        if (! is_array($settings)) {
            return [];
        }

        // IS2341: validate the EXACT configured method. Semantic aliases must
        // never allow a disabled method (for example bank_transfer) to borrow
        // the account of another enabled method (for example direct_bank_deposit).
        $wanted = $this->normaliseMethodKey($method);
        $config = null;
        foreach ($settings as $storedMethod => $storedConfig) {
            if ($this->normaliseMethodKey((string) $storedMethod) === $wanted && is_array($storedConfig)) {
                $config = $storedConfig;
                break;
            }
        }

        if (! is_array($config)
            || ! $this->truthy($config['is_enabled'] ?? 0)
            || ! $this->truthy($config['is_purchase_enabled'] ?? 0)) {
            return [];
        }

        $ids = [];
        $value = $config['account'] ?? $config['accounts'] ?? $config['account_ids'] ?? null;
        $values = is_array($value) ? $value : [$value];
        array_walk_recursive($values, static function ($item) use (&$ids): void {
            if (is_numeric($item) && (int) $item > 0) {
                $ids[] = (int) $item;
            }
        });

        return array_values(array_unique($ids));
    }


    protected function normaliseMethodKey(string $method): string
    {
        return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', trim($method)), '_'));
    }

    /** @return array<int, string> */
    protected function methodAliases(string $method): array
    {
        $method = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $method)));
        $aliases = [
            'cash' => ['cash'],
            'bank_transfer' => ['bank_transfer', 'direct_bank_deposit'],
            'cheque' => ['cheque'],
            'card' => ['card', 'own_cards'],
            'advance' => ['advance', 'pre_payments', 'prepayment'],
            'prepayment' => ['prepayment', 'pre_payments', 'advance'],
            'other' => ['other'],
            'credit_purchase' => ['credit_purchase', 'credit_purchase_due', 'credit', 'pay_later'],
        ];

        return array_values(array_unique(array_merge([$method], $aliases[$method] ?? [])));
    }

    protected function truthy(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'true', 'yes', 'on'], true);
    }

}
