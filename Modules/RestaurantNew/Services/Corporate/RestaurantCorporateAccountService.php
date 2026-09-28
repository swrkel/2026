<?php

namespace Modules\RestaurantNew\Services\Corporate;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewCorporateAccount;
use Modules\RestaurantNew\Entities\RestaurantNewCorporateInvoice;

class RestaurantCorporateAccountService
{
    public function accounts(int $businessId, ?int $locationId = null)
    {
        return RestaurantNewCorporateAccount::query()
            ->forBusiness($businessId)
            ->when($locationId, fn ($q) => $q->where(function ($x) use ($locationId) {
                $x->whereNull('location_id')->orWhere('location_id', $locationId);
            }))
            ->latest('id');
    }

    public function createAccount(array $data): RestaurantNewCorporateAccount
    {
        return RestaurantNewCorporateAccount::create($data);
    }

    public function createMonthlyInvoice(array $header, array $lines): RestaurantNewCorporateInvoice
    {
        return DB::transaction(function () use ($header, $lines) {
            $invoice = RestaurantNewCorporateInvoice::create($header);
            $subtotal = 0;
            foreach ($lines as $line) {
                $qty = (float) ($line['qty'] ?? 1);
                $unit = (float) ($line['unit_price'] ?? 0);
                $total = $qty * $unit;
                $subtotal += $total;
                $invoice->lines()->create(array_merge($line, [
                    'business_id' => $header['business_id'],
                    'line_total' => $total,
                ]));
            }
            $grand = $subtotal + (float)($header['tax_total'] ?? 0) - (float)($header['discount_total'] ?? 0);
            $invoice->update(['subtotal' => $subtotal, 'grand_total' => $grand, 'balance_amount' => $grand]);
            return $invoice->fresh();
        });
    }
}
