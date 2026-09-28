<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;

class PluginController extends Controller
{
    public function index()
    {
        return view('pos::plugins.index');
    }
}
