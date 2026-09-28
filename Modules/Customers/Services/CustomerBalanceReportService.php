<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

class CustomerBalanceReportService
{
    public function data(int $businessId, int $limit = 500): array
    {
        $customers = Customer::forBusiness($businessId)
            ->customersOnly()
            ->whereNull('deleted_at')
            ->select(['id', 'contact_id', 'name', 'mobile', 'credit_limit', 'active'])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $balances = $this->balances($businessId, $customers->pluck('id')->all());

        $rows = $customers->map(function ($customer) use ($balances) {
            $balance = (float) ($balances[$customer->id] ?? 0);
            $creditLimit = (float) ($customer->credit_limit ?? 0);

            return (object) [
                'id' => $customer->id,
                'customer_code' => $customer->contact_id,
                'customer_name' => $customer->name,
                'mobile' => $customer->mobile,
                'credit_limit' => $creditLimit,
                'balance' => $balance,
                'available_credit' => $creditLimit > 0 ? max($creditLimit - max($balance, 0), 0) : 0,
                'status' => ((int) ($customer->active ?? 0) === 1) ? 'Active' : 'Inactive',
            ];
        });

        return [
            'rows' => $rows,
            'summary' => [
                'total_customers' => $rows->count(),
                'total_balance' => $rows->sum('balance'),
                'total_credit_limit' => $rows->sum('credit_limit'),
                'total_available_credit' => $rows->sum('available_credit'),
            ],
        ];
    }

    protected function balances(int $businessId, array $customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }

        if (Schema::hasTable('contact_ledgers')) {
            return DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->whereIn('contact_id', $customerIds)
                ->whereNull('deleted_at')
                ->selectRaw("contact_id, SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
                ->groupBy('contact_id')
                ->pluck('balance', 'contact_id')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->toArray();
        }

        return [];
    }
}
