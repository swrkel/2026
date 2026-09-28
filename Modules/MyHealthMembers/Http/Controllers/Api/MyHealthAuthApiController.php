<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;
use Modules\MyHealthMembers\Services\MyHealthMobileTokenService;

class MyHealthAuthApiController extends MyHealthApiBaseController
{
    public function login(Request $request, MyHealthMobileTokenService $tokenService)
    {
        $data = $request->validate([
            'login_code' => 'required_without:myhealth_code|string',
            'myhealth_code' => 'required_without:login_code|string',
            'password' => 'nullable|string',
            'device_name' => 'nullable|string|max:191',
            'device_id' => 'nullable|string|max:191',
        ]);

        $code = $data['login_code'] ?? $data['myhealth_code'];

        $login = MyHealthMemberLogin::where('login_code', $code)->first();
        $member = $login ? MyHealthMember::find($login->member_id) : MyHealthMember::where('myhealth_code', $code)->first();

        if (! $member || ! $member->is_active) {
            throw ValidationException::withMessages(['login_code' => ['Invalid MyHealth login details.']]);
        }

        if ($login && ! empty($login->password) && ! Hash::check((string) $request->input('password'), $login->password)) {
            throw ValidationException::withMessages(['password' => ['Invalid MyHealth login details.']]);
        }

        if ($login) {
            $login->update(['last_login_at' => now()]);
        }

        $issued = $tokenService->issue($member, $request);

        return $this->success([
            'member' => $this->memberPayload($member),
            'access_token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => optional($issued['record']->expires_at)->toDateTimeString(),
        ], 'Logged in successfully.');
    }

    public function logout(Request $request, MyHealthMobileTokenService $tokenService)
    {
        if ($request->bearerToken()) {
            $tokenService->revoke($request->bearerToken());
        }

        return $this->success([], 'Logged out successfully.');
    }
}
