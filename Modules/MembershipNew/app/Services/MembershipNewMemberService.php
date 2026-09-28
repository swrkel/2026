<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewMember;

class MembershipNewMemberService
{
    public function create(array $data): MembershipNewMember
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['member_code']) || trim((string) $data['member_code']) === '') {
                $data['member_code'] = $this->nextMemberCode((int) $data['business_id']);
            }
            $data['joined_on'] = $data['joined_on'] ?? now()->toDateString();
            $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;
            return MembershipNewMember::create($data);
        });
    }

    public function nextMemberCode(int $businessId): string
    {
        $next = ((int) MembershipNewMember::forBusiness($businessId)->max('id')) + 1;
        return 'MN-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
