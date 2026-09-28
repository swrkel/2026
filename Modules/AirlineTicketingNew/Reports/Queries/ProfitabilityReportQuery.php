<?php

namespace Modules\AirlineTicketingNew\Reports\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProfitabilityReportQuery
{
    public function build(int $businessId, array $filters): Builder
    {
        return DB::table('atn_ticket_profits as p')
            ->join('atn_tickets as t', 't.id', '=', 'p.ticket_id')
            ->where('p.business_id', $businessId)
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('t.issue_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('t.issue_date', '<=', $date))
            ->select([
                't.ticket_no','t.issue_date','p.sale_amount','p.supplier_cost','p.tax_cost',
                'p.agent_commission','p.staff_incentive','p.other_cost','p.gross_profit','p.net_profit'
            ]);
    }
}
