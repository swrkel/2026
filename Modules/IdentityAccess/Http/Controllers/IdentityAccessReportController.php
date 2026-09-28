<?php

namespace Modules\IdentityAccess\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\IdentityAccess\Entities\IdentityAccessSecurityEvent;

class IdentityAccessReportController extends Controller
{
    public function index()
    {
        $events = IdentityAccessSecurityEvent::latest()->paginate(100);
        return view('identityaccess::reports.index', compact('events'));
    }
}
