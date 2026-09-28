<?php

namespace Modules\IdentityAccess\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\IdentityAccess\Services\Auth\IdentityAuthenticationService;

class IdentityAccessApiController extends Controller
{
    public function authenticate(Request $request, IdentityAuthenticationService $auth)
    {
        $data = $request->validate([
            'portal_type' => 'required|string|max:60',
            'passcode' => 'required|string|max:100',
        ]);

        return response()->json($auth->authenticatePasscode($data['portal_type'], $data['passcode'], $request->all()));
    }

    public function verifyOtp(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'OTP verification endpoint foundation created.']);
    }

    public function logout(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Logout endpoint foundation created.']);
    }
}
