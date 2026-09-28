<?php

namespace Modules\Suppliers\Services\Financial;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S763 - keep the user-selected Supplier payment date consistent everywhere.
 *
 * Supplier Pay Due / Advance Payment reuse the ERP's shared payment endpoint.
 * The selected value belongs on BOTH transaction_payments.paid_on and every
 * linked account_transactions.operation_date.  Some older listener sequences
 * saved the payment date correctly but left one or both Finance ledger rows on
 * the system-entry date; other sequences did the reverse.  This guard makes the
 * selected Supplier payment date the single source of truth without changing
 * unrelated customer/payment flows.
 */
class SupplierPaymentDateGuard
{
    /** @var array<int, string> root/payment id => selected paid_on */
    private array $pendingPaymentDates = [];

    public function preparePayment(object $payment): void
    {
        $value = $this->resolvedPaidOnValue();
        if ($value === null) {
            return;
        }

        $this->setPaidOn($payment, $value);
    }

    public function normalizePayment(object $payment): void
    {
        $value = $this->resolvedPaidOnValue();
        if ($value === null || ! Schema::hasTable('transaction_payments')) {
            return;
        }

        $id = (int) ($payment->id ?? 0);
        if ($id <= 0) {
            return;
        }

        // Direct queries avoid a second Eloquent saving/saved event cycle.
        DB::table('transaction_payments')
            ->where('id', $id)
            ->update(['paid_on' => $value]);

        $this->setPaidOn($payment, $value);
        $this->pendingPaymentDates[$id] = $value;
        $this->syncLinkedAccountTransactionDates($id, $value);
    }

    /**
     * The shared Pay Due flow creates allocation children with a bulk INSERT
     * after the parent TransactionPayment saved event has already completed.
     * Bulk INSERT bypasses Eloquent events, so synchronize the whole payment
     * family at request termination.  This makes the user-selected date the
     * single source of truth for parent, allocation children and Finance rows.
     */
    public function flushPending(): void
    {
        if ($this->pendingPaymentDates === [] || ! Schema::hasTable('transaction_payments')) {
            return;
        }

        foreach ($this->pendingPaymentDates as $paymentId => $value) {
            $paymentIds = $this->paymentFamilyIds((int) $paymentId);
            if ($paymentIds === []) {
                continue;
            }

            DB::table('transaction_payments')
                ->whereIn('id', $paymentIds)
                ->update(['paid_on' => $value]);

            if (Schema::hasTable('account_transactions')
                && Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
                DB::table('account_transactions')
                    ->whereIn('transaction_payment_id', $paymentIds)
                    ->update(['operation_date' => $value]);
            }
        }

        $this->pendingPaymentDates = [];
    }

    /**
     * AccountTransaction can be created after TransactionPayment::saved has
     * already fired.  Run this from AccountTransaction::saved as a second gate
     * so the Payment Account and Accounts Payable rows cannot keep "now" as
     * their operation date while the payment itself has the selected date.
     */
    public function normalizeAccountTransaction(object $accountTransaction): void
    {
        $value = $this->resolvedPaidOnValue();
        if ($value === null || ! Schema::hasTable('account_transactions')) {
            return;
        }

        $paymentId = (int) ($accountTransaction->transaction_payment_id ?? 0);
        $accountTransactionId = (int) ($accountTransaction->id ?? 0);
        if ($paymentId <= 0 || $accountTransactionId <= 0) {
            return;
        }

        DB::table('account_transactions')
            ->where('id', $accountTransactionId)
            ->where('transaction_payment_id', $paymentId)
            ->update(['operation_date' => $value]);

        if (method_exists($accountTransaction, 'setAttribute')) {
            $accountTransaction->setAttribute('operation_date', $value);
        } else {
            $accountTransaction->operation_date = $value;
        }
    }

    private function syncLinkedAccountTransactionDates(int $paymentId, string $value): void
    {
        if (! Schema::hasTable('account_transactions')
            || ! Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
            return;
        }

        $paymentIds = $this->paymentFamilyIds($paymentId);
        if ($paymentIds === []) {
            return;
        }

        DB::table('account_transactions')
            ->whereIn('transaction_payment_id', $paymentIds)
            ->update(['operation_date' => $value]);
    }

