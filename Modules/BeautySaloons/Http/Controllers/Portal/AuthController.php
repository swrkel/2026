<?php

namespace Modules\BeautySaloons\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Portal\PortalCustomerSessionService;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('beautysaloons::portal.auth.login');
    }

    public function login(Request $request, PortalCustomerSessionService $session)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! $session->attempt($data['login'], $data['password'])) {
            return back()->withErrors(['login' => 'Invalid customer login details.'])->withInput();
        }

        return redirect()->route('beautysaloons.portal.dashboard');
    }

    public function logout(PortalCustomerSessionService $session)
    {
        $session->logout();
        return redirect()->route('beautysaloons.portal.login');
    }
}
