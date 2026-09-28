<?php

namespace Modules\HRManager\Http\Controllers;

use App\Http\Controllers\Controller;

class HRSettingsController extends Controller
{
    public function index()
    {
        return view('hrmanager::settings.index');
    }
}
