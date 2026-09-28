<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransferCarrierInvoice;
use Modules\StockTransferNew\Entities\StockTransferCarrierInvoiceLine;

class CarrierInvoiceService
{
    public function list(int $businessId, array $filters = [])
    {
        return StockTransferCarrierInvoice::query()
            ->where('business_id', $businessId)
            ->when(Arr::get($filters, 'status'), fn ($q, $v) => $q->where('status', $v))
            ->when(Arr::get($filters, 'carrier_name'), fn ($q, $v) => $q->where('carrier_name', 'like', '%' . $v . '%'))
            ->when(Arr::get($filters, 'transfer_id'), fn ($q, $v) => $q->where('transfer_id', $v))
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function store(int $businessId, array $data, int $userId): StockTransferCarrierInvoice
    {
        return DB::transaction(function () use ($businessId, $data, $userId) {
            $lines = Arr::get($data, 'lines', []);
            $totals = $this->calculateTotals($data, $lines);

            $invoice = StockTransferCarrierInvoice::create(array_merge($data, $totals, [
                'business_id' => $businessId,
                'status' => 'draft',
                'created_by' => $userId,
            ]));

            foreach ($lines as $line) {
                StockTransferCarrierInvoiceLine::create([
                    'business_id' => $businessId,
                    'carrier_invoice_id' => $invoice->id,
                    'charge_type' => Arr::get($line, 'charge_type', 'other'),
                    'description' => Arr::get($line, 'description'),
                    'qty' => (float) Arr::get($line, 'qty', 1),
                    'rate' => (float) Arr::get($line, 'rate', 0),
                    'amount' => (float) Arr::get($line, 'amount', ((float) Arr::get($line, 'qty', 1) * (float) Arr::get($line, 'rate', 0))),
                    'remarks' => Arr::get($line, 'remarks'),
                ]);
            }

            return $invoice;
        });
    }

    public function approve(StockTransferCarrierInvoice $invoice, int $userId): StockTransferCarrierInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new \RuntimeException('Only draft carrier invoices can be approved.');
        }

        $invoice->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $invoice;
    }

    public function cancel(StockTransferCarrierInvoice $invoice, int $userId): StockTransferCarrierInvoice
    {
        if ($invoice->status === 'posted') {
            throw new \RuntimeException('Posted carrier invoices cannot be cancelled from StockTransferNew.');
        }

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
        ]);

        return $invoice;
    }

    private function calculateTotals(array $data, array $lines): array
    {
        $lineTotal = collect($lines)->sum(function ($line) {
            return (float) Arr::get($line, 'amount', ((float) Arr::get($line, 'qty', 1) * (float) Arr::get($line, 'rate', 0)));
        });

        $freight = (float) Arr::get($data, 'freight_amount', 0);
        $loading = (float) Arr::get($data, 'loading_charge', 0);
        $unloading = (float) Arr::get($data, 'unloading_charge', 0);
        $other = (float) Arr::get($data, 'other_charge', 0) + $lineTotal;
        $tax = (float) Arr::get($data, 'tax_amount', 0);
        $discount = (float) Arr::get($data, 'discount_amount', 0);

        return [
            'freight_amount' => $freight,
            'loading_charge' => $loading,
            'unloading_charge' => $unloading,
            'other_charge' => $other,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => max(0, $freight + $loading + $unloading + $other + $tax - $discount),
        ];
    }
}
