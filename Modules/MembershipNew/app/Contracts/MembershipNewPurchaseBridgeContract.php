<?php

namespace Modules\MembershipNew\app\Contracts;

interface MembershipNewPurchaseBridgeContract
{
    public function previewMembershipBenefits(array $purchaseData): array;

    public function confirmMembershipPurchase(array $purchaseData): array;
}
