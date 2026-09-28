<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BankingPlaceholderController extends Controller
{
    public function show(string $module)
    {
        $title = Str::of($module)->replace('-', ' ')->title();
        return view('bankingui::testing.placeholder', compact('module', 'title'));
    }
}
