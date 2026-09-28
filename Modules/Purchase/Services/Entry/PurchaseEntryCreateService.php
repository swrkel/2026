<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseNumberGenerator;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryCreateService
{
    public function __construct(
        protected PurchaseEntryFormService $form,
        protected PurchaseEntryStockService $stock,
        protected PurchaseEntryTankService $tanks,
        protected PurchaseEntryPaymentService $payments,
        protected PurchaseEntryAccountingService $accounting,
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseNumberGenerator $numberGenerator,
        protected PurchaseSchemaUtil $schema
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(): array
    {
        return $this->form->formData();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{transaction_id: int, invoice_no: string, final_total: float, payment_status: string, status: string, linked_purchase_order_id: ?int}
     */
    public function store(array $data, Request $request): array
    {
        $this->assertRequiredTables();

        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        if ($businessId <= 0 || $userId <= 0) {
            throw new \RuntimeException('The business session is not available. Please sign in again.');
        }

        $supplierId = (int) $data['contact_id'];
        $locationId = (int) $data['location_id'];
        $storeId = (int) $data['store_id'];
        $linkedPurchaseOrderId = ! empty($data['linked_purchase_order_id'])
            ? (int) $data['linked_purchase_order_id']
            : null;
        $this->validateOwnership($businessId, $supplierId, $locationId, $storeId);

        $reference = trim((string) ($data['ref_no'] ?? ''));
        if ($reference !== '' && $this->referenceExists($businessId, $supplierId, $reference)) {
            throw new \InvalidArgumentException('This supplier invoice/reference number has already been used for the selected supplier.');
        }

        $status = (string) $data['status'];
        $exchangeRate = max(0.000001, $this->numbers->number($data['exchange_rate'] ?? 1));
        $this->validatePaymentStatusCombination($status, (array) ($data['payments'] ?? []));

        $prepared = $this->stock->prepareLines((array) $data['purchases'], $exchangeRate);
        $tankAllocations = $this->tanks->prepareAllocations($prepared['lines'], (array) ($data['tanks'] ?? []), $locationId, $status);
        $totals = $this->calculateTotals($businessId, $data, $prepared, $exchangeRate);
        $transactionDate = $this->numbers->dateTime($data['transaction_date'])->format('Y-m-d H:i:s');
        $invoiceDate = $this->numbers->date($data['invoice_date']);
        $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
        if ($invoiceNo === '' || $this->invoiceExists($businessId, $invoiceNo)) {
            $invoiceNo = $this->numberGenerator->next('purchase_entry', $businessId);
        }

        $document = $this->uploadDocument($request);

        try {
            return DB::transaction(function () use (
                $data,
                $prepared,
                $tankAllocations,
                $totals,
                $businessId,
                $userId,
                $supplierId,
                $locationId,
                $storeId,
                $transactionDate,
                $invoiceDate,
                $invoiceNo,
                $reference,
                $document,
                $status,
                $exchangeRate,
                $linkedPurchaseOrderId
            ): array {
                $linkedOrder = $this->lockPendingPurchaseOrder(
                    $linkedPurchaseOrderId,
                    $businessId,
                    $supplierId,
                    $locationId,
                    $storeId
                );
                $purchaseOrderNo = $linkedOrder
                    ? trim((string) $linkedOrder->invoice_no)
                    : trim((string) ($data['order_no'] ?? ''));

                $transactionPayload = $this->schema->filter('transactions', [
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'sw_shift_no' => $data['sw_shift_no'] ?? null,
                    'store_id' => $storeId,
                    'type' => 'purchase',
                    'status' => $status,
                    'payment_status' => 'due',
                    'contact_id' => $supplierId,
                    'invoice_no' => $invoiceNo,
                    'purchase_entry_no' => $invoiceNo,
                    'order_no' => $purchaseOrderNo !== '' ? $purchaseOrderNo : null,
                    'ref_no' => $reference !== '' ? $reference : null,
                    'transaction_date' => $transactionDate,
                    'invoice_date' => $invoiceDate,
                    'total_before_tax' => $totals['subtotal'],
                    'tax_id' => $totals['order_tax_id'],
                    // Product taxes are stored per purchase line; this field is the additional order tax.
                    'tax_amount' => $totals['order_tax'],
                    'discount_type' => $totals['discount_type'],
                    'discount_amount' => $totals['discount_amount'],
                    'shipping_details' => trim((string) ($data['shipping_details'] ?? '')) ?: null,
                    'shipping_charges' => $totals['shipping_charges'],
                    'price_adjustment' => $totals['price_adjustment'],
                    'additional_notes' => trim((string) ($data['additional_notes'] ?? '')) ?: null,
                    'final_total' => $totals['final_total'],
                    'exchange_rate' => $exchangeRate,
                    'pay_term_number' => isset($data['pay_term_number']) && $data['pay_term_number'] !== '' ? (int) $data['pay_term_number'] : null,
                    'pay_term_type' => $data['pay_term_type'] ?? null,
                    'created_by' => $userId,
                    'document' => $document,
                    'is_vat' => ! empty($data['is_vat']) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $transactionId = (int) DB::table('transactions')->insertGetId($transactionPayload);
                $this->stock->saveLines($transactionId, $prepared['lines'], $locationId, $storeId, $status);
                $this->tanks->saveAllocations($transactionId, $tankAllocations);

                $paymentResult = $this->payments->save(
                    $transactionId,
                    $supplierId,
                    $totals['final_total'],
                    (array) ($data['payments'] ?? []),
                    $exchangeRate
                );

                DB::table('transactions')->where('id', $transactionId)->update($this->schema->filter('transactions', [
                    'payment_status' => $paymentResult['status'],
                    'updated_at' => now(),
                ]));

                // Pending/ordered entries do not affect stock or ledgers until they are received.
                if ($status === 'received') {
                    $this->accounting->post(
                        $businessId,
                        $locationId,
                        $userId,
                        $transactionId,
                        $reference !== '' ? $reference : $invoiceNo,
                        $transactionDate,
                        $totals['final_total'],
                        $paymentResult['paid_total'],
                        $paymentResult['payments']
                    );
                }

                if ($linkedOrder) {
                    DB::table('transactions')
                        ->where('business_id', $businessId)
                        ->where('type', 'purchase_order')
                        ->where('id', (int) $linkedOrder->id)
                        ->update($this->schema->filter('transactions', [
                            'status' => 'received',
                            'updated_at' => now(),
                        ]));
                }

                Log::info('Standalone Purchase entry saved', [
                    'transaction_id' => $transactionId,
                    'business_id' => $businessId,
                    'invoice_no' => $invoiceNo,
                    'status' => $status,
                    'final_total' => $totals['final_total'],
                    'payment_status' => $paymentResult['status'],
                    'linked_purchase_order_id' => $linkedOrder ? (int) $linkedOrder->id : null,
                ]);

                return [
                    'transaction_id' => $transactionId,
                    'invoice_no' => $invoiceNo,
                    'final_total' => $totals['final_total'],
                    'payment_status' => $paymentResult['status'],
                    'status' => $status,
                    'linked_purchase_order_id' => $linkedOrder ? (int) $linkedOrder->id : null,
                ];
            }, 3);
        } catch (\Throwable $e) {
            $this->removeUploadedDocument($document);
            throw $e;
        }
    }

    protected function lockPendingPurchaseOrder(
        ?int $purchaseOrderId,
        int $businessId,
        int $supplierId,
        int $locationId,
        int $storeId
    ): ?object {
        if (! $purchaseOrderId) {
            return null;
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $supplierId)
            ->where('type', 'purchase_order')
            ->whereIn('status', ['ordered', 'pending'])
            ->where('id', $purchaseOrderId)
            ->lockForUpdate();
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $order = $query->first(['id', 'invoice_no', 'location_id', 'store_id', 'status']);
        if (! $order) {
            throw new \InvalidArgumentException(
                'The selected purchase order is no longer pending or has already been taken to Add Purchase.'
            );
        }
        if ((int) ($order->location_id ?? 0) !== $locationId || (int) ($order->store_id ?? 0) !== $storeId) {
            throw new \InvalidArgumentException(
                'The selected purchase order belongs to a different business location or store. Please reload it and try again.'
            );
        }

        $orderNumber = trim((string) ($order->invoice_no ?? ''));
        if ($orderNumber === '') {
            throw new \InvalidArgumentException('The selected purchase order does not have a valid order number.');
        }

        if (Schema::hasColumn('transactions', 'order_no')) {
            $usedQuery = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $supplierId)
                ->where('type', 'purchase')
                ->where('order_no', $orderNumber);
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $usedQuery->whereNull('deleted_at');
            }
            if ($usedQuery->exists()) {
                throw new \InvalidArgumentException(
                    'This purchase order has already been taken to Add Purchase and cannot be selected again.'
                );
            }
        }

        return $order;
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $prepared @return array<string, mixed> */
    public function calculateTotals(int $businessId, array $data, array $prepared, float $exchangeRate): array
    {
        $subtotal = (float) $prepared['subtotal'];
        $lineTax = (float) $prepared['line_tax'];
        $discountType = (string) ($data['discount_type'] ?? 'fixed');
        $discountInput = max(0, $this->numbers->number($data['discount_amount'] ?? 0));
        $discount = $discountType === 'percentage'
            ? $subtotal * min(100, $discountInput) / 100
            : min($subtotal, $discountInput * $exchangeRate);

        $orderTaxId = ! empty($data['tax_id']) ? (int) $data['tax_id'] : null;
        $orderTaxRate = 0.0;
        if ($orderTaxId && Schema::hasTable('tax_rates')) {
            $taxQuery = DB::table('tax_rates')
                ->where('business_id', $businessId)
                ->where('id', $orderTaxId);
            if (Schema::hasColumn('tax_rates', 'deleted_at')) {
                $taxQuery->whereNull('deleted_at');
            }
            $orderTaxRate = (float) ($taxQuery->value('amount') ?? 0);
        }
        $orderTax = max(0, $subtotal - $discount) * $orderTaxRate / 100;
        $shipping = max(0, $this->numbers->number($data['shipping_charges'] ?? 0)) * $exchangeRate;
        $adjustment = $this->numbers->number($data['price_adjustment'] ?? 0) * $exchangeRate;
        $freeTotal = ! empty($data['apply_free_product_total']) ? (float) $prepared['free_product_total'] : 0.0;
        $final = max(0, $subtotal + $lineTax - $discount + $orderTax + $shipping + $adjustment + $freeTotal);

        return [
            'subtotal' => round($subtotal, 6),
            'line_tax' => round($lineTax, 6),
            'order_tax_id' => $orderTaxId,
            'order_tax' => round($orderTax, 6),
            'tax_total' => round($lineTax + $orderTax, 6),
            'discount_type' => $discountType,
            'discount_amount' => round($discount, 6),
            'shipping_charges' => round($shipping, 6),
            'price_adjustment' => round($adjustment, 6),
            'free_product_total' => round($freeTotal, 6),
            'final_total' => round($final, 6),
        ];
    }

    public function validateOwnership(int $businessId, int $supplierId, int $locationId, int $storeId): void
    {
        $supplierQuery = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('id', $supplierId)
            ->whereIn('type', ['supplier', 'both']);
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $supplierQuery->whereNull('deleted_at');
        }
        if (! $supplierQuery->exists()) {
            throw new \InvalidArgumentException('The selected supplier is not available for this business.');
        }

        $locationQuery = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->where('id', $locationId);
        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $locationQuery->whereNull('deleted_at');
        }
        if (! $locationQuery->exists()) {
            throw new \InvalidArgumentException('The selected business location is not valid.');
        }

        $storeQuery = DB::table('stores')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('id', $storeId);
        if (Schema::hasColumn('stores', 'deleted_at')) {
            $storeQuery->whereNull('deleted_at');
        }
        if (! $storeQuery->exists()) {
            throw new \InvalidArgumentException('The selected store does not belong to the selected business location.');
        }
    }

    public function referenceExists(int $businessId, int $supplierId, string $reference, ?int $excludeId = null): bool
    {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('contact_id', $supplierId)
            ->where('ref_no', $reference);
        if ($excludeId && $excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    protected function invoiceExists(int $businessId, string $invoiceNo): bool
    {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('invoice_no', $invoiceNo);
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    /** @param array<int, array<string, mixed>> $payments */
    public function validatePaymentStatusCombination(string $status, array $payments): void
    {
        if ($status === 'received') {
            return;
        }

        foreach ($payments as $payment) {
            $method = (string) ($payment['method'] ?? '');
            $amount = $this->numbers->number($payment['amount'] ?? 0);
            if ($amount > 0 && $method !== '' && $method !== 'credit_purchase') {
                throw new \InvalidArgumentException('Actual payments can be recorded only when the purchase status is Received.');
            }
        }
    }

    public function uploadDocument(Request $request): ?string
    {
        if (! $request->hasFile('document')) {
            return null;
        }

        $file = $request->file('document');
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('The purchase document could not be uploaded.');
        }

        $directory = public_path('uploads/documents');
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('The purchase document directory is not writable.');
        }

        $original = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName()) ?: 'purchase-document';
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '_' . $original;
        $file->move($directory, $filename);

        return $filename;
    }

    public function removeUploadedDocument(?string $document): void
    {
        if (! $document) {
            return;
        }
        $path = public_path('uploads/documents/' . basename($document));
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function assertRequiredTables(): void
    {
        foreach (['transactions', 'purchase_lines', 'contacts', 'business_locations', 'stores', 'products', 'product_variations', 'variations'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required purchase table [$table] is missing in the tenant database.");
            }
        }
    }
}
