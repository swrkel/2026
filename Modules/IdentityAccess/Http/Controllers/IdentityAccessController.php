<?php

namespace Modules\IdentityAccess\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\IdentityAccess\Entities\IdentityAccessLoginIdentity;
use Modules\IdentityAccess\Entities\IdentityAccessSession;
use Modules\IdentityAccess\Entities\IdentityAccessOtp;
use Modules\IdentityAccess\Entities\IdentityAccessSecurityEvent;

class IdentityAccessController extends Controller
{
    public function index()
    {
        $stats = [
            'identities' => IdentityAccessLoginIdentity::count(),
            'active_sessions' => IdentityAccessSession::where('status', 'active')->count(),
            'pending_otps' => IdentityAccessOtp::where('status', 'pending')->count(),
            'security_events_today' => IdentityAccessSecurityEvent::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('identityaccess::dashboard.index', compact('stats'));
    }
}
