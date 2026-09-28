<?php

namespace Modules\AirlineTicketingNew\Reports\Queries;

use Illuminate\Support\Facades\DB;

class OutstandingReportQuery
{
    public function build(int $businessId)
    {
        return DB::table('atn_invoices')
            ->where('business_id', $businessId)
            ->where('due_total', '>', 0)
            ->orderByDesc('invoice_date');
    }
}
