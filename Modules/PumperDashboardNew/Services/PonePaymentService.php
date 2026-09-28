<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneCreditSale;
use Modules\PumperDashboardNew\Entities\PonePayment;
use Modules\PumperDashboardNew\Entities\PonePaymentEditHistory;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;
use Modules\PumperDashboardNew\Utils\PoneDecimal;

class PonePaymentService
{
    public function __construct(
        private PoneContextService $context,
        private PoneNumberSequenceService $numbers,
        private PoneSharedMasterDataService $masterData,
        private PoneSettingsService $settings,
        private PoneShiftTotalsService $totals,
        private PoneOperatorLedgerService $ledger,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function create(array $data): PonePayment
    {
        return DB::transaction(function () use ($data): PonePayment {
            $shift = $this->context->shift();
            $type = (string) $data['payment_type'];
            $config = $this->settings->get($shift->business_id, $shift->location_id);
            $this->assertTypeEnabled($type, $config);
            [$amounts, $cashRows, $cardRows] = $this->resolveAmounts($type, $data, $config);
            $customerId = $this->validatedCustomerId($shift->business_id, $shift->location_id, $data['customer_id'] ?? null, $type === 'credit');
            $accountId = $this->validatedAccountId($shift->business_id, $data['account_id'] ?? null);
            $this->validateCardAccounts($shift->business_id, $cardRows);

            $payment = PonePayment::query()->create([
                'uuid' => Str::uuid()->toString(),
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'payment_number' => $this->numbers->next($shift->business_id, $shift->location_id, 'payment'),
                'collection_form_no' => $data['collection_form_no'] ?? $shift->collection_form_no,
                'parent_payment_id' => $data['parent_payment_id'] ?? null,
                'account_id' => $accountId,
                'payment_type' => $type,
                'gross_amount' => $amounts['gross'],
                'discount_amount' => $amounts['discount'],
                'amount' => $amounts['amount'],
                'customer_id' => $customerId,
                'reference_no' => $data['reference_no'] ?? null,
                'card_type' => $data['card_type'] ?? ($cardRows[0]['card_type'] ?? null),
                'card_last_four' => $data['card_last_four'] ?? ($cardRows[0]['last_four'] ?? null),
                'slip_no' => $data['slip_no'] ?? ($cardRows[0]['slip_no'] ?? null),
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'transaction_at' => $data['transaction_at'] ?? now(),
                'note' => $data['note'] ?? null,
                'status' => 'confirmed',
                'integration_status' => 'pending',
                'edit_version' => 1,
                'created_by' => $this->context->userId(),
            ]);

            $this->replaceCashRows($payment, $cashRows);
            $this->replaceCardRows($payment, $cardRows);
            if ($type === 'credit') {
                $data['customer_id'] = $customerId;
                $this->createCreditSale($payment, $data, collect($data['lines'] ?? [])->all(), $config);
            }

            $fresh = $payment->fresh($this->relations());
            $this->bridge->syncPayment($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('payment.created', 'pone_payment', $payment->id, null, $fresh);
            return $fresh;
        }, 3);
    }

    public function update(int $paymentId, array $data): PonePayment
    {
        return DB::transaction(function () use ($paymentId, $data): PonePayment {
            $shift = $this->context->shift();
            $payment = PonePayment::query()->whereKey($paymentId)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->with($this->relations())->lockForUpdate()->firstOrFail();
            $config = $this->settings->get($shift->business_id, $shift->location_id);
            $this->assertEditable($payment, $config);
            if ((string) ($data['payment_type'] ?? $payment->payment_type) !== $payment->payment_type) {
                throw ValidationException::withMessages(['payment_type' => __('pumperdashboardnew::lang.payment_type_cannot_change')]);
            }
            $reason = trim((string) ($data['edit_reason'] ?? ''));
            if ($reason === '') throw ValidationException::withMessages(['edit_reason' => __('pumperdashboardnew::lang.edit_reason_required')]);

            $before = $payment->toArray();
            [$amounts, $cashRows, $cardRows] = $this->resolveAmounts($payment->payment_type, $data, $config);
            $customerId = $this->validatedCustomerId(
                $shift->business_id,
                $shift->location_id,
                array_key_exists('customer_id', $data) ? $data['customer_id'] : $payment->customer_id,
                $payment->payment_type === 'credit'
            );
            $accountId = $this->validatedAccountId(
                $shift->business_id,
                array_key_exists('account_id', $data) ? $data['account_id'] : $payment->account_id
            );
            $this->validateCardAccounts($shift->business_id, $cardRows);
            $version = (int) $payment->edit_version + 1;
            $payment->update([
                'collection_form_no' => $data['collection_form_no'] ?? $payment->collection_form_no,
                'account_id' => $accountId,
                'gross_amount' => $amounts['gross'],
                'discount_amount' => $amounts['discount'],
                'amount' => $amounts['amount'],
                'customer_id' => $customerId,
                'reference_no' => $data['reference_no'] ?? null,
                'card_type' => $data['card_type'] ?? ($cardRows[0]['card_type'] ?? null),
                'card_last_four' => $data['card_last_four'] ?? ($cardRows[0]['last_four'] ?? null),
                'slip_no' => $data['slip_no'] ?? ($cardRows[0]['slip_no'] ?? null),
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'transaction_at' => $data['transaction_at'] ?? $payment->transaction_at,
                'note' => $data['note'] ?? null,
                'edit_version' => $version,
                'edit_reason' => $reason,
                'edited_by' => $this->context->userId(),
                'edited_at' => now(),
                'integration_status' => 'pending',
            ]);
            $this->replaceCashRows($payment, $cashRows);
            $this->replaceCardRows($payment, $cardRows);
            $after = $payment->fresh($this->relations())->toArray();
            PonePaymentEditHistory::query()->create([
                'payment_id' => $payment->id,
                'business_id' => $payment->business_id,
                'version_no' => $version,
                'reason' => $reason,
                'before_data' => $before,
                'after_data' => $after,
                'edited_by' => $this->context->userId(),
                'edited_at' => now(),
            ]);
            $fresh = $payment->fresh($this->relations());
            $this->bridge->syncPayment($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('payment.updated', 'pone_payment', $payment->id, $before, $fresh);
            return $fresh;
        }, 3);
    }

    public function void(int $paymentId, ?string $reason = null): PonePayment
    {
        return DB::transaction(function () use ($paymentId, $reason): PonePayment {
            $shift = $this->context->shift();
            $payment = PonePayment::query()->whereKey($paymentId)->where('shift_id', $shift->id)
                ->where('business_id', $this->context->businessId())->with($this->relations())->lockForUpdate()->firstOrFail();
            if ($payment->status === 'void') return $payment;
            $reason = trim((string) $reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            $before = $payment->toArray();
            $payment->update([
                'status' => 'void',
                'note' => $reason,
                'voided_by' => $this->context->userId(),
                'voided_at' => now(),
                'integration_status' => 'pending',
            ]);
            $fresh = $payment->fresh($this->relations());
            $this->bridge->voidPayment($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('payment.voided', 'pone_payment', $payment->id, $before, $fresh);
            return $fresh;
        }, 3);
    }

    private function resolveAmounts(string $type, array $data, array $config): array
    {
        $cashRows = [];
        $cardRows = [];
        if ($type === 'credit') {
            $this->validateCreditConfirmation($data, $config);
            $amount = collect($data['lines'] ?? [])->sum(fn (array $line): float => $this->lineAmount($line));
            if ($amount <= 0) throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.credit_lines_required')]);
            return [['gross' => round($amount, 4), 'discount' => 0.0, 'amount' => round($amount, 4)], $cashRows, $cardRows];
        }
        if ($type === 'cash' && ($config['cash_denomination_enabled'] ?? true) && ! empty($data['cash_denominations'])) {
            $cashRows = $this->normaliseCashRows((array) $data['cash_denominations']);
            $amount = round(array_sum(array_column($cashRows, 'amount')), 4);
            if ($amount <= 0) throw ValidationException::withMessages(['cash_denominations' => __('pumperdashboardnew::lang.cash_denomination_required')]);
            return [['gross' => $amount, 'discount' => 0.0, 'amount' => $amount], $cashRows, $cardRows];
        }
        if ($type === 'card' && ! empty($data['card_lines'])) {
            $cardRows = $this->normaliseCardRows((array) $data['card_lines']);
            if (! ($config['multi_card_enabled'] ?? true) && count($cardRows) > 1) {
                throw ValidationException::withMessages(['card_lines' => __('pumperdashboardnew::lang.multiple_cards_disabled')]);
            }
            $amount = round(array_sum(array_column($cardRows, 'amount')), 4);
            if ($amount <= 0) throw ValidationException::withMessages(['card_lines' => __('pumperdashboardnew::lang.card_line_required')]);
            return [['gross' => $amount, 'discount' => 0.0, 'amount' => $amount], $cashRows, $cardRows];
        }
        if ($type === 'cheque' && (empty($data['cheque_no']) || empty($data['cheque_date']))) {
            throw ValidationException::withMessages(['cheque_no' => __('pumperdashboardnew::lang.cheque_details_required')]);
        }
        $gross = PoneDecimal::money($data['gross_amount'] ?? $data['amount'] ?? 0);
        $discount = PoneDecimal::money($data['discount_amount'] ?? 0);
        $amount = PoneDecimal::money($gross - $discount);
        if ($amount <= 0) throw ValidationException::withMessages(['amount' => __('pumperdashboardnew::lang.amount_must_be_positive')]);
        return [['gross' => $gross, 'discount' => $discount, 'amount' => $amount], $cashRows, $cardRows];
    }

    private function createCreditSale(PonePayment $payment, array $data, array $lines, array $config): void
    {
        $dual = (bool) ($config['require_credit_dual_confirmation'] ?? true);
        $credit = PoneCreditSale::query()->create([
            'payment_id' => $payment->id,
            'business_id' => $payment->business_id,
            'customer_id' => (int) $data['customer_id'],
            'order_number' => trim((string) $data['order_number']),
            'bill_number' => $data['bill_number'] ?? null,
            'vehicle_number' => trim((string) $data['vehicle_number']),
            'customer_reference' => $data['customer_reference'] ?? null,
            'order_date' => $data['order_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? null,
            'customer_confirmed' => true,
            'order_confirmed' => true,
            'vehicle_confirmed' => true,
            'customer_confirmed_at' => now(),
            'order_confirmed_at' => now(),
            'vehicle_confirmed_at' => now(),
            'confirmation_rounds' => $dual ? 2 : 1,
            'locked_at' => now(),
            'confirmed_by' => $this->context->userId(),
            'note' => $data['note'] ?? null,
        ]);
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $product = $this->masterData->product($payment->business_id, $productId, $payment->location_id);
            if (! $product) throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.invalid_product')]);
            $quantity = PoneDecimal::quantity($line['quantity'] ?? 0);
            $unitPrice = PoneDecimal::unitPrice($line['unit_price'] ?? $product->unit_price ?? 0);
            $discount = PoneDecimal::money($line['discount_amount'] ?? 0);
            $amount = PoneDecimal::lineTotal($quantity, $unitPrice, $discount);
            if ($quantity <= 0 || $amount <= 0) throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.invalid_sale_line')]);
            $credit->lines()->create(['product_id' => $productId, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'discount_amount' => $discount, 'amount' => $amount]);
        }
    }

    private function normaliseCashRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $denomination = round((float) ($row['denomination'] ?? 0), 2);
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($denomination <= 0 || $quantity <= 0) continue;
            $result[] = ['denomination' => $denomination, 'quantity' => $quantity, 'amount' => round($denomination * $quantity, 4)];
        }
        return $result;
    }

    private function normaliseCardRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $amount = round((float) ($row['amount'] ?? 0), 4);
            if ($amount <= 0) continue;
            $lastFour = preg_replace('/\D+/', '', (string) ($row['last_four'] ?? ''));
            if ($lastFour !== '' && strlen($lastFour) !== 4) throw ValidationException::withMessages(['card_lines' => __('pumperdashboardnew::lang.card_last_four_invalid')]);
            $result[] = [
                'card_type' => $row['card_type'] ?? null,
                'last_four' => $lastFour ?: null,
                'slip_no' => $row['slip_no'] ?? null,
                'account_id' => ! empty($row['account_id']) ? (int) $row['account_id'] : null,
                'reference_no' => $row['reference_no'] ?? null,
                'amount' => $amount,
            ];
        }
        return $result;
    }

    private function replaceCashRows(PonePayment $payment, array $rows): void
    {
        $payment->cashDenominations()->delete();
        foreach ($rows as $row) $payment->cashDenominations()->create($row);
    }

    private function replaceCardRows(PonePayment $payment, array $rows): void
    {
        $payment->cardLines()->delete();
        foreach ($rows as $row) $payment->cardLines()->create($row);
    }

    private function validateCreditConfirmation(array $data, array $config): void
    {
        if (! ($config['require_credit_dual_confirmation'] ?? true)) return;
        $confirmed = filter_var($data['customer_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && filter_var($data['order_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && filter_var($data['vehicle_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && (int) ($data['confirmation_rounds'] ?? 0) >= 2;
        if (! $confirmed) throw ValidationException::withMessages(['confirmation_rounds' => __('pumperdashboardnew::lang.credit_double_confirmation_required')]);
    }

    private function validatedCustomerId(int $businessId, ?int $locationId, mixed $value, bool $required): ?int
    {
        $customerId = (int) ($value ?? 0);
        if ($customerId <= 0) {
            if ($required) throw ValidationException::withMessages(['customer_id' => __('pumperdashboardnew::lang.invalid_customer')]);
            return null;
        }
        if (! $this->masterData->customer($businessId, $customerId, $locationId)) {
            throw ValidationException::withMessages(['customer_id' => __('pumperdashboardnew::lang.invalid_customer')]);
        }
        return $customerId;
    }

    private function validatedAccountId(int $businessId, mixed $value): ?int
    {
        $accountId = (int) ($value ?? 0);
        if ($accountId <= 0) return null;
        if (! $this->masterData->account($businessId, $accountId)) {
            throw ValidationException::withMessages(['account_id' => __('pumperdashboardnew::lang.invalid_account')]);
        }
        return $accountId;
    }

    private function validateCardAccounts(int $businessId, array $rows): void
    {
        foreach ($rows as $index => $row) {
            $accountId = (int) ($row['account_id'] ?? 0);
            if ($accountId > 0 && ! $this->masterData->account($businessId, $accountId)) {
                throw ValidationException::withMessages([
                    'card_lines.' . $index . '.account_id' => __('pumperdashboardnew::lang.invalid_account'),
                ]);
            }
        }
    }

    private function assertTypeEnabled(string $type, array $config): void
    {
        if ($type === 'cheque' && ! ($config['cheque_enabled'] ?? true)) throw ValidationException::withMessages(['payment_type' => __('pumperdashboardnew::lang.cheque_disabled')]);
        if ($type === 'credit' && ! ($config['credit_sale_enabled'] ?? true)) throw ValidationException::withMessages(['payment_type' => __('pumperdashboardnew::lang.credit_disabled')]);
    }

    private function assertEditable(PonePayment $payment, array $config): void
    {
        if (! ($config['allow_payment_edit'] ?? true)) throw ValidationException::withMessages(['payment' => __('pumperdashboardnew::lang.payment_edit_disabled')]);
        if ($payment->status !== 'confirmed') throw ValidationException::withMessages(['payment' => __('pumperdashboardnew::lang.only_confirmed_payment_editable')]);
        if ($payment->payment_type === 'credit' || $payment->creditSale?->locked_at) throw ValidationException::withMessages(['payment' => __('pumperdashboardnew::lang.credit_sale_locked')]);
        $minutes = (int) ($config['payment_edit_lock_minutes'] ?? 1440);
        if ($minutes > 0 && $payment->created_at && $payment->created_at->diffInMinutes(now()) > $minutes) {
            throw ValidationException::withMessages(['payment' => __('pumperdashboardnew::lang.payment_edit_period_expired')]);
        }
    }

    private function lineAmount(array $line): float
    {
        return PoneDecimal::lineTotal($line['quantity'] ?? 0, $line['unit_price'] ?? 0, $line['discount_amount'] ?? 0);
    }

    private function relations(): array
    {
        return ['creditSale.lines', 'cashDenominations', 'cardLines', 'editHistories'];
    }
}
