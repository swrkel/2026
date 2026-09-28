<?php

namespace Modules\Customers\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Services\CustomerLedgerService;

class CustomerAgingReport
{
    protected $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function data(int $businessId): array
    {
        return [
            'summary' => $this->ledgerService->summary($businessId),
            'aging' => $this->ledgerService->agingSummary($businessId),
            'aging_detail' => $this->customerAgingDetail($businessId),
        ];
    }

    protected function customerAgingDetail(int $businessId)
    {
        if (!Schema::hasTable('contacts')) {
            return collect();
        }

        $rows = collect();
        $customers = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('type', 'customer')
            ->when(Schema::hasColumn('contacts', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->select(['id', 'contact_id', 'name'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        foreach ($customers as $customer) {
            $summary = $this->ledgerService->ledgerSummary($businessId, (int) $customer->id);
            $balance = (float) ($summary['balance'] ?? 0);
            if ($balance <= 0) {
                continue;
            }

            $lastDate = null;
            if (Schema::hasTable('contact_ledgers')) {
                $lastDate = DB::table('contact_ledgers')
                    ->where('business_id', $businessId)
                    ->where('contact_id', $customer->id)
                    ->when(Schema::hasColumn('contact_ledgers', 'deleted_at'), function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->orderByDesc(Schema::hasColumn('contact_ledgers', 'operation_date') ? 'operation_date' : 'created_at')
                    ->value(Schema::hasColumn('contact_ledgers', 'operation_date') ? 'operation_date' : 'created_at');
            }

            $lastDate = $lastDate ?: date('Y-m-d');
            $age = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($lastDate)))) / 86400));
            $bucket = $age <= 0 ? 'Current' : ($age <= 30 ? '1 - 30 Days' : ($age <= 60 ? '31 - 60 Days' : ($age <= 90 ? '61 - 90 Days' : 'Over 90 Days')));

            $rows->push((object) [
                'customer_code' => $customer->contact_id,
                'customer_name' => $customer->name,
                'last_date' => date('Y-m-d', strtotime($lastDate)),
                'age_days' => $age,
                'bucket' => $bucket,
                'amount' => $balance,
            ]);
        }

        return $rows;
    }
}
