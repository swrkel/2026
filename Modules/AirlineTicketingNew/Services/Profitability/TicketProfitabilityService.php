<?php

namespace Modules\AirlineTicketingNew\Services\Profitability;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketProfit;

class TicketProfitabilityService
{
    public function calculate(Ticket $ticket, array $costs): TicketProfit
    {
        $sale = (float) $ticket->grand_total;
        $supplier = (float) ($costs['supplier_cost'] ?? 0);
        $tax = (float) ($costs['tax_cost'] ?? 0);
        $commission = (float) ($costs['agent_commission'] ?? 0);
        $incentive = (float) ($costs['staff_incentive'] ?? 0);
        $other = (float) ($costs['other_cost'] ?? 0);

        return DB::transaction(fn () => TicketProfit::query()->updateOrCreate(
            ['business_id' => $ticket->business_id, 'ticket_id' => $ticket->id],
            [
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'sale_amount' => $sale,
                'supplier_cost' => $supplier,
                'tax_cost' => $tax,
                'agent_commission' => $commission,
                'staff_incentive' => $incentive,
                'other_cost' => $other,
                'gross_profit' => round($sale - $supplier, 4),
                'net_profit' => round($sale - $supplier - $tax - $commission - $incentive - $other, 4),
                'calculated_at' => now(),
            ]
        ));
    }
}
