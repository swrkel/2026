<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthMedicineBatch;
use Modules\MyHealthMembers\Entities\MyHealthPharmacyStock;

class MyHealthStockService
{
    public function addStock(array $data): MyHealthMedicineBatch
    {
        return DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($data) {
            $batch = MyHealthMedicineBatch::create([
                'pharmacy_id' => $data['pharmacy_id'] ?? null,
                'medicine_id' => $data['medicine_id'],
                'batch_no' => $data['batch_no'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'purchase_cost' => $data['purchase_cost'] ?? 0,
                'selling_price' => $data['selling_price'] ?? 0,
                'opening_qty' => $data['quantity'],
                'available_qty' => $data['quantity'],
                'status' => 'active',
            ]);

            $this->ledger([
                'pharmacy_id' => $batch->pharmacy_id,
                'medicine_id' => $batch->medicine_id,
                'batch_id' => $batch->id,
                'transaction_date' => $data['transaction_date'] ?? date('Y-m-d'),
                'transaction_type' => $data['transaction_type'] ?? 'purchase',
                'reference_no' => $data['reference_no'] ?? $batch->batch_no,
                'qty_in' => $data['quantity'],
                'qty_out' => 0,
                'balance_qty' => $batch->available_qty,
                'notes' => $data['notes'] ?? null,
            ]);

            return $batch;
        });
    }

    public function dispenseFromBatch(MyHealthMedicineBatch $batch, float $quantity, string $referenceNo, ?string $notes = null): void
    {
        if ((float) $batch->available_qty < $quantity) {
            throw new \RuntimeException('Insufficient pharmacy stock.');
        }

        $batch->available_qty = (float) $batch->available_qty - $quantity;
        $batch->save();

        $this->ledger([
            'pharmacy_id' => $batch->pharmacy_id,
            'medicine_id' => $batch->medicine_id,
            'batch_id' => $batch->id,
            'transaction_date' => date('Y-m-d'),
            'transaction_type' => 'dispense',
            'reference_no' => $referenceNo,
            'qty_in' => 0,
            'qty_out' => $quantity,
            'balance_qty' => $batch->available_qty,
            'notes' => $notes,
        ]);
    }

    private function ledger(array $data): void
    {
        MyHealthPharmacyStock::create($data);
    }
}