    /** @return array<int, int> */
    private function paymentFamilyIds(int $paymentId): array
    {
        if (! Schema::hasTable('transaction_payments')) {
            return [];
        }

        $ids = [$paymentId];
        if (! Schema::hasColumn('transaction_payments', 'parent_id')) {
            return $ids;
        }

        $payment = DB::table('transaction_payments')
            ->where('id', $paymentId)
            ->first(['id', 'parent_id']);

        if (! $payment) {
            return [];
        }

        $rootId = ! empty($payment->parent_id) ? (int) $payment->parent_id : (int) $payment->id;
        $ids[] = $rootId;

        $children = DB::table('transaction_payments')
            ->where('parent_id', $rootId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($ids, $children)));
    }

    private function setPaidOn(object $payment, string $value): void
    {
        if (method_exists($payment, 'setAttribute')) {
            $payment->setAttribute('paid_on', $value);
        } else {
            $payment->paid_on = $value;
        }
    }

    private function resolvedPaidOnValue(): ?string
    {
        if (! $this->isSupplierPaymentRequest()) {
            return null;
        }

        // The Suppliers-owned ISO marker is authoritative and deliberately
        // bypasses business display-format parsing.  This removes the ambiguity
        // that can turn a selected 2026 date into an unrelated future year.
        $iso = request()->input('supplier_paid_on_iso');
        if (is_scalar($iso) && trim((string) $iso) !== '') {
            $paidOn = $this->parseIsoPaidOn(trim((string) $iso));

            return $paidOn ? $paidOn->format('Y-m-d H:i:s') : null;
        }

        $raw = request()->input('paid_on');
        $raw = is_scalar($raw) ? trim((string) $raw) : '';
        if ($raw === '') {
            return null;
        }

        $paidOn = $this->parsePaidOn($raw);

        return $paidOn ? $paidOn->format('Y-m-d H:i:s') : null;
    }

    /**
     * The explicit Suppliers JS marker is authoritative.  The route fallback is
     * intentionally supplier-checked so a normal Customer Pay Due request is
     * never changed just because it uses the same core endpoint.
     */
    private function isSupplierPaymentRequest(): bool
    {
        if (! function_exists('request')) {
            return false;
        }

        $context = strtolower(trim((string) request()->input('supplier_payment_context', '')));
        if (in_array($context, ['pay_due', 'advance_payment'], true)) {
            return true;
        }

        try {
            if (! request()->is('payments/pay-contact-due*')
                && ! request()->is('payments/advance-payment*')) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        $contactId = (int) request()->input('contact_id', 0);
        if ($contactId <= 0 || ! Schema::hasTable('contacts')) {
            return false;
        }

        $query = DB::table('contacts')->where('id', $contactId);
        if (Schema::hasColumn('contacts', 'business_id')) {
            $businessId = (int) request()->session()->get('user.business_id', request()->session()->get('business.id', 0));
            if ($businessId > 0) {
                $query->where('business_id', $businessId);
            }
        }

        $type = strtolower((string) $query->value('type'));

        return in_array($type, ['supplier', 'both'], true);
    }

    private function parseIsoPaidOn(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Native datetime-local posts no timezone. Treat it as the business/user
        // local wall-clock value and parse it strictly; never let Carbon guess.
        foreach ([
            ['!Y-m-d\\TH:i:s', 'Y-m-d\\TH:i:s'],
            ['!Y-m-d\\TH:i', 'Y-m-d\\TH:i'],
            ['!Y-m-d H:i:s', 'Y-m-d H:i:s'],
            ['!Y-m-d H:i', 'Y-m-d H:i'],
        ] as [$format, $roundTripFormat]) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false && $parsed->format($roundTripFormat) === $value) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Try the next exact ISO form.
            }
        }

        return null;
    }

    private function parsePaidOn(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i',
            'Y-m-d H:i:s', 'Y-m-d H:i',
            'Y-m-d h:i A', 'Y-m-d h:i a',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y h:i A', 'd/m/Y h:i a',
            'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y h:i A', 'm/d/Y h:i a',
            'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y h:i A', 'd-m-Y h:i a',
            'm-d-Y H:i:s', 'm-d-Y H:i', 'm-d-Y h:i A', 'm-d-Y h:i a',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat('!' . $format, $value);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Try next accepted format.
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
