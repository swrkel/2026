<?php

namespace Modules\Reporting\Http\Controllers\Recovery;

use Illuminate\Routing\Controller;

class RecoveryReportController extends Controller
{
    /**
     * Recovery Reporting Dashboard
     */
    public function index()
    {
        return view(
            'reporting::recovery.index'
        );
    }
}