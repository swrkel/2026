<?php

namespace Modules\Deposits\Services;

use Illuminate\Http\Request;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositAccountParty;

class DepositPartyService
{
    public function syncPrimaryParties(DepositAccount $account, Request $request): void
    {
        $this->saveParty($account, 'nominee', [
            'name' => $request->input('nominee_name'),
            'relationship' => $request->input('nominee_relationship'),
            'nic_no' => $request->input('nominee_nic_no'),
            'mobile' => $request->input('nominee_mobile'),
            'share_percentage' => $request->input('nominee_share_percentage', 100),
            'address' => $request->input('nominee_address'),
        ]);

        $this->saveParty($account, 'beneficiary', [
            'name' => $request->input('beneficiary_name'),
            'relationship' => $request->input('beneficiary_relationship'),
            'nic_no' => $request->input('beneficiary_nic_no'),
            'mobile' => $request->input('beneficiary_mobile'),
            'share_percentage' => $request->input('beneficiary_share_percentage', 100),
            'address' => $request->input('beneficiary_address'),
        ]);
    }

    private function saveParty(DepositAccount $account, string $type, array $data): void
    {
        if (empty($data['name'])) {
            DepositAccountParty::where('deposit_account_id', $account->id)->where('party_type', $type)->delete();
            return;
        }

        DepositAccountParty::updateOrCreate(
            ['deposit_account_id' => $account->id, 'party_type' => $type],
            array_merge($data, [
                'business_id' => $account->business_id,
                'location_id' => $account->location_id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ])
        );
    }
}
