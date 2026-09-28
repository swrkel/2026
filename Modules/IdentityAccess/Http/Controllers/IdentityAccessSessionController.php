<?php

namespace Modules\IdentityAccess\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\IdentityAccess\Entities\IdentityAccessSession;
use Modules\IdentityAccess\Services\Session\IdentitySessionService;

class IdentityAccessSessionController extends Controller
{
    public function index()
    {
        $sessions = IdentityAccessSession::latest()->paginate(50);
        return view('identityaccess::sessions.index', compact('sessions'));
    }

    public function revoke($id, IdentitySessionService $service)
    {
        $service->revoke((int) $id);
        return back()->with('status', 'Session revoked successfully.');
    }
}
