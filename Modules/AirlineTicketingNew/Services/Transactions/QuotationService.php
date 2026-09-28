<?php

namespace Modules\AirlineTicketingNew\Services\Transactions;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Quotation;

class QuotationService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly QuotationCalculationService $calculator
    ) {
    }

    public function create(array $header, array $segments): Quotation
    {
        return DB::transaction(function () use ($header, $segments): Quotation {
            $calculated = $this->calculator->calculate($segments);

            $header['quotation_no'] = $this->numbers->next(
                $header['business_id'],
                $header['business_location_id'] ?? null,
                $header['store_id'] ?? null,
                'quotation',
                'ATQ'
            );

            $quotation = Quotation::query()->create(array_merge($header, [
                'subtotal' => $calculated['subtotal'],
                'tax_total' => $calculated['tax_total'],
                'service_fee_total' => $calculated['service_fee_total'],
                'discount_total' => $calculated['discount_total'],
                'grand_total' => $calculated['grand_total'],
                'status' => $header['status'] ?? 'draft',
            ]));

            foreach ($calculated['segments'] as $index => $segment) {
                $quotation->segments()->create(array_merge($segment, [
                    'business_id' => $quotation->business_id,
                    'business_location_id' => $quotation->business_location_id,
                    'store_id' => $quotation->store_id,
                    'segment_no' => $index + 1,
                ]));
            }

            return $quotation->fresh(['segments']);
        });
    }
}
