<?php

namespace Modules\Reporting\Http\Controllers\Compliance;

use Illuminate\Routing\Controller;

class ComplianceReportController extends Controller
{
    /**
     * Compliance Reporting Dashboard
     */
    public function index()
    {
        return view(
            'reporting::compliance.index'
        );
    }
}