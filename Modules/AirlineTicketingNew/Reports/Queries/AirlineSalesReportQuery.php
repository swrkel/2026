<?php

namespace Modules\AirlineTicketingNew\Reports\Queries;

use Illuminate\Support\Facades\DB;

class AirlineSalesReportQuery
{
    public function build(int $businessId, array $filters)
    {
        return DB::table('atn_tickets as t')
            ->leftJoin('atn_airlines as a', 'a.id', '=', 't.airline_id')
            ->where('t.business_id', $businessId)
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->whereDate('t.issue_date','>=',$v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->whereDate('t.issue_date','<=',$v))
            ->groupBy('t.airline_id','a.name','t.currency_code')
            ->selectRaw('a.name airline_name, t.currency_code, COUNT(*) ticket_count, SUM(t.grand_total) sales_total, SUM(t.tax_total) tax_total');
    }
}
