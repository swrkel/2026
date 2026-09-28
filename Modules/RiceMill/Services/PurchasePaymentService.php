<?php
namespace Modules\RiceMill\Services;

use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{PurchasePayment,Setting,PaddyPurchase};

/**
 * Rice Mill adapter for the ERP's standard Purchase payment engine.
 *
 * The Rice Mill Purchase Order remains in rcm_* tables, but its payment is
 * mirrored through the same transactions / transaction_payments workflow used
 * by the core Purchase module. This preserves Supplier Ledger, Finance account
 * books, cheque/bank handling and payment-status behaviour without writing
 * standalone account_transactions from Rice Mill.
 */
class PurchasePaymentService
{
    public function __construct(
        private ExternalMasterDataService $masters,
        private TransactionUtil $transactionUtil,
        private FinanceAccountPostingService $financeAccounts
    ) {}

    public function post(int $businessId, int $userId, PaddyPurchase $purchase, array $input): PurchasePayment
    {
        foreach (['transactions','transaction_payments','account_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'The standard Purchase payment tables are not available in this tenant database (missing '.$table.').',
                ]);
            }
        }
        if (! Schema::hasTable('rcm_purchase_payments')) {
            throw ValidationException::withMessages([
                'payment_method' => 'Rice Mill Purchase Payment database update is not installed. Please import the supplied incremental SQL or run the Rice Mill migrations.',
            ]);
        }

        $setting = Setting::forBusiness($businessId)->select(['settings'])->first();
        $settings = (array) optional($setting)->settings;
        $mappingEnabled = array_key_exists('paddy_product_category_mapping_enabled', $settings)
            ? (bool) $settings['paddy_product_category_mapping_enabled']
            : true;
        if (! $mappingEnabled) {
            throw ValidationException::withMessages([
                'payment_account_id' => 'The Paddy Product Category Mapping is disabled. Enable it under Rice Mill / Settings / Product Category Mapping before saving a Purchase Order.',
            ]);
        }

        $payableAccountId = (int) ($settings['paddy_payment_account_id'] ?? 0);
        $payableAccounts = collect($this->masters->currentLiabilityAccounts($businessId))->keyBy('id');
        if ($payableAccountId <= 0 || ! $payableAccounts->has($payableAccountId)) {
            throw ValidationException::withMessages([
                'payment_account_id' => 'Please map an active Current Liabilities account for Paddy under Rice Mill / Settings / Product Category Mapping before saving a Purchase Order.',
            ]);
        }

        $method = trim((string) ($input['payment_method'] ?? ''));
        $paymentAccountId = (int) ($input['payment_account_id'] ?? 0);
        $locationId = $purchase->location_id ? (int) $purchase->location_id : null;
        $map = $this->masters->purchasePaymentMethodAccounts($businessId);

        // Credit Purchase is a standard Purchase-module option. At Purchase
        // Order stage it remains Due and uses the mapped Paddy A/P account as
        // the displayed account; no actual payment line is created by the core
        // TransactionUtil until money is paid.
        $map['methods']['credit_purchase'] = $map['methods']['credit_purchase'] ?? 'Credit Purchase (Due)';
        $map['accounts']['credit_purchase'][$payableAccountId] = (string) $payableAccounts[$payableAccountId]['name'];

        $allowed = $locationId
            ? array_keys($map['by_location'][$locationId][$method] ?? [])
            : array_keys($map['accounts'][$method] ?? []);
        if ($method === 'credit_purchase') {
            $allowed[] = $payableAccountId;
        }
        $allowed = array_values(array_unique(array_map('intval', $allowed)));

        if ($method === '' || ! isset($map['methods'][$method])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Please select a Payment Method enabled for purchases.',
            ]);
        }
        if ($paymentAccountId <= 0 || ! in_array($paymentAccountId, $allowed, true)) {
            throw ValidationException::withMessages([
                'payment_account_id' => 'Please select a Payment Account linked to the selected Payment Method.',
            ]);
        }

        $amount = round((float) $purchase->net_total, 4);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'lines' => 'The Purchase Order total must be greater than zero.',
            ]);
        }

        $existing = PurchasePayment::forBusiness($businessId)->where('purchase_id', $purchase->id)->first();
        if ($existing && ! empty($existing->transaction_id)) {
            $existingTransaction = Transaction::where('business_id',$businessId)->find((int)$existing->transaction_id);
            if ($existingTransaction) {
                $this->financeAccounts->syncPurchaseOrder(
                    $businessId,$userId,$purchase,$existingTransaction,
                    (string)($existing->payment_method ?: $method),
                    (int)($existing->payment_account_id ?: $paymentAccountId),
                    (int)($existing->payable_account_id ?: $payableAccountId)
                );
            }
            return $existing;
        }

        $transaction = $this->createStandardPurchaseOrderTransaction($businessId, $userId, $purchase);

        $paymentNote = trim((string) ($input['payment_note'] ?? ''));
        $chequeNo = trim((string) ($input['cheque_number'] ?? ''));
        $payment = [
            'method' => $method,
            'amount' => $amount,
            'account_id' => $paymentAccountId,
            'note' => $this->paymentNote($purchase, $paymentNote),
            'cheque_number' => $chequeNo,
            'cheque_date' => $purchase->purchase_date->format('Y-m-d'),
            'card_transaction_number' => null,
            'bank_name' => null,
            'post_dated_cheque' => 0,
            'update_post_dated_cheque' => 0,
            'transaction_no_1' => '',
            'transaction_no_2' => '',
            'transaction_no_3' => '',
        ];

        // Same standard method used by PurchaseController. Status "ordered"
        // is important: this is a Purchase Order, not received stock.
        $this->transactionUtil->createOrUpdatePaymentLines(
            $transaction,
            [$payment],
            $businessId,
            $userId,
            false,
            'ordered',
            null
        );

        if ($method === 'credit_purchase') {
            $this->transactionUtil->updatePaymentStatus($transaction->id, $amount, 'credit_purchase', $amount);
            $transaction->payment_status = 'due';
            $transaction->save();
        } else {
            $this->transactionUtil->updatePaymentStatus($transaction->id, $amount);
            $transaction->refresh();
        }

        $transactionPayment = TransactionPayment::where('transaction_id', $transaction->id)
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();

        $paidAmount = $method === 'credit_purchase' ? 0.0 : $amount;
        $auditData = [
            'business_id' => $businessId,
            'purchase_id' => (int) $purchase->id,
            'payment_date' => $purchase->purchase_date,
            'payment_method' => $method,
            'payment_method_label' => (string) ($map['methods'][$method] ?? $method),
            'payable_account_id' => $payableAccountId,
            'payment_account_id' => $paymentAccountId,
            'amount' => $paidAmount,
            'cheque_number' => $chequeNo !== '' ? $chequeNo : null,
            'note' => $paymentNote !== '' ? $paymentNote : null,
            'created_by' => $userId,
        ];
        if (Schema::hasColumn('rcm_purchase_payments', 'transaction_id')) {
            $auditData['transaction_id'] = (int) $transaction->id;
        }
        if (Schema::hasColumn('rcm_purchase_payments', 'transaction_payment_id')) {
            $auditData['transaction_payment_id'] = $transactionPayment?->id;
        }
        if (Schema::hasColumn('rcm_purchase_payments', 'payment_status')) {
            $auditData['payment_status'] = (string) ($transaction->payment_status ?: 'due');
        }

        if ($existing) {
            $existing->update($auditData);
            $audit = $existing->fresh();
        } else {
            $audit = PurchasePayment::create($auditData);
        }

        $purchaseUpdate = [];
        if (Schema::hasColumn('rcm_paddy_purchases', 'core_transaction_id')) {
            $purchaseUpdate['core_transaction_id'] = (int) $transaction->id;
        }
        if (Schema::hasColumn('rcm_paddy_purchases', 'payment_status')) {
            $purchaseUpdate['payment_status'] = (string) ($transaction->payment_status ?: 'due');
        }
        if ($purchaseUpdate) {
            $purchase->update($purchaseUpdate);
        }

        // Mirror the Purchase Order into the Product stock Account Books while
        // retaining the ERP's standard payment transaction for the selected
        // Payment Account. This is what makes the details visible in Finance >
        // List Accounts immediately after the Purchase Order is saved.
        $this->financeAccounts->syncPurchaseOrder(
            $businessId,$userId,$purchase->fresh(),$transaction,
            $method,$paymentAccountId,$payableAccountId
        );

        return $audit;
    }

    private function createStandardPurchaseOrderTransaction(int $businessId, int $userId, PaddyPurchase $purchase): Transaction
    {
        if (! empty($purchase->core_transaction_id)) {
            $existing = Transaction::where('business_id', $businessId)->find((int) $purchase->core_transaction_id);
            if ($existing) {
                return $existing;
            }
        }

        $taxId = ! empty($purchase->purchase_tax_id) ? (int) $purchase->purchase_tax_id : null;
        $date = $purchase->purchase_date->format('Y-m-d').' '.now()->format('H:i:s');
        $note = 'Rice Mill Purchase Order: '.$purchase->purchase_no;
        if (! empty($purchase->note)) {
            $note .= "\n".$purchase->note;
        }
        if ($taxId) {
            $note .= "\nPurchase tax selected for actual purchase/receipt; tax is not posted separately at Purchase Order stage.";
        }

        $data = [
            'business_id' => $businessId,
            'location_id' => $purchase->location_id,
            'store_id' => null,
            'type' => 'purchase',
            'sub_type' => 'rice_mill_purchase_order',
            'status' => 'ordered',
            'payment_status' => 'due',
            'contact_id' => $purchase->supplier_id,
            'transaction_date' => $date,
            'invoice_date' => null,
            'invoice_no' => $purchase->purchase_no,
            'order_no' => $purchase->purchase_no,
            'ref_no' => 'RM-'.$purchase->purchase_no,
            'total_before_tax' => (float) $purchase->subtotal,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_id' => $taxId,
            // Explicitly zero at PO stage. Tax selection is retained for the
            // actual purchase/receipt stage, where tax will be calculated.
            'tax_amount' => 0,
            'shipping_charges' => (float) $purchase->other_charges,
            'price_adjustment' => 0,
            'final_total' => (float) $purchase->net_total,
            'exchange_rate' => 1,
            'additional_notes' => $note,
            'created_by' => $userId,
        ];

        $columns = array_flip(Schema::getColumnListing('transactions'));
        $data = array_intersect_key($data, $columns);

        return Transaction::create($data);
    }

    private function paymentNote(PaddyPurchase $purchase, string $userNote): string
    {
        $parts = ['Rice Mill Purchase Order: '.$purchase->purchase_no];
        if ($userNote !== '') {
            $parts[] = $userNote;
        }
        return implode(' | ', $parts);
    }
}
