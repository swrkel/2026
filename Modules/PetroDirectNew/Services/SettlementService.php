<?php

namespace Modules\PetroDirectNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirectNew\Entities\PdirectnewSettlement;
use Modules\PetroDirectNew\Entities\PdirectnewOperator;
use Modules\PetroDirectNew\Entities\PdirectnewPump;
use Modules\PetroDirectNew\Entities\PdirectnewShift;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementCustomerPayment;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementMeterSale;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementOtherIncome;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementOtherSale;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementOtherSaleLine;
use Modules\PetroDirectNew\Entities\PdirectnewSettlementPayment;
use Modules\PetroDirectNew\Support\BusinessContext;

class SettlementService
{
    public function __construct(
        private BusinessContext $context,
        private NumberSequenceService $numbers,
        private AuditService $audit
    ) {}

    public function create(array $data): PdirectnewSettlement
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->context->requireBusiness();
            $locationId = (int) $data['location_id'];
            abort_unless($this->context->locationAllowed($locationId), 403);
            $this->assertModuleRecord(PdirectnewOperator::class, (int) $data['operator_id'], $businessId, $locationId, true);
            if (!empty($data['shift_id'])) $this->assertModuleRecord(PdirectnewShift::class, (int) $data['shift_id'], $businessId, $locationId, false);

