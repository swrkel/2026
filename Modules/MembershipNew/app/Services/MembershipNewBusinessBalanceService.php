<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;

class MembershipNewBusinessBalanceService
{
    public function balances(int $businessId, ?string $search = null)
    {
        return DB::table('mn_member_business_maps as mbm')
            ->join('mn_central_members as cm', 'cm.id', '=', 'mbm.central_member_id')
            ->leftJoin('mn_business_customer_histories as h', function ($join) {
                $join->on('h.member_business_map_id', '=', 'mbm.id')->whereNull('h.deleted_at');
            })
            ->leftJoin('mn_point_transactions as p', function ($join) {
                $join->on('p.member_business_map_id', '=', 'mbm.id')->whereNull('p.deleted_at');
            })
            ->where('mbm.business_id', $businessId)
            ->whereNull('mbm.deleted_at')
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('cm.central_member_code', 'like', $like)
                        ->orWhere('cm.first_name', 'like', $like)
                        ->orWhere('cm.last_name', 'like', $like)
                        ->orWhere('cm.mobile', 'like', $like);
                });
            })
            ->select(
                'mbm.id as member_business_map_id',
                'cm.central_member_code',
                'cm.first_name',
                'cm.last_name',
                'cm.mobile',
                'mbm.created_at',
                'mbm.created_by',
                DB::raw('COALESCE(SUM(h.debit),0) as total_debit'),
                DB::raw('COALESCE(SUM(h.credit),0) as total_credit'),
                DB::raw('COALESCE(SUM(h.debit),0) - COALESCE(SUM(h.credit),0) as ledger_balance'),
                DB::raw('COALESCE(SUM(p.points),0) as point_balance')
            )
            ->groupBy('mbm.id', 'cm.central_member_code', 'cm.first_name', 'cm.last_name', 'cm.mobile', 'mbm.created_at', 'mbm.created_by')
            ->orderBy('cm.first_name');
    }
}
