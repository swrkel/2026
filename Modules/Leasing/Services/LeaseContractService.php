<?php

namespace Modules\Leasing\Services;

use Illuminate\Support\Facades\DB;
use Modules\Leasing\Models\LeaseContract;
use Modules\Leasing\Models\LeasingTransaction;

class LeaseContractService
{
    public function create(array $data, array $lease_assetIds = [])
    {
        return DB::transaction(function () use ($data, $lease_assetIds) {
            $lease_contract = LeaseContract::create($data);
            if (! empty($lease_assetIds)) {
                $sync = [];
                foreach ($lease_assetIds as $lease_assetId) {
                    if ($lease_assetId) {
                        $sync[$lease_assetId] = ['lease_asset_value' => $data['assessed_value'] ?? 0];
                    }
                }
                $lease_contract->lease_assets()->sync($sync);
            }

            LeasingTransaction::create([
                'business_id' => $lease_contract->business_id,
                'location_id' => $lease_contract->location_id,
                'leasing_lease_contract_id' => $lease_contract->id,
                'transaction_no' => $lease_contract->lease_contract_no . '-ADV',
                'type' => 'advance',
                'transaction_date' => $lease_contract->lease_contractd_on ?: date('Y-m-d'),
                'amount' => $lease_contract->advance_amount,
                'balance_after' => $lease_contract->outstanding_amount,
                'notes' => 'Initial lease_contract advance',
            ]);

            return $lease_contract;
        });
    }

    public function redeem(LeaseContract $lease_contract, $amount, $date = null)
    {
        return DB::transaction(function () use ($lease_contract, $amount, $date) {
            $lease_contract->status = 'redeemed';
            $lease_contract->workflow_status = 'redeemed';
            $lease_contract->outstanding_amount = 0;
            $lease_contract->save();

            LeasingTransaction::create([
                'business_id' => $lease_contract->business_id,
                'location_id' => $lease_contract->location_id,
                'leasing_lease_contract_id' => $lease_contract->id,
                'transaction_no' => $lease_contract->lease_contract_no . '-RED',
                'type' => 'payment',
                'transaction_date' => $date ?: date('Y-m-d'),
                'amount' => $amount,
                'balance_after' => 0,
                'notes' => 'LeaseContract redeemed',
            ]);

            return $lease_contract;
        });
    }
}
