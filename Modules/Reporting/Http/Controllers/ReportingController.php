<?php

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Routing\Controller;

class ReportingController extends Controller
{
    /**
     * Reporting Dashboard
     */
    public function index()
    {
        return view(
            'reporting::dashboard.index'
        );
    }
}