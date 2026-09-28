<?php

namespace Modules\IdentityAccess\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class IdentityAccessSettingController extends Controller
{
    public function index()
    {
        return view('identityaccess::settings.index');
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Identity Access settings saved.');
    }
}
