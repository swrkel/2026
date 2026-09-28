<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;

class KitchenDisplayController extends Controller
{
    public function index()
    {
        return view('pos::kitchen.index', [
            'title' => __('pos::lang.kitchen_display'),
        ]);
    }
}
