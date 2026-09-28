<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyPrepaidPackage;
use Modules\BeautySaloons\Entities\BeautyPrepaidPackageLine;
use Modules\BeautySaloons\Entities\BeautyPrepaidPackageSale;
use Modules\BeautySaloons\Entities\BeautyPrepaidPackageUsage;

class PrepaidPackageService
{
    public function indexData(): array
    {
        return ['packages' => BeautyPrepaidPackage::latest()->paginate(25)];
    }

    public function store(array $data): BeautyPrepaidPackage
    {
        return DB::transaction(function () use ($data) {
            $package = BeautyPrepaidPackage::create([
                'business_id' => $data['business_id'] ?? auth()->user()->business_id ?? null,
                'business_location_id' => $data['business_location_id'] ?? null,
                'package_code' => $data['package_code'] ?? null,
                'package_name' => $data['package_name'] ?? null,
                'package_type' => $data['package_type'] ?? 'service',
                'sale_price' => $this->num($data['sale_price'] ?? 0),
                'valid_days' => (int) ($data['valid_days'] ?? 0),
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach (($data['lines'] ?? []) as $line) {
                if (empty($line['item_name'])) { continue; }
                BeautyPrepaidPackageLine::create([
                    'prepaid_package_id' => $package->id,
                    'item_type' => $line['item_type'] ?? 'service',
                    'item_id' => $line['item_id'] ?? null,
                    'item_name' => $line['item_name'],
                    'qty' => $this->num($line['qty'] ?? 1),
                    'value' => $this->num($line['value'] ?? 0),
                ]);
            }

            return $package;
        });
    }

    public function sell(array $data): BeautyPrepaidPackageSale
    {
        return DB::transaction(function () use ($data) {
            $package = BeautyPrepaidPackage::findOrFail($data['prepaid_package_id']);
            return BeautyPrepaidPackageSale::create([
                'business_id' => $package->business_id,
                'business_location_id' => $data['business_location_id'] ?? $package->business_location_id,
                'prepaid_package_id' => $package->id,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'expiry_date' => now()->parse($data['sale_date'] ?? now())->addDays((int) $package->valid_days)->toDateString(),
                'sale_amount' => $this->num($data['sale_amount'] ?? $package->sale_price),
                'remaining_value' => $this->num($data['sale_amount'] ?? $package->sale_price),
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function consume(int $saleId, array $data): BeautyPrepaidPackageUsage
    {
        return DB::transaction(function () use ($saleId, $data) {
            $sale = BeautyPrepaidPackageSale::lockForUpdate()->findOrFail($saleId);
            $amount = $this->num($data['amount'] ?? 0);
            if ($sale->remaining_value < $amount) {
                throw new \RuntimeException('Insufficient prepaid package balance.');
            }
            $sale->remaining_value -= $amount;
            $sale->status = $sale->remaining_value <= 0 ? 'used' : 'active';
            $sale->save();

            return BeautyPrepaidPackageUsage::create([
                'package_sale_id' => $sale->id,
                'usage_date' => $data['usage_date'] ?? now()->toDateString(),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'amount' => $amount,
                'remaining_value' => $sale->remaining_value,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function reportData(): array
    {
        return [
            'sales' => BeautyPrepaidPackageSale::latest()->paginate(50),
            'usages' => BeautyPrepaidPackageUsage::latest()->paginate(50),
        ];
    }

    private function num($value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }
}
