<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class BillingReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.type', ['sell', 'settlement'])
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.transaction_date',
                'contacts.name as customer_name',
                'business_locations.name as location_name',
                'transactions.final_total',
                'transactions.payment_status',
                'transactions.created_at',
            ]);

        $this->applyDateRange($query, 'transactions.transaction_date', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $this->applyLocation($query, 'transactions.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('transactions.transaction_date', 'desc')->orderBy('transactions.id', 'desc');
    }
}
