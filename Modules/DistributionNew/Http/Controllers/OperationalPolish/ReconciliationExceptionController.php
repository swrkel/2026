<?php

namespace Modules\DistributionNew\Http\Controllers\OperationalPolish;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\OperationalPolish\ReconciliationExceptionService;

class ReconciliationExceptionController extends Controller
{
    public function index()
    {
        return view('distributionnew::operational_polish.reconciliation_exception.index');
    }
}
