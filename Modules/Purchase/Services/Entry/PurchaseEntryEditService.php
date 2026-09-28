<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryEditService
{
    public function __construct(
        protected PurchaseEntryFormService $form,
        protected PurchaseEntryDataService $data,
        protected PurchaseEntryShowService $show,
        protected PurchaseEntryCreateService $writer,
        protected PurchaseEntryStockService $stock,
        protected PurchaseEntryTankService $tanks,
        protected PurchaseEntryPaymentService $payments,
        protected PurchaseEntryAccountingService $accounting,
        protected PurchaseEntryLifecycleService $lifecycle,
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(int $id): array
    {
        $page = $this->show->pageData($id);
        $purchase = $page['purchase'];
        $businessId = $this->numbers->businessId();
        $exchangeRate = max(0.000001, (float) ($purchase->exchange_rate ?? 1));
        $locationId = (int) ($purchase->location_id ?? 0);
        $storeId = (int) ($purchase->store_id ?? 0);
        $supplierId = (int) ($purchase->contact_id ?? 0);

        $formData = $this->form->formData();
        $formData['stores'] = $locationId > 0
            ? $this->form->stores($businessId, $locationId)
            : collect();

        $lineTax = (float) ($page['line_tax_total'] ?? 0);
        $freeProductTotal = (float) $page['lines']->sum(function ($line): float {
            return max(0, (float) ($line->bonus_qty ?? 0)) * max(0, (float) ($line->purchase_price_inc_tax ?? 0));
        });
        $baseWithoutFree = (float) ($purchase->total_before_tax ?? 0)
            + $lineTax
            - (float) ($purchase->discount_amount ?? 0)
            + (float) ($purchase->tax_amount ?? 0)
            + (float) ($purchase->shipping_charges ?? 0)
            + (float) ($purchase->price_adjustment ?? 0);
        $finalTotal = (float) ($purchase->final_total ?? 0);
        $applyFreeProductTotal = $freeProductTotal > 0.000001
            && abs($finalTotal - ($baseWithoutFree + $freeProductTotal)) + 0.01 < abs($finalTotal - $baseWithoutFree);

        $entry = [
            'id' => (int) $purchase->id,
            'invoice_no' => (string) (($purchase->invoice_no ?? '') ?: ($purchase->purchase_entry_no ?? ('PUR-' . $purchase->id))),
            'location_id' => $locationId,
            'store_id' => $storeId,
            'status' => (string) (($purchase->status ?? '') ?: 'received'),
            'contact_id' => $supplierId,
            'ref_no' => (string) ($purchase->ref_no ?? ''),
            'transaction_date' => $this->dateTimeInput($purchase->transaction_date ?? null),
            'invoice_date' => $this->dateInput($purchase->invoice_date ?? $purchase->transaction_date ?? null),
            'order_no' => (string) ($purchase->order_no ?? ''),
            'pay_term_number' => $purchase->pay_term_number ?? null,
            'pay_term_type' => (string) (($purchase->pay_term_type ?? '') ?: 'days'),
            'exchange_rate' => $exchangeRate,
            'document' => (string) ($purchase->document ?? ''),
            'is_vat' => (bool) ($purchase->is_vat ?? false),
            'apply_free_product_total' => $applyFreeProductTotal,
            // Stored transaction discounts are absolute values. Fixed mode preserves the exact saved total on edit.
            'discount_type' => 'fixed',
            'discount_amount' => $this->enteredMoney($purchase->discount_amount ?? 0, $exchangeRate),
            'tax_id' => $purchase->tax_id ?? null,
            'shipping_details' => (string) ($purchase->shipping_details ?? ''),
            'shipping_charges' => $this->enteredMoney($purchase->shipping_charges ?? 0, $exchangeRate),
            'price_adjustment' => $this->enteredMoney($purchase->price_adjustment ?? 0, $exchangeRate),
            'additional_notes' => (string) ($purchase->additional_notes ?? ''),
        ];

        $initialLines = [];
        foreach ($page['lines'] as $line) {
            $product = $this->data->product((int) $line->variation_id, $locationId, $storeId ?: null);
            if (! $product) {
                throw new \InvalidArgumentException(
                    'This purchase contains a product variation that is no longer available. Restore that product before editing the purchase.'
                );
            }

            $multiplier = max(0.000001, (float) ($line->sub_unit_multiplier ?? 1));
            $selectedUnitId = (int) (($line->sub_unit_id ?? 0) ?: ($product['unit_id'] ?? 0));
            $product['selected_unit_id'] = $selectedUnitId;
            $product['unit_multiplier'] = $multiplier;
            $product['quantity'] = (float) ($line->quantity ?? 0) / $multiplier;
            $product['free_qty'] = (float) ($line->bonus_qty ?? 0) / $multiplier;
            $product['purchase_price'] = $this->enteredUnitMoney($line->pp_without_discount ?? 0, $multiplier, $exchangeRate);
            $product['purchase_price_inc_tax'] = $this->enteredUnitMoney($line->purchase_price_inc_tax ?? 0, $multiplier, $exchangeRate);
            $product['discount_type'] = 'percentage';
            $product['discount_value'] = max(0, (float) ($line->discount_percent ?? 0));
            $product['tax_id'] = ! empty($line->tax_id) ? (int) $line->tax_id : null;
            $product['profit_percent'] = (float) ($line->profit_percent ?? 0);
            // IS2112: selling price is the product-master/base-unit value. Do not
            // scale it by the purchase sub-unit multiplier on Edit Purchase.
            $product['selling_price'] = max(0, (float) ($line->selling_price ?? 0));
            $product['lot_number'] = (string) ($line->lot_number ?? '');
            $product['mfg_date'] = $this->dateInput($line->mfg_date ?? null);
            $product['exp_date'] = $this->dateInput($line->exp_date ?? null);
            $initialLines[] = $product;
        }

        $initialPayments = [];
        foreach ($page['payments'] as $payment) {
            $initialPayments[] = [
                'method' => (string) (($payment->method ?? '') ?: 'cash'),
                'amount' => $this->enteredMoney($payment->amount ?? 0, $exchangeRate),
                'account_id' => $payment->account_id ?? null,
                'paid_on' => $this->dateTimeInput($payment->paid_on ?? null),
                'reference_no' => (string) (($payment->reference_no ?? null) ?: ($payment->transaction_no ?? '')),
                'payment_ref_no' => (string) ($payment->payment_ref_no ?? ''),
                'cheque_number' => (string) ($payment->cheque_number ?? ''),
                'cheque_date' => $this->dateInput($payment->cheque_date ?? null),
                'bank_name' => (string) ($payment->bank_name ?? ''),
                'bank_account_number' => (string) ($payment->bank_account_number ?? ''),
                'transfer_date' => $this->dateInput($payment->transfer_date ?? null),
                'card_transaction_number' => (string) ($payment->card_transaction_number ?? ''),
                'card_number' => (string) ($payment->card_number ?? ''),
                'card_type' => (string) ($payment->card_type ?? ''),
                'card_holder_name' => (string) ($payment->card_holder_name ?? ''),
                'note' => (string) ($payment->note ?? ''),
                'auto_credit' => false,
            ];
        }

        $due = max(0, (float) ($page['due_total'] ?? 0));
        if ((string) $entry['status'] === 'received' && $due > 0.000001) {
            $initialPayments[] = [
                'method' => 'credit_purchase',
                'amount' => $this->enteredMoney($due, $exchangeRate),
                'account_id' => null,
                'paid_on' => $this->dateTimeInput(now()),
                'reference_no' => '',
                'note' => '',
                'auto_credit' => true,
            ];
        }

        $formData['purchase_no'] = $entry['invoice_no'];
        $formData['transaction_date'] = $entry['transaction_date'];
        $formData['invoice_date'] = $entry['invoice_date'];
        $formData['purchase'] = $entry;
        $formData['supplier'] = $this->data->supplier($supplierId) ?: [
            'id' => $supplierId,
            'name' => (string) ($purchase->supplier_name ?? ''),
            'mobile' => (string) ($purchase->supplier_mobile ?? ''),
            'outstanding' => 0,
        ];
        $formData['initial_lines'] = $initialLines;
        $formData['initial_payments'] = $initialPayments;
        $formData['initial_tanks'] = $this->tanks->existingAllocations((int) $purchase->id);
        $formData['reference_exclude_id'] = (int) $purchase->id;
        $formData['is_edit'] = true;
        $formData['routes']['store'] = route('purchase.entries.update', ['id' => $purchase->id]);

        return $formData;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{transaction_id: int, invoice_no: string, final_total: float, payment_status: string, status: string}
     */
    public function update(int $id, array $data, Request $request): array
    {
        $this->writer->assertRequiredTables();

        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        if ($businessId <= 0 || $userId <= 0) {
            throw new \RuntimeException('The business session is not available. Please sign in again.');
        }

        $supplierId = (int) $data['contact_id'];
        $locationId = (int) $data['location_id'];
        $storeId = (int) $data['store_id'];
        $this->writer->validateOwnership($businessId, $supplierId, $locationId, $storeId);

        $reference = trim((string) ($data['ref_no'] ?? ''));
        if ($reference !== '' && $this->writer->referenceExists($businessId, $supplierId, $reference, $id)) {
            throw new \InvalidArgumentException('This supplier invoice/reference number has already been used for the selected supplier.');
        }

        $status = (string) $data['status'];
        $exchangeRate = max(0.000001, $this->numbers->number($data['exchange_rate'] ?? 1));
        $this->writer->validatePaymentStatusCombination($status, (array) ($data['payments'] ?? []));

        $prepared = $this->stock->prepareLines((array) $data['purchases'], $exchangeRate);
        $tankAllocations = $this->tanks->prepareAllocations($prepared['lines'], (array) ($data['tanks'] ?? []), $locationId, $status);
        $totals = $this->writer->calculateTotals($businessId, $data, $prepared, $exchangeRate);
        $transactionDate = $this->numbers->dateTime($data['transaction_date'])->format('Y-m-d H:i:s');
        $invoiceDate = $this->numbers->date($data['invoice_date']);
        $newDocument = $this->writer->uploadDocument($request);
        $oldDocument = null;

        try {
            $result = DB::transaction(function () use (
                $id,
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
                $reference,
                $newDocument,
                $status,
                $exchangeRate,
                &$oldDocument
            ): array {
                $purchase = $this->lifecycle->lockPurchase($id, $businessId);
                $oldDocument = (string) ($purchase->document ?? '');
                $allowedPaymentReferences = $this->schema->tableExists('transaction_payments')
                    ? DB::table('transaction_payments')
                        ->where('transaction_id', $id)
                        ->whereNotNull('payment_ref_no')
                        ->pluck('payment_ref_no')
                        ->filter(fn ($value) => trim((string) $value) !== '')
                        ->map(fn ($value) => (string) $value)
                        ->values()
                        ->all()
                    : [];
                $oldLines = $this->lifecycle->linesForUpdate($id);
                $this->lifecycle->assertCanModify($purchase, $oldLines);
                $this->lifecycle->reverseReceivedStock($purchase, $oldLines);
                $this->tanks->reverseAndDelete($id);
                $this->lifecycle->clearRelatedRecords($id);

                $invoiceNo = trim((string) ($purchase->invoice_no ?? $data['invoice_no'] ?? ''));
                if ($invoiceNo === '') {
                    $invoiceNo = 'PUR-' . $id;
                }

                $transactionPayload = $this->schema->filter('transactions', [
                    'location_id' => $locationId,
                    'store_id' => $storeId,
                    'status' => $status,
                    'payment_status' => 'due',
                    'contact_id' => $supplierId,
                    'invoice_no' => $invoiceNo,
                    'purchase_entry_no' => $invoiceNo,
                    'order_no' => trim((string) ($data['order_no'] ?? '')) ?: null,
                    'ref_no' => $reference !== '' ? $reference : null,
                    'transaction_date' => $transactionDate,
                    'invoice_date' => $invoiceDate,
                    'total_before_tax' => $totals['subtotal'],
                    'tax_id' => $totals['order_tax_id'],
                    'tax_amount' => $totals['order_tax'],
                    'discount_type' => $totals['discount_type'],
                    'discount_amount' => $totals['discount_amount'],
                    'shipping_details' => trim((string) ($data['shipping_details'] ?? '')) ?: null,
                    'shipping_charges' => $totals['shipping_charges'],
                    'price_adjustment' => $totals['price_adjustment'],
                    'additional_notes' => trim((string) ($data['additional_notes'] ?? '')) ?: null,
                    'final_total' => $totals['final_total'],
                    'exchange_rate' => $exchangeRate,
                    'pay_term_number' => isset($data['pay_term_number']) && $data['pay_term_number'] !== ''
                        ? (int) $data['pay_term_number']
                        : null,
                    'pay_term_type' => $data['pay_term_type'] ?? null,
                    'document' => $newDocument ?: ($oldDocument ?: null),
                    'is_vat' => ! empty($data['is_vat']) ? 1 : 0,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);

                DB::table('transactions')->where('id', $id)->update($transactionPayload);
                $this->stock->saveLines($id, $prepared['lines'], $locationId, $storeId, $status);
                $this->tanks->saveAllocations($id, $tankAllocations);

                $paymentResult = $this->payments->save(
                    $id,
                    $supplierId,
                    $totals['final_total'],
                    (array) ($data['payments'] ?? []),
                    $exchangeRate,
                    'APEP',
                    $allowedPaymentReferences
                );

                DB::table('transactions')->where('id', $id)->update($this->schema->filter('transactions', [
                    'payment_status' => $paymentResult['status'],
                    'updated_at' => now(),
                ]));

                if ($status === 'received') {
                    $this->accounting->post(
                        $businessId,
                        $locationId,
                        $userId,
                        $id,
                        $reference !== '' ? $reference : $invoiceNo,
                        $transactionDate,
                        $totals['final_total'],
                        $paymentResult['paid_total'],
                        $paymentResult['payments']
                    );
                }

                Log::info('Standalone Purchase entry updated', [
                    'transaction_id' => $id,
                    'business_id' => $businessId,
                    'invoice_no' => $invoiceNo,
                    'status' => $status,
                    'final_total' => $totals['final_total'],
                    'payment_status' => $paymentResult['status'],
                ]);

                return [
                    'transaction_id' => $id,
                    'invoice_no' => $invoiceNo,
                    'final_total' => $totals['final_total'],
                    'payment_status' => $paymentResult['status'],
                    'status' => $status,
                ];
            }, 3);

            if ($newDocument && $oldDocument && basename($newDocument) !== basename($oldDocument)) {
                $this->lifecycle->removeDocument($oldDocument);
            }

            return $result;
        } catch (\Throwable $e) {
            if ($newDocument) {
                $this->writer->removeUploadedDocument($newDocument);
            }
            throw $e;
        }
    }

    protected function enteredMoney(mixed $amount, float $exchangeRate): float
    {
        return round((float) $amount / max(0.000001, $exchangeRate), 6);
    }

    protected function enteredUnitMoney(mixed $amount, float $multiplier, float $exchangeRate): float
    {
        return round(((float) $amount * $multiplier) / max(0.000001, $exchangeRate), 6);
    }

    protected function dateTimeInput(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return now()->format('Y-m-d\TH:i');
        }

        return $this->numbers->dateTime($value)->format('Y-m-d\TH:i');
    }

    protected function dateInput(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        return $this->numbers->dateTime($value)->format('Y-m-d');
    }
}
