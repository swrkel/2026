<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;

class DataController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'module' => 'customers']);
    }
}
