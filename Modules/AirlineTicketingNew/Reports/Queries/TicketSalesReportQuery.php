<?php

namespace Modules\AirlineTicketingNew\Reports\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TicketSalesReportQuery
{
    public function build(int $businessId, array $filters): Builder
    {
        return DB::table('atn_tickets as t')
            ->leftJoin('atn_airlines as a', 'a.id', '=', 't.airline_id')
            ->leftJoin('atn_passengers as p', 'p.id', '=', 't.passenger_id')
            ->where('t.business_id', $businessId)
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('t.issue_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('t.issue_date', '<=', $date))
            ->when($filters['business_location_id'] ?? null, fn ($q, $id) => $q->where('t.business_location_id', $id))
            ->when($filters['store_id'] ?? null, fn ($q, $id) => $q->where('t.store_id', $id))
            ->select([
                't.ticket_no','t.issue_date','a.name as airline_name',
                DB::raw("CONCAT_WS(' ',p.first_name,p.last_name) as passenger_name"),
                't.currency_code','t.base_fare','t.tax_total','t.service_fee_total','t.grand_total','t.status'
            ]);
    }
}
