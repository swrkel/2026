<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerTransactionReportService
{
    public function data(int $businessId, int $limit = 500): array
    {
        if (!Schema::hasTable('transactions')) {
            return ['rows' => collect(), 'summary' => ['total_amount' => 0, 'total_records' => 0]];
        }

        $rows = DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $businessId)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.type', ['sell', 'opening_balance', 'advance_payment', 'sell_return', 'settlement', 'security_deposit', 'direct_customer_loan'])
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.type',
                'transactions.status',
                'transactions.payment_status',
                'transactions.final_total',
                'contacts.name as customer_name',
                'contacts.contact_id as customer_code',
            ])
            ->orderByDesc('transactions.transaction_date')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();

        return [
            'rows' => $rows,
            'summary' => [
                'total_records' => $rows->count(),
                'total_amount' => $rows->sum('final_total'),
            ],
        ];
    }
}
