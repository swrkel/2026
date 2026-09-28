<?php

namespace Modules\DistributionNew\Http\Controllers\OperationalPolish;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\OperationalPolish\CollectionControlService;

class CollectionControlController extends Controller
{
    public function index()
    {
        return view('distributionnew::operational_polish.collection_control.index');
    }
}
