<?php

namespace Modules\Pawning\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pawning\Models\Pledge;
use Modules\Pawning\Models\PawningTransaction;

class PledgeService
{
    public function create(array $data, array $articleIds = [])
    {
        return DB::transaction(function () use ($data, $articleIds) {
            $pledge = Pledge::create($data);
            if (! empty($articleIds)) {
                $sync = [];
                foreach ($articleIds as $articleId) {
                    if ($articleId) {
                        $sync[$articleId] = ['article_value' => $data['assessed_value'] ?? 0];
                    }
                }
                $pledge->articles()->sync($sync);
            }

            PawningTransaction::create([
                'business_id' => $pledge->business_id,
                'location_id' => $pledge->location_id,
                'pawning_pledge_id' => $pledge->id,
                'transaction_no' => $pledge->pledge_no . '-ADV',
                'type' => 'advance',
                'transaction_date' => $pledge->pledged_on ?: date('Y-m-d'),
                'amount' => $pledge->advance_amount,
                'balance_after' => $pledge->outstanding_amount,
                'notes' => 'Initial pledge advance',
            ]);

            return $pledge;
        });
    }

    public function redeem(Pledge $pledge, $amount, $date = null)
    {
        return DB::transaction(function () use ($pledge, $amount, $date) {
            $pledge->status = 'redeemed';
            $pledge->workflow_status = 'redeemed';
            $pledge->outstanding_amount = 0;
            $pledge->save();

            PawningTransaction::create([
                'business_id' => $pledge->business_id,
                'location_id' => $pledge->location_id,
                'pawning_pledge_id' => $pledge->id,
                'transaction_no' => $pledge->pledge_no . '-RED',
                'type' => 'redemption',
                'transaction_date' => $date ?: date('Y-m-d'),
                'amount' => $amount,
                'balance_after' => 0,
                'notes' => 'Pledge redeemed',
            ]);

            return $pledge;
        });
    }
}