            $settlement = PdirectnewSettlement::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'settlement_no' => $this->numbers->next('settlement', $locationId, 'DS-'),
                'shift_id' => Arr::get($data, 'shift_id'),
                'operator_id' => (int) $data['operator_id'],
                'transaction_date' => $data['transaction_date'],
                'work_shift' => Arr::get($data, 'work_shift'),
                'status' => 'draft',
                'note' => Arr::get($data, 'note'),
                'created_by' => $this->context->userId(),
                'updated_by' => $this->context->userId(),
            ]);

            $this->syncChildren($settlement, $data);
            $this->recalculate($settlement);
            $this->audit->record('created', 'settlement', $settlement->id, [], $settlement->fresh()->toArray());
            return $settlement->fresh();
        });
    }

    public function update(PdirectnewSettlement $settlement, array $data): PdirectnewSettlement
    {
        abort_unless(in_array($settlement->status, ['draft','reopened'], true), 422, 'Only draft or reopened settlements can be edited.');
        return DB::transaction(function () use ($settlement, $data) {
            $before = $settlement->toArray();
            $businessId = $this->context->requireBusiness();
            $locationId = (int) $data['location_id'];
            abort_unless($this->context->locationAllowed($locationId), 403);
            $this->assertModuleRecord(PdirectnewOperator::class, (int) $data['operator_id'], $businessId, $locationId, true);
            if (!empty($data['shift_id'])) $this->assertModuleRecord(PdirectnewShift::class, (int) $data['shift_id'], $businessId, $locationId, false);
            $settlement->fill([
                'location_id' => (int) $data['location_id'],
                'shift_id' => Arr::get($data, 'shift_id'),
                'operator_id' => (int) $data['operator_id'],
                'transaction_date' => $data['transaction_date'],
                'work_shift' => Arr::get($data, 'work_shift'),
                'note' => Arr::get($data, 'note'),
                'updated_by' => $this->context->userId(),
            ])->save();
            $this->syncChildren($settlement, $data);
            $this->recalculate($settlement);
            $this->audit->record('updated', 'settlement', $settlement->id, $before, $settlement->fresh()->toArray());
            return $settlement->fresh();
        });
    }

    private function syncChildren(PdirectnewSettlement $settlement, array $data): void
    {
        $settlement->meterSales()->delete();
        $settlement->payments()->delete();
        $settlement->otherIncome()->delete();
        $settlement->customerPayments()->delete();
        foreach ($settlement->otherSales()->get() as $sale) {
            PdirectnewSettlementOtherSaleLine::where('other_sale_id', $sale->id)->delete();
            $sale->delete();
        }

        foreach ((array) Arr::get($data, 'meter_sales', []) as $row) {
            if (!is_array($row) || empty($row['pump_id'])) continue;
            $this->assertModuleRecord(PdirectnewPump::class, (int) $row['pump_id'], (int) $settlement->business_id, (int) $settlement->location_id, false);
            if (!empty($row['product_id'])) $this->assertSharedRecord('products', (int) $row['product_id'], (int) $settlement->business_id);
            $opening = (float) ($row['opening_meter'] ?? 0);
            $closing = (float) ($row['closing_meter'] ?? 0);
            $testing = (float) ($row['testing_qty'] ?? 0);
            $sold = max(0, $closing - $opening - $testing);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $discount = (float) ($row['discount_value'] ?? 0);
            $amount = max(0, ($sold * $unitPrice) - $discount);
            PdirectnewSettlementMeterSale::create([
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'assignment_id' => $row['assignment_id'] ?? null,
                'pump_id' => $row['pump_id'],
                'product_id' => $row['product_id'] ?? null,
                'opening_meter' => $opening,
                'closing_meter' => $closing,
                'testing_qty' => $testing,
                'sold_qty' => $sold,
                'unit_price' => $unitPrice,
                'discount_type' => $row['discount_type'] ?? 'fixed',
                'discount_value' => $discount,
                'amount' => $amount,
            ]);
        }

        foreach ((array) Arr::get($data, 'payments', []) as $row) {
            if (!is_array($row) || (float) ($row['amount'] ?? 0) == 0) continue;
            if (!empty($row['contact_id'])) $this->assertSharedRecord('contacts', (int) $row['contact_id'], (int) $settlement->business_id);
            if (!empty($row['account_id'])) $this->assertSharedRecord('accounts', (int) $row['account_id'], (int) $settlement->business_id);
            PdirectnewSettlementPayment::create([
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'payment_type' => $row['payment_type'] ?? 'cash',
                'reference_no' => $row['reference_no'] ?? null,
                'contact_id' => $row['contact_id'] ?? null,
                'account_id' => $row['account_id'] ?? null,
                'amount' => (float) $row['amount'],
                'payment_date' => $row['payment_date'] ?? $settlement->transaction_date,
                'status' => 'active',
                'details' => $row['details'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
        }

        foreach ((array) Arr::get($data, 'other_income', []) as $row) {
            if (!is_array($row) || (float) ($row['amount'] ?? 0) == 0) continue;
            if (!empty($row['account_id'])) $this->assertSharedRecord('accounts', (int) $row['account_id'], (int) $settlement->business_id);
            PdirectnewSettlementOtherIncome::create([
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'description' => $row['description'] ?? 'Other Income',
                'amount' => (float) $row['amount'],
                'account_id' => $row['account_id'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
        }

        foreach ((array) Arr::get($data, 'customer_payments', []) as $row) {
            if (!is_array($row) || empty($row['contact_id']) || (float) ($row['amount'] ?? 0) == 0) continue;
            $this->assertSharedRecord('contacts', (int) $row['contact_id'], (int) $settlement->business_id);
            PdirectnewSettlementCustomerPayment::create([
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'contact_id' => $row['contact_id'],
                'reference_no' => $row['reference_no'] ?? null,
                'amount' => (float) $row['amount'],
                'payment_method' => $row['payment_method'] ?? 'cash',
                'created_by' => $this->context->userId(),
            ]);
        }

        foreach ((array) Arr::get($data, 'other_sales', []) as $saleData) {
            if (!is_array($saleData)) continue;
            $lines = (array) ($saleData['lines'] ?? []);
            $total = 0;
            foreach ($lines as $line) {
                $total += (float) ($line['line_total'] ?? ((float) ($line['qty'] ?? 0) * (float) ($line['unit_price'] ?? 0)));
            }
            if ($total <= 0) continue;
            if (!empty($saleData['contact_id'])) $this->assertSharedRecord('contacts', (int) $saleData['contact_id'], (int) $settlement->business_id);
            $sale = PdirectnewSettlementOtherSale::create([
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'reference_no' => $saleData['reference_no'] ?? null,
                'contact_id' => $saleData['contact_id'] ?? null,
                'total' => $total,
                'notes' => $saleData['notes'] ?? null,
                'created_by' => $this->context->userId(),
            ]);
            foreach ($lines as $line) {
                $qty=(float)($line['qty']??0); $price=(float)($line['unit_price']??0);
                if ($qty <= 0) continue;
                if (!empty($line['product_id'])) $this->assertSharedRecord('products', (int) $line['product_id'], (int) $settlement->business_id);
                PdirectnewSettlementOtherSaleLine::create([
                    'other_sale_id' => $sale->id,
                    'product_id' => $line['product_id'] ?? null,
                    'variation_id' => $line['variation_id'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'tax_amount' => (float)($line['tax_amount']??0),
                    'line_total' => (float)($line['line_total']??($qty*$price)),
                ]);
            }
        }
    }

    public function recalculate(PdirectnewSettlement $settlement): void
    {
        $meterSales = (float) $settlement->meterSales()->sum('amount');
        $otherSales = (float) $settlement->otherSales()->sum('total');
        $otherIncome = (float) $settlement->otherIncome()->sum('amount');
        $expected = $meterSales + $otherSales + $otherIncome;
        $received = (float) $settlement->payments()->where('status','active')->whereNotIn('payment_type', ['shortage','excess'])->sum('amount')
            + (float) $settlement->customerPayments()->sum('amount');
        $settlement->expected_total = round($expected, 4);
        $settlement->received_total = round($received, 4);
        $settlement->variance = round($received - $expected, 4);
        $settlement->save();
    }

    private function assertModuleRecord(string $modelClass, int $id, int $businessId, int $locationId, bool $allowBusinessWide): void
    {
        $query = $modelClass::query()->where('business_id', $businessId)->whereKey($id);
        if ((new $modelClass)->getTable() !== 'pdirectnew_operators' || !$allowBusinessWide) {
            $query->where('location_id', $locationId);
        } else {
            $query->where(function ($q) use ($locationId) { $q->where('location_id', $locationId)->orWhereNull('location_id'); });
        }
        abort_unless($query->exists(), 422, 'The selected Petro Direct-New record is outside the active business or location.');
    }

    private function assertSharedRecord(string $table, int $id, int $businessId): void
    {
        abort_unless(Schema::hasTable($table), 422, 'Required shared master table is unavailable.');
        $query = DB::table($table)->where('id', $id);
        if (Schema::hasColumn($table, 'business_id')) $query->where('business_id', $businessId);
        abort_unless($query->exists(), 422, 'The selected shared master record is outside the active business.');
    }

    public function finalize(PdirectnewSettlement $settlement): PdirectnewSettlement
    {
        abort_unless(in_array($settlement->status, ['draft','review','approved','reopened'], true), 422);
        $this->recalculate($settlement);
        $before = $settlement->toArray();
        $settlement->status = 'finalized';
        $settlement->finalized_at = Carbon::now();
        $settlement->finalized_by = $this->context->userId();
        $settlement->save();
        $this->audit->record('finalized', 'settlement', $settlement->id, $before, $settlement->toArray());
        return $settlement;
    }
}
