<?php

namespace Modules\MembershipNew\app\Reports;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayment;
use Modules\MembershipNew\app\Models\MembershipNewPointTransaction;
use Modules\MembershipNew\app\Models\MembershipNewShareHolding;

class MembershipNewReportBuilder
{
    public function memberBalances(int $businessId, ?string $search = null)
    {
        return DB::table('mn_members as m')
            ->leftJoin('mn_point_transactions as p', function ($join) {
                $join->on('m.id', '=', 'p.member_id')->whereNull('p.deleted_at');
            })
            ->where('m.business_id', $businessId)
            ->whereNull('m.deleted_at')
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('m.member_code', 'like', $like)
                        ->orWhere('m.first_name', 'like', $like)
                        ->orWhere('m.last_name', 'like', $like)
                        ->orWhere('m.mobile', 'like', $like)
                        ->orWhere('m.email', 'like', $like)
                        ->orWhere('m.nic', 'like', $like);
                });
            })
            ->select(
                'm.id',
                'm.member_code',
                'm.first_name',
                'm.last_name',
                'm.mobile',
                'm.joined_on',
                'm.created_at',
                'm.created_by',
                DB::raw('COALESCE(SUM(p.points),0) as point_balance')
            )
            ->groupBy('m.id', 'm.member_code', 'm.first_name', 'm.last_name', 'm.mobile', 'm.joined_on', 'm.created_at', 'm.created_by')
            ->orderBy('m.id', 'desc');
    }

    public function pointLedger(int $businessId, ?string $search = null)
    {
        return MembershipNewPointTransaction::with('member')->forBusiness($businessId)
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('type', 'like', $like)
                        ->orWhere('reference_type', 'like', $like)
                        ->orWhere('reference_id', 'like', $like)
                        ->orWhereHas('member', function ($member) use ($like) {
                            $member->where('member_code', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('mobile', 'like', $like);
                        });
                });
            })->latest();
    }

    public function shareRegister(int $businessId, ?string $search = null)
    {
        return MembershipNewShareHolding::with('member')->forBusiness($businessId)
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->whereHas('member', function ($member) use ($like) {
                    $member->where('member_code', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like);
                });
            })->latest();
    }

    public function dividendRegister(int $businessId, ?string $search = null)
    {
        return MembershipNewDividendPayment::with('member')->forBusiness($businessId)
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->whereHas('member', function ($member) use ($like) {
                    $member->where('member_code', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like);
                });
            })->latest();
    }
}
