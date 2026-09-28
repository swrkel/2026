<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMember;

abstract class MyHealthApiBaseController extends Controller
{
    protected function success($data = [], string $message = 'OK', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 422, $errors = [])
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    protected function member(): MyHealthMember
    {
        return request()->attributes->get('myhealth_member');
    }

    protected function memberPayload(MyHealthMember $member): array
    {
        return [
            'id' => $member->id,
            'myhealth_code' => $member->myhealth_code,
            'name' => $member->name,
            'mobile' => $member->mobile,
            'email' => $member->email,
            'nic_no' => $member->nic_no,
            'passport_no' => $member->passport_no,
            'date_of_birth' => $member->date_of_birth,
            'gender' => $member->gender,
            'blood_group' => $member->blood_group ?? null,
            'address' => $member->address,
            'is_active' => (bool) $member->is_active,
        ];
    }
}
