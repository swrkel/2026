<?php

namespace Modules\BeautySaloons\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\BeautySaloons\Entities\BeautyCustomer;
use Modules\BeautySaloons\Services\Api\BeautyPortalApiResponse;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required', 'string']]);
        $customer = BeautyCustomer::query()->where('mobile', $data['login'])->orWhere('email', $data['login'])->first();
        $hash = $customer->portal_password ?? $customer->password ?? null;

        if (! $customer || ! $hash || ! Hash::check($data['password'], $hash)) {
            return BeautyPortalApiResponse::error('Invalid login details.', 401);
        }

        $token = method_exists($customer, 'createToken') ? $customer->createToken('beauty-saloons-mobile')->plainTextToken : null;
        return BeautyPortalApiResponse::success(['customer' => $customer, 'token' => $token], 'Login successful.');
    }

    public function logout(Request $request)
    {
        optional($request->user()->currentAccessToken())->delete();
        return BeautyPortalApiResponse::success([], 'Logout successful.');
    }
}
