<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LoyaltyService
{
    public function dashboard(): array
    {
        $members = $this->members();
        $tiers = $this->tiers();
        $ledger = $this->ledger();

        return [
            'members_count' => count($members),
            'active_members' => count(array_filter($members, fn($m) => ($m->status ?? '') === 'active')),
            'total_points' => array_sum(array_map(fn($m) => (float)($m->points_balance ?? 0), $members)),
            'tiers_count' => count($tiers),
            'members' => $members,
            'tiers' => $tiers,
            'ledger' => $ledger,
            'notes' => [
                'Loyalty data is stored inside the tenant database and scoped by business and business location.',
                'Points can be earned from room revenue, POS, room service, banquets, conference bookings or manual adjustment.',
                'This is a HotelManagement standalone layer. It can bridge to Customers/Communication Hub without duplicating those modules.',
            ],
        ];
    }

    public function saveTier(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_loyalty_tiers')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'name' => $data['name'],
            'min_points' => $data['min_points'] ?? 0,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'benefits' => $data['benefits'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_loyalty_tiers')->where('business_id', $this->businessId())->where('code', $data['code']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_loyalty_tiers')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'code' => $data['code'],
                'created_at' => now(),
            ]));
        }
    }

    public function saveMember(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_loyalty_members')) return;
        $memberNo = $data['member_no'] ?? $this->nextMemberNo();
        $values = [
            'business_location_id' => $this->locationId(),
            'guest_id' => $data['guest_id'] ?? null,
            'tier_id' => $data['tier_id'] ?? null,
            'guest_name' => $data['guest_name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'join_date' => $data['join_date'] ?? now()->toDateString(),
            'status' => $data['status'] ?? 'active',
            'created_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_loyalty_members')->where('business_id', $this->businessId())->where('member_no', $memberNo);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_loyalty_members')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'member_no' => $memberNo,
                'created_at' => now(),
            ]));
        }
    }

    public function postPoints(int $memberId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_loyalty_point_ledger') || !Schema::hasTable('hm_loyalty_members')) return;
        $type = $data['type'] ?? 'earn';
        $points = (float)($data['points'] ?? 0);
        $signed = in_array($type, ['redeem','expire','reverse']) ? -abs($points) : abs($points);

        DB::transaction(function () use ($memberId, $data, $userId, $type, $points, $signed) {
            DB::table('hm_loyalty_point_ledger')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'member_id' => $memberId,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'type' => $type,
                'points' => $points,
                'signed_points' => $signed,
                'amount' => $data['amount'] ?? 0,
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_loyalty_members')->where('id', $memberId)->update([
                'points_balance' => DB::raw('COALESCE(points_balance,0) + '.((float)$signed)),
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function tiers(): array
    {
        if (!Schema::hasTable('hm_loyalty_tiers')) return [];
        try { return DB::table('hm_loyalty_tiers')->orderBy('min_points')->orderBy('id')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function members(): array
    {
        if (!Schema::hasTable('hm_loyalty_members')) return [];
        try {
            $q = DB::table('hm_loyalty_members as m')->leftJoin('hm_loyalty_tiers as t','m.tier_id','=','t.id')
                ->select('m.*','t.name as tier_name')->orderByDesc('m.id')->limit(100);
            return $q->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function ledger(): array
    {
        if (!Schema::hasTable('hm_loyalty_point_ledger')) return [];
        try { return DB::table('hm_loyalty_point_ledger')->orderByDesc('id')->limit(50)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextMemberNo(): string
    {
        $prefix = 'HML'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_loyalty_members')) {
            $last = DB::table('hm_loyalty_members')->where('member_no','like',$prefix.'%')->orderByDesc('id')->value('member_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
