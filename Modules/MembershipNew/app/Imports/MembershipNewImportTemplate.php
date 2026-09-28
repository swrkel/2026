<?php

namespace Modules\MembershipNew\app\Imports;

class MembershipNewImportTemplate
{
    public static function centralMembersHeaders(): array
    {
        return [
            'central_member_code',
            'first_name',
            'last_name',
            'mobile',
            'email',
            'nic',
            'date_of_birth',
            'address',
            'card_no',
        ];
    }

    public static function sharesHeaders(): array
    {
        return [
            'central_member_code',
            'business_id',
            'shares',
            'share_value',
            'note',
        ];
    }

    public static function pointRulesHeaders(): array
    {
        return [
            'business_id',
            'outlet_business_id',
            'location_id',
            'category_id',
            'amount_step',
            'points_per_amount',
            'max_points_per_invoice',
            'is_active',
        ];
    }
}
