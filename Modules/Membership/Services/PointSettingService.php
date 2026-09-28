<?php

namespace Modules\Membership\Services;

use Modules\Membership\Entities\MembershipBusinessType;
use Modules\Membership\Entities\MembershipPointSetting;

class PointSettingService
{
    public function queryForBusiness(int $businessId)
    {
        return MembershipPointSetting::where('business_id', $businessId)
            ->with([
                'businessType:id,business_type',
                'createdBy:id,username,first_name,last_name',
            ])
            ->select(
                'id',
                'membership_business_type_id',
                'reward_point_percent',
                'min_bill_total_to_earn',
                'max_points_per_bill',
                'min_bill_total_to_redeem',
                'min_redeem_point',
                'max_redeem_point_per_bill',
                'expiry_period_months',
                'expiry_period_years',
                'created_by',
                'created_at'
            )
            ->orderBy('created_at', 'desc');
    }

    public function activeBusinessTypesForBusiness(int $businessId)
    {
        return MembershipBusinessType::whereIn('business_id', [0, $businessId])
            ->orderByRaw('business_id = 0 DESC')
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            });
    }

    public function findForBusiness(int $id, int $businessId): MembershipPointSetting
    {
        return MembershipPointSetting::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();
    }

    public function create(array $data, int $businessId, int $userId): MembershipPointSetting
    {
        return MembershipPointSetting::create([
            'business_id' => $businessId,
            'membership_business_type_id' => $data['membership_business_type_id'],
            'reward_point_percent' => $data['reward_point_percent'],
            'min_bill_total_to_earn' => $data['min_bill_total_to_earn'],
            'max_points_per_bill' => $data['max_points_per_bill'],
            'min_bill_total_to_redeem' => $data['min_bill_total_to_redeem'],
            'min_redeem_point' => $data['min_redeem_point'],
            'max_redeem_point_per_bill' => $data['max_redeem_point_per_bill'],
            'expiry_period_months' => $data['expiry_period_months'] ?? null,
            'expiry_period_years' => null,
            'created_by' => $userId,
        ]);
    }

    public function update(MembershipPointSetting $pointSetting, array $data): MembershipPointSetting
    {
        $pointSetting->update([
            'membership_business_type_id' => $data['membership_business_type_id'],
            'reward_point_percent' => $data['reward_point_percent'],
            'min_bill_total_to_earn' => $data['min_bill_total_to_earn'],
            'max_points_per_bill' => $data['max_points_per_bill'],
            'min_bill_total_to_redeem' => $data['min_bill_total_to_redeem'],
            'min_redeem_point' => $data['min_redeem_point'],
            'max_redeem_point_per_bill' => $data['max_redeem_point_per_bill'],
            'expiry_period_months' => $data['expiry_period_months'] ?? null,
            'expiry_period_years' => null,
        ]);

        return $pointSetting;
    }

    public function delete(MembershipPointSetting $pointSetting): bool
    {
        return (bool) $pointSetting->delete();
    }
}
