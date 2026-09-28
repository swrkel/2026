<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\LeadsNew\Services\Release\LeadsNewReleaseChecklistService;

class LeadsNewReleaseController extends Controller
{
    public function checklist(LeadsNewReleaseChecklistService $service)
    {
        return view('leadsnew::release.checklist', [
            'items' => $service->items(),
        ]);
    }
}
