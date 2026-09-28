<?php

namespace Modules\POSModule\Http\Controllers;

use Illuminate\Routing\Controller;

class POSModuleController extends Controller
{
    public function index()
    {
        return view('posmodule::index');
    }
}
