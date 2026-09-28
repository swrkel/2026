<?php

namespace Modules\MembershipNew\app\Integration\Examples;

use Modules\MembershipNew\app\Integration\MembershipNewPurchaseIntegration;
use Modules\MembershipNew\app\Integration\MembershipNewCentralCustomerBridge;

class POSMembershipNewBridgeExample
{
    public function afterSaleSaved(object $sale, array $membershipPayload): array
    {
        // Example only. Do not call this class directly from production without adapting to real POS models.

        $customerBridge = app(MembershipNewCentralCustomerBridge::class);
        $purchaseBridge = app(MembershipNewPurchaseIntegration::class);

        $central = $customerBridge->registerOrLinkCustomer([
            'business_id' => $sale->business_id,
            'local_customer_id' => $sale->contact_id ?? null,
            'first_name' => $membershipPayload['first_name'] ?? null,
            'last_name' => $membershipPayload['last_name'] ?? null,
            'mobile' => $membershipPayload['mobile'] ?? null,
            'email' => $membershipPayload['email'] ?? null,
            'nic' => $membershipPayload['nic'] ?? null,
        ]);

        $customerBridge->recordBusinessSale([
            'business_id' => $sale->business_id,
            'member_business_map_id' => $central['member_business_map_id'],
            'amount' => $sale->final_total ?? 0,
            'reference_type' => 'pos_sale',
            'reference_id' => $sale->id,
        ]);

        return $purchaseBridge->confirm([
            'business_id' => $sale->business_id,
            'member_business_map_id' => $central['member_business_map_id'],
            'central_member_id' => $central['central_member_id'],
            'member_id' => $membershipPayload['member_id'] ?? null,
            'outlet_business_id' => $sale->business_id,
            'location_id' => $sale->location_id ?? null,
            'category_id' => $membershipPayload['category_id'] ?? null,
            'purchase_amount' => $sale->final_total ?? 0,
            'redeem_points' => $membershipPayload['redeem_points'] ?? 0,
            'reference_type' => 'pos_sale',
            'reference_id' => $sale->id,
            'source_module' => 'pos',
        ]);
    }
}
