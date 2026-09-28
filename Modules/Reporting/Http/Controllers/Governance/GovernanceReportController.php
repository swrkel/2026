<?php

namespace Modules\Reporting\Http\Controllers\Governance;

use Illuminate\Routing\Controller;

class GovernanceReportController extends Controller
{
    /**
     * Governance Reporting Dashboard
     */
    public function index()
    {
        return view(
            'reporting::governance.index'
        );
    }
}