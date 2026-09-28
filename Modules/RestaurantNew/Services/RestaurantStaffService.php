<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewCashierShift;
use Modules\RestaurantNew\Entities\RestaurantNewCashMovement;
use Modules\RestaurantNew\Entities\RestaurantNewServiceChargeDistribution;
use Modules\RestaurantNew\Entities\RestaurantNewStaffMember;
use Modules\RestaurantNew\Entities\RestaurantNewTipEntry;

class RestaurantStaffService
{
    public function createStaff(array $data): RestaurantNewStaffMember
    {
        return RestaurantNewStaffMember::create($data);
    }

    public function openShift(array $data): RestaurantNewCashierShift
    {
        return DB::transaction(function () use ($data) {
            $data['shift_no'] = $data['shift_no'] ?? $this->nextShiftNo((int) $data['business_id'], $data['location_id'] ?? null);
            $data['opened_at'] = $data['opened_at'] ?? now();
            $data['status'] = 'open';
            $data['expected_cash'] = $data['opening_cash'] ?? 0;

            return RestaurantNewCashierShift::create($data);
        });
    }

    public function addCashMovement(RestaurantNewCashierShift $shift, string $type, float $amount, array $extra = []): RestaurantNewCashMovement
    {
        return DB::transaction(function () use ($shift, $type, $amount, $extra) {
            $movement = RestaurantNewCashMovement::create(array_merge([
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'cashier_shift_id' => $shift->id,
                'movement_type' => $type,
                'amount' => $amount,
            ], $extra));

            if ($type === 'cash_in') {
                $shift->cash_in = (float) $shift->cash_in + $amount;
            }
            if ($type === 'cash_out') {
                $shift->cash_out = (float) $shift->cash_out + $amount;
            }
            $shift->expected_cash = (float) $shift->opening_cash + (float) $shift->cash_sales + (float) $shift->cash_in - (float) $shift->cash_out;
            $shift->save();

            return $movement;
        });
    }

    public function closeShift(RestaurantNewCashierShift $shift, float $countedCash, ?string $note = null): RestaurantNewCashierShift
    {
        return DB::transaction(function () use ($shift, $countedCash, $note) {
            $shift->counted_cash = $countedCash;
            $shift->expected_cash = (float) $shift->opening_cash + (float) $shift->cash_sales + (float) $shift->cash_in - (float) $shift->cash_out;
            $shift->shortage_excess = $countedCash - (float) $shift->expected_cash;
            $shift->closed_at = now();
            $shift->closing_note = $note;
            $shift->status = 'closed';
            $shift->save();

            return $shift;
        });
    }

    public function recordTip(array $data): RestaurantNewTipEntry
    {
        return RestaurantNewTipEntry::create($data);
    }

    public function distributeServiceCharge(RestaurantNewCashierShift $shift, float $baseAmount): int
    {
        $staff = RestaurantNewStaffMember::where('business_id', $shift->business_id)
            ->where(function ($query) use ($shift) {
                $query->whereNull('location_id')->orWhere('location_id', $shift->location_id);
            })
            ->where('is_active', true)
            ->where('service_charge_share_percent', '>', 0)
            ->get();

        $count = 0;
        foreach ($staff as $member) {
            RestaurantNewServiceChargeDistribution::create([
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'cashier_shift_id' => $shift->id,
                'staff_member_id' => $member->id,
                'base_amount' => $baseAmount,
                'share_percent' => $member->service_charge_share_percent,
                'distributed_amount' => round($baseAmount * ((float) $member->service_charge_share_percent / 100), 4),
                'distribution_date' => now()->toDateString(),
                'status' => 'pending',
            ]);
            $count++;
        }

        return $count;
    }

    private function nextShiftNo(int $businessId, ?int $locationId): string
    {
        $prefix = 'RNS-' . now()->format('Ymd') . '-';
        $count = RestaurantNewCashierShift::where('business_id', $businessId)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return $prefix . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
