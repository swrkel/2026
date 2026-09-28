<?php

namespace Modules\MyHealthMembers\Services\Access;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MyHealthBusinessAccessService
{
    public function listRequests(array $filters = [])
    {
        $query = DB::table('myhealth_access_requests')->orderByDesc('id');

        foreach (['member_code', 'mobile', 'nic_no', 'status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, 'like', '%' . $filters[$field] . '%');
            }
        }

        return $query->paginate(25);
    }

    public function requestAccess(array $data, $user = null): array
    {
        $otp = (string) random_int(100000, 999999);
        $now = Carbon::now();

        $id = DB::table('myhealth_access_requests')->insertGetId([
            'member_code' => $data['member_code'],
            'purpose' => $data['purpose'],
            'access_sections' => json_encode($data['access_sections'] ?? []),
            'otp' => $otp,
            'status' => 'pending_otp',
            'requested_by' => optional($user)->id,
            'expires_at' => $now->copy()->addMinutes(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('myhealth_audit_logs')->insert([
            'reference_type' => 'business_access_request',
            'reference_id' => $id,
            'action' => 'created',
            'description' => 'Business access request created for My Health member.',
            'created_by' => optional($user)->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'success' => true,
            'access_request_id' => $id,
            'message' => 'OTP generated and access request created.',
            'otp_preview' => $otp, // Remove later when SMS/email gateway is live.
        ];
    }

    public function verifyOtp(array $data, $user = null): array
    {
        $request = DB::table('myhealth_access_requests')->where('id', $data['access_request_id'])->first();

        if (!$request) {
            return ['success' => false, 'message' => 'Access request not found.'];
        }

        if ($request->status !== 'pending_otp') {
            return ['success' => false, 'message' => 'This request is not pending OTP verification.'];
        }

        if (Carbon::parse($request->expires_at)->isPast()) {
            return ['success' => false, 'message' => 'OTP has expired. Please request access again.'];
        }

        if ((string) $request->otp !== (string) $data['otp']) {
            return ['success' => false, 'message' => 'Invalid OTP.'];
        }

        DB::table('myhealth_access_requests')->where('id', $request->id)->update([
            'status' => 'approved',
            'approved_by' => optional($user)->id,
            'approved_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return ['success' => true, 'message' => 'Access approved successfully.'];
    }

    public function revoke(int $id): void
    {
        DB::table('myhealth_access_requests')->where('id', $id)->update([
            'status' => 'revoked',
            'updated_at' => Carbon::now(),
        ]);
    }
}
