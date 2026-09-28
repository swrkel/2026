<?php

namespace Modules\MembershipNew\app\Integration\Examples;

use Modules\MembershipNew\app\Integration\MembershipNewPurchaseIntegration;

class SalesMembershipNewBridgeExample
{
    public function afterInvoiceSaved(object $invoice, array $membershipPayload): array
    {
        // Example only. Adapt to the real sales transaction object.

        return app(MembershipNewPurchaseIntegration::class)->confirm([
            'business_id' => $invoice->business_id,
            'member_business_map_id' => $membershipPayload['member_business_map_id'] ?? null,
            'central_member_id' => $membershipPayload['central_member_id'] ?? null,
            'member_id' => $membershipPayload['member_id'] ?? null,
            'outlet_business_id' => $invoice->business_id,
            'location_id' => $invoice->location_id ?? null,
            'category_id' => $membershipPayload['category_id'] ?? null,
            'purchase_amount' => $invoice->final_total ?? 0,
            'redeem_points' => $membershipPayload['redeem_points'] ?? 0,
            'reference_type' => 'sales_invoice',
            'reference_id' => $invoice->id,
            'source_module' => 'sales',
        ]);
    }
}
