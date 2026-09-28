<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\LeadsNew\Support\LeadsNewToolbar;

class LeadsNewUiController extends Controller
{
    public function index()
    {
        return $this->standards();
    }

    public function standards()
    {
        return view('leadsnew::ui.standards', [
            'toolbar' => LeadsNewToolbar::standard(),
        ]);
    }
}
