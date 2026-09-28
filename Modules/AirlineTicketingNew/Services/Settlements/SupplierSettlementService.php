<?php

namespace Modules\AirlineTicketingNew\Services\Settlements;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\SupplierSettlement;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class SupplierSettlementService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(array $data, array $lines): SupplierSettlement
    {
        return DB::transaction(function () use ($data, $lines): SupplierSettlement {
            $gross = collect($lines)->sum(fn ($line) => (float) ($line['supplier_cost'] ?? 0));
            $commission = collect($lines)->sum(fn ($line) => (float) ($line['commission_amount'] ?? 0));
            $tax = collect($lines)->sum(fn ($line) => (float) ($line['tax_amount'] ?? 0));
            $other = (float) ($data['other_deduction'] ?? 0);
            $net = round($gross - $commission - $tax - $other, 4);

            $settlement = SupplierSettlement::query()->create(array_merge($data, [
                'settlement_no' => $this->numbers->next(
                    $data['business_id'],
                    $data['business_location_id'] ?? null,
                    $data['store_id'] ?? null,
                    'supplier_settlement',
                    'ATSS'
                ),
                'gross_payable' => $gross,
                'commission_deduction' => $commission,
                'tax_deduction' => $tax,
                'net_payable' => $net,
                'paid_amount' => 0,
                'due_amount' => $net,
                'status' => 'unpaid',
            ]));

            foreach ($lines as $line) {
                $settlement->lines()->create(array_merge($line, [
                    'business_id' => $settlement->business_id,
                    'business_location_id' => $settlement->business_location_id,
                    'store_id' => $settlement->store_id,
                    'status' => 'included',
                ]));
            }

            return $settlement->fresh('lines');
        });
    }
}
