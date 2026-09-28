<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseEntryAddPaymentService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseEntryFormService $form,
        protected PurchaseEntryPaymentService $payments,
        protected PurchaseEntryAccountingService $accounting,
        protected SupplierPaymentReferenceService $paymentReferences
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(int $id): array
    {
        $purchase = $this->purchase($id, false);
        $paid = $this->paidTotal($id);
        $due = max(0, round((float) ($purchase->final_total ?? 0) - $paid, 6));
        if ($due <= 0.000001) {
            throw new \InvalidArgumentException('This purchase is already fully paid.');
        }

        $businessId = $this->numbers->businessId();
        $supplierName = '';
        if (Schema::hasTable('contacts') && ! empty($purchase->contact_id)) {
            $supplier = DB::table('contacts')->where('id', (int) $purchase->contact_id)->first(['name', 'supplier_business_name']);
            $supplierName = trim((string) (($supplier->supplier_business_name ?? '') ?: ($supplier->name ?? '')));
        }

        $locationName = '';
        if (Schema::hasTable('business_locations') && ! empty($purchase->location_id)) {
            $locationName = (string) (DB::table('business_locations')->where('id', (int) $purchase->location_id)->value('name') ?? '');
        }

        $methods = $this->form->paymentMethodsForLocation(
            $businessId,
            (int) ($purchase->location_id ?? 0)
        );
        unset($methods['credit_purchase']);

        if ($methods === []) {
            throw new \InvalidArgumentException(
                'No active Purchase payment method is assigned to this business location in Payment Options.'
            );
        }

        return [
            'purchase' => $purchase,
            'purchase_no' => (string) (($purchase->invoice_no ?? '') ?: ($purchase->purchase_entry_no ?? ('PUR-' . $purchase->id))),
            'supplier_name' => $supplierName,
            'location_name' => $locationName,
            'paid_total' => $paid,
            'due_total' => $due,
            'payment_methods' => $methods,
            'accounts' => $this->form->paymentAccounts($businessId),
            'payment_method_accounts' => $this->form->paymentMethodAccounts($businessId),
            'paid_on' => now()->format('Y-m-d\TH:i'),
            // S710 v2: show the configured LPEP next reference before Save.
            'payment_ref_preview' => $this->paymentReferences->preview('LPEP', $businessId, now()),
        ];
    }

    /** @param array<string, mixed> $data
     *  @return array{payment_id:int,payment_ref_no:string,payment_status:string,paid_total:float,due_total:float}
     */
    public function store(int $id, array $data): array
    {
        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        if ($businessId <= 0 || $userId <= 0) {
            throw new \RuntimeException('The business session is not available. Please sign in again.');
        }

        return DB::transaction(function () use ($id, $data, $businessId, $userId): array {
            $purchase = $this->purchase($id, true);
            $existingPaid = $this->paidTotal($id);
            $finalTotal = max(0, (float) ($purchase->final_total ?? 0));
            $due = max(0, round($finalTotal - $existingPaid, 6));
            if ($due <= 0.000001) {
                throw new \InvalidArgumentException('This purchase is already fully paid.');
            }

            $amount = max(0, $this->numbers->number($data['amount'] ?? 0));
            if ($amount <= 0.000001) {
                throw new \InvalidArgumentException('Enter a payment amount greater than zero.');
            }
            if ($amount > $due + 0.01) {
                throw new \InvalidArgumentException(sprintf('Payment cannot exceed the current due amount of %.2f.', $due));
            }

            $method = trim((string) ($data['method'] ?? ''));
            if ($method === '' || $method === 'credit_purchase') {
                throw new \InvalidArgumentException('Select a valid payment method.');
            }

            // reference_no is an optional external/bank/slip reference.
            // The permanent ERP payment identity is generated server-side as LPEPYYYY-####.
            $externalReference = trim((string) ($data['reference_no'] ?? ''));

            $payment = [
                'method' => $method,
                'amount' => $amount,
                'account_id' => (int) ($data['account_id'] ?? 0),
                'paid_on' => $data['paid_on'] ?? now(),
                'reference_no' => $externalReference,
                'note' => trim((string) ($data['note'] ?? '')),
                'cheque_number' => trim((string) ($data['cheque_number'] ?? '')),
                'cheque_date' => $data['cheque_date'] ?? null,
                'bank_name' => trim((string) ($data['bank_name'] ?? '')),
                'bank_account_number' => trim((string) ($data['bank_account_number'] ?? '')),
                'transfer_date' => $data['transfer_date'] ?? null,
                'card_transaction_number' => trim((string) ($data['card_transaction_number'] ?? '')),
                'card_number' => trim((string) ($data['card_number'] ?? '')),
                'card_type' => trim((string) ($data['card_type'] ?? '')),
                'card_holder_name' => trim((string) ($data['card_holder_name'] ?? '')),
            ];

            // Passing the CURRENT due amount makes the existing payment service enforce
            // the remaining-balance ceiling while still using the module's account validation.
            $saved = $this->payments->save(
                $id,
                (int) ($purchase->contact_id ?? 0),
                $due,
                [$payment],
                1.0,
                'LPEP'
            );
            if (empty($saved['payments'][0]['payment_id'])) {
                throw new \RuntimeException('The payment could not be saved.');
            }

            $savedPayment = $saved['payments'][0];
            $paymentId = (int) $savedPayment['payment_id'];
            $paymentReference = (string) ($savedPayment['payment_ref_no'] ?? '');
            $paidOn = $this->numbers->dateTime($payment['paid_on'])->format('Y-m-d H:i:s');
            $this->accounting->postAdditionalPayment(
                $businessId,
                (int) ($purchase->location_id ?? 0),
                $userId,
                $id,
                $paymentId,
                (int) $savedPayment['account_id'],
                (float) $savedPayment['amount'],
                $paymentReference,
                $paidOn,
                (string) $savedPayment['method']
            );

            $newPaid = $this->paidTotal($id);
            $newDue = max(0, round($finalTotal - $newPaid, 6));
            $status = $newDue <= 0.01 ? 'paid' : ($newPaid > 0.000001 ? 'partial' : 'due');
            DB::table('transactions')->where('id', $id)->update([
                'payment_status' => $status,
                'updated_at' => now(),
            ]);

            return [
                'payment_id' => $paymentId,
                'payment_ref_no' => $paymentReference,
                'payment_status' => $status,
                'paid_total' => $newPaid,
                'due_total' => $newDue,
            ];
        }, 3);
    }

    protected function purchase(int $id, bool $lock): object
    {
        $query = DB::table('transactions')
            ->where('id', $id)
            ->where('business_id', $this->numbers->businessId())
            ->where('type', 'purchase');
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        $purchase = $query->first();
        if (! $purchase) {
            throw new \InvalidArgumentException('The purchase entry was not found.');
        }

        return $purchase;
    }

    protected function paidTotal(int $transactionId): float
    {
        if (! Schema::hasTable('transaction_payments')) {
            return 0.0;
        }
        $query = DB::table('transaction_payments')
            ->where('transaction_id', $transactionId)
            ->where('amount', '>', 0);
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('transaction_payments', 'is_return')) {
            $query->where(function ($inner): void {
                $inner->whereNull('is_return')->orWhere('is_return', 0);
            });
        }

        return (float) $query->sum('amount');
    }

    protected function paymentCount(int $transactionId): int
    {
        if (! Schema::hasTable('transaction_payments')) {
            return 0;
        }
        $query = DB::table('transaction_payments')->where('transaction_id', $transactionId);
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }
}
