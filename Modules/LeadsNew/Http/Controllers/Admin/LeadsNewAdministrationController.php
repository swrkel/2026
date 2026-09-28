<?php

namespace Modules\LeadsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;

class LeadsNewAdministrationController extends Controller
{
    public function index()
    {
        return view('leadsnew::admin.index');
    }
}
