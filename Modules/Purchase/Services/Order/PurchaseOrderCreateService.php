<?php

namespace Modules\Purchase\Services\Order;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Services\Entry\PurchaseEntryCreateService;
use Modules\Purchase\Services\Entry\PurchaseEntryFormService;
use Modules\Purchase\Services\Entry\PurchaseEntryStockService;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseNumberGenerator;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseOrderCreateService
{
    public function __construct(
        protected PurchaseEntryFormService $form,
        protected PurchaseEntryStockService $stock,
        protected PurchaseEntryCreateService $entryHelper,
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseNumberGenerator $numberGenerator,
        protected PurchaseSchemaUtil $schema
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(): array
    {
        $businessId = $this->numbers->businessId();
        $data = $this->form->formData();

        $data['purchase_no'] = $this->numberGenerator->next('purchase_order', $businessId);
        $data['transaction_date'] = now()->format('Y-m-d\TH:i');
        $data['invoice_date'] = now()->toDateString();
        $data['order_statuses'] = [
            'ordered' => 'Ordered',
            'pending' => 'Pending',
        ];
        $data['payment_methods'] = [];
        $data['is_purchase_order'] = true;

        // Purchase orders reuse only the standalone Purchase data endpoints.
        // Their save and navigation endpoints remain entirely order-specific.
        $data['routes']['store'] = route('purchase.orders.store');
        $data['routes']['index'] = route('purchase.orders.index');
        $data['routes']['show'] = url('/purchase/orders/__ID__');
        $data['routes']['create'] = route('purchase.orders.create');

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{transaction_id:int, invoice_no:string, final_total:float, status:string}
     */
    public function store(array $data, Request $request): array
    {
        $this->entryHelper->assertRequiredTables();

        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        if ($businessId <= 0 || $userId <= 0) {
            throw new \RuntimeException('The business session is not available. Please sign in again.');
        }

        $supplierId = (int) $data['contact_id'];
        $locationId = (int) $data['location_id'];
        $storeId = (int) $data['store_id'];
        $this->entryHelper->validateOwnership($businessId, $supplierId, $locationId, $storeId);

        $reference = trim((string) ($data['ref_no'] ?? ''));
        if ($reference !== '' && $this->referenceExists($businessId, $supplierId, $reference)) {
            throw new \InvalidArgumentException(
                'This supplier quotation/reference number has already been used for the selected supplier.'
            );
        }

        $status = in_array(($data['status'] ?? 'ordered'), ['ordered', 'pending'], true)
            ? (string) $data['status']
            : 'ordered';
        $exchangeRate = max(0.000001, $this->numbers->number($data['exchange_rate'] ?? 1));
        $prepared = $this->stock->prepareLines((array) $data['purchases'], $exchangeRate);
        $totals = $this->entryHelper->calculateTotals($businessId, $data, $prepared, $exchangeRate);
        $transactionDate = $this->numbers->dateTime($data['transaction_date'])->format('Y-m-d H:i:s');
        $expectedDate = $this->numbers->date($data['invoice_date']);

        $orderNumber = trim((string) ($data['invoice_no'] ?? ''));
        if ($orderNumber === '' || $this->orderNumberExists($businessId, $orderNumber)) {
            $orderNumber = $this->numberGenerator->next('purchase_order', $businessId);
        }

        $document = $this->entryHelper->uploadDocument($request);

        try {
            return DB::transaction(function () use (
                $data,
                $prepared,
                $totals,
                $businessId,
                $userId,
                $supplierId,
                $locationId,
                $storeId,
                $transactionDate,
                $expectedDate,
                $orderNumber,
                $reference,
                $document,
                $status,
                $exchangeRate
            ): array {
                $transactionPayload = $this->schema->filter('transactions', [
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'store_id' => $storeId,
                    'type' => 'purchase_order',
                    'status' => $status,
                    'payment_status' => 'due',
                    'contact_id' => $supplierId,
                    'invoice_no' => $orderNumber,
                    'order_no' => trim((string) ($data['order_no'] ?? '')) ?: $orderNumber,
                    'ref_no' => $reference !== '' ? $reference : null,
                    'transaction_date' => $transactionDate,
                    // The shared form posts the expected delivery date as invoice_date.
                    'invoice_date' => $expectedDate,
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
                    'created_by' => $userId,
                    'document' => $document,
                    'is_vat' => ! empty($data['is_vat']) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $transactionId = (int) DB::table('transactions')->insertGetId($transactionPayload);
                $this->saveOrderLines($transactionId, $prepared['lines']);

                Log::info('Standalone Purchase order saved', [
                    'transaction_id' => $transactionId,
                    'business_id' => $businessId,
                    'invoice_no' => $orderNumber,
                    'status' => $status,
                    'final_total' => $totals['final_total'],
                ]);

                return [
                    'transaction_id' => $transactionId,
                    'invoice_no' => $orderNumber,
                    'final_total' => (float) $totals['final_total'],
                    'status' => $status,
                ];
            }, 3);
        } catch (\Throwable $e) {
            $this->entryHelper->removeUploadedDocument($document);
            throw $e;
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    protected function saveOrderLines(int $transactionId, array $lines): void
    {
        foreach ($lines as $line) {
            $payload = $this->schema->filter('purchase_lines', [
                'transaction_id' => $transactionId,
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $line['quantity'],
                'bonus_qty' => $line['free_qty'],
                'pp_without_discount' => $line['pp_without_discount'],
                'discount_percent' => $line['discount_percent'],
                'purchase_price' => $line['purchase_price'],
                'purchase_price_inc_tax' => $line['purchase_price_inc_tax'],
                'item_tax' => $line['item_tax'],
                'tax_id' => $line['tax_id'],
                'mfg_date' => $line['mfg_date'],
                'exp_date' => $line['exp_date'],
                'lot_number' => $line['lot_number'],
                'sub_unit_id' => $line['sub_unit_id'],
                'secondary_unit_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('purchase_lines')->insert($payload);
        }
    }

    protected function orderNumberExists(int $businessId, string $orderNumber): bool
    {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'purchase_order')
            ->where('invoice_no', $orderNumber);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    protected function referenceExists(int $businessId, int $supplierId, string $reference): bool
    {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'purchase_order')
            ->where('contact_id', $supplierId)
            ->where('ref_no', $reference);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }
}
